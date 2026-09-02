<?php

namespace App\Livewire;

use App\Models\Marquee;
use App\Models\Slot;
use Illuminate\Validation\Rule;
use Livewire\Component;

class SlotForm extends Component
{
    // Mode
    public $isEditMode = false;
    public $slotId = null;

    // Fields
    public $marquee_id = null;
    public $slot_name = '';
    public $start_time = '';
    public $end_time = '';
    public $description = '';
    public $status = 'active';

    // Dropdowns
    public $marquees = [];

    /**
     * Mount component.
     */
    public function mount($slot = null)
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            $this->marquees = Marquee::orderBy('name')->get();
        } else {
            $this->marquees = $user->getAccessibleMarquees();
            $this->marquee_id = $user->getActiveMarqueeId();
        }

        if ($slot) {
            $this->isEditMode = true;
            $this->slotId = $slot->id;
            $this->marquee_id = $slot->marquee_id;
            $this->slot_name = $slot->slot_name;
            
            // Format start and end times for HTML input type="time"
            $this->start_time = date('H:i', strtotime($slot->start_time));
            $this->end_time = date('H:i', strtotime($slot->end_time));
            
            $this->description = $slot->description;
            $this->status = $slot->status;
        }
    }

    /**
     * Validation rules.
     */
    protected function rules()
    {
        return [
            'marquee_id' => 'required|exists:marquees,id',
            'slot_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('slots', 'slot_name')
                    ->ignore($this->slotId)
                    ->where('marquee_id', $this->marquee_id)
                    ->whereNull('deleted_at'),
            ],
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ];
    }

    /**
     * Custom error messages.
     */
    protected $messages = [
        'slot_name.unique' => 'A shift slot with this name already exists.',
        'end_time.after' => 'The end time must be after the start time.',
    ];

    /**
     * Save the slot.
     */
    public function save()
    {
        $user = auth()->user();

        if (!$user->isSuperAdmin()) {
            $this->marquee_id = $user->getActiveMarqueeId();
        }

        $validatedData = $this->validate();

        if ($this->isEditMode) {
            $slot = Slot::findOrFail($this->slotId);

            // Security check
            if (!$user->isSuperAdmin() && !$user->hasAccessToMarquee($slot->marquee_id)) {
                abort(403, 'Unauthorized operation.');
            }

            $slot->update($validatedData);
            session()->flash('success', 'Slot updated successfully.');
        } else {
            Slot::create($validatedData);
            session()->flash('success', 'Slot created successfully.');
        }

        return redirect()->route('slots.index');
    }

    /**
     * Render component.
     */
    public function render()
    {
        return view('livewire.slot-form');
    }
}
