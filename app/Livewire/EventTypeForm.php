<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\EventType;
use App\Models\Slot;
use Illuminate\Validation\Rule;
use Livewire\Component;

class EventTypeForm extends Component
{
    public $isEditMode = false;
    public $eventTypeId = null;

    // Fields
    public $event_type_name = '';
    public $event_type_code = '';
    public $branch_id = '';
    public $description = '';
    public $default_duration_hours = '';
    public $default_slot_preference = '';
    public $base_price = '';
    public $status = 'Active';
    public $sort_order = 0;
    public $is_system_default = false;

    // Dropdown arrays
    public $branches = [];
    public $slotShifts = [];

    public function mount($eventType = null)
    {
        $user = auth()->user();
        abort_unless($user->isSuperAdmin() || $user->hasPermission($eventType ? 'event-types.edit' : 'event-types.create'), 403);

        $marqueeId = $user->marquee_id;

        // Fetch tenant branches
        $this->branches = Branch::where('marquee_id', $marqueeId)->orderBy('name')->get();

        // Fetch active slot shifts for slot preference dropdown
        $this->slotShifts = Slot::where('marquee_id', $marqueeId)->where('status', 'active')->orderBy('name')->get();

        if ($eventType) {
            $this->isEditMode = true;
            $this->eventTypeId = $eventType->id;
            $this->event_type_name = $eventType->event_type_name;
            $this->event_type_code = $eventType->event_type_code;
            $this->branch_id = $eventType->branch_id ?? '';
            $this->description = $eventType->description ?? '';
            $this->default_duration_hours = $eventType->default_duration_hours;
            $this->default_slot_preference = $eventType->default_slot_preference ?? '';
            $this->base_price = $eventType->base_price;
            $this->status = $eventType->status;
            $this->sort_order = $eventType->sort_order;
            $this->is_system_default = $eventType->is_system_default;
        }
    }

    protected function rules()
    {
        $user = auth()->user();
        $marqueeId = $user->marquee_id;

        return [
            'event_type_name' => 'required|string|max:255',
            'event_type_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('event_types', 'event_type_code')
                    ->ignore($this->eventTypeId)
                    ->where('marquee_id', $marqueeId)
                    ->whereNull('deleted_at'),
            ],
            'branch_id' => [
                'nullable',
                Rule::exists('branches', 'id')->where('marquee_id', $marqueeId)->whereNull('deleted_at'),
            ],
            'description' => 'nullable|string',
            'default_duration_hours' => 'nullable|numeric|min:0.5|max:24',
            'default_slot_preference' => 'nullable|string|max:255',
            'base_price' => 'nullable|numeric|min:0',
            'status' => 'required|in:Active,Inactive',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }

    protected $messages = [
        'event_type_code.unique' => 'This event type code is already registered in your Marquee database.',
        'branch_id.exists' => 'The selected branch must belong to your Marquee.',
    ];

    /**
     * Save event type.
     */
    public function save()
    {
        $user = auth()->user();
        abort_unless($user->isSuperAdmin() || $user->hasPermission($this->isEditMode ? 'event-types.edit' : 'event-types.create'), 403);

        $validatedData = $this->validate();

        $eventTypeData = [
            'marquee_id' => auth()->user()->marquee_id,
            'branch_id' => $this->branch_id ?: null,
            'event_type_name' => $this->event_type_name,
            'event_type_code' => $this->event_type_code,
            'description' => $this->description,
            'default_duration_hours' => $this->default_duration_hours ?: null,
            'default_slot_preference' => $this->default_slot_preference ?: null,
            'base_price' => $this->base_price ?: null,
            'status' => $this->status,
            'sort_order' => $this->sort_order ?: 0,
        ];

        if ($this->isEditMode) {
            $eventType = EventType::findOrFail($this->eventTypeId);

            // Scope Check
            if (!auth()->user()->isSuperAdmin() && $eventType->marquee_id !== auth()->user()->marquee_id) {
                abort(403, 'Unauthorized.');
            }

            // System default protection: prevent editing code
            if ($eventType->is_system_default) {
                unset($eventTypeData['event_type_code']);
            }

            $eventType->update($eventTypeData);
            session()->flash('success', 'Event type updated successfully.');
        } else {
            $eventTypeData['is_system_default'] = false;
            EventType::create($eventTypeData);
            session()->flash('success', 'Event type created successfully.');
        }

        return redirect()->route('event-types.index');
    }

    public function render()
    {
        return view('livewire.event-type-form');
    }
}
