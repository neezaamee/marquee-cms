<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\Hall;
use App\Models\Marquee;
use Illuminate\Validation\Rule;
use Livewire\Component;

class HallForm extends Component
{
    // Mode
    public $isEditMode = false;
    public $hallId = null;

    // Fields
    public $marquee_id = null;
    public $branch_id = null;
    public $hall_name = '';
    public $hall_code = '';
    public $capacity = '';
    public $hall_type = '';
    public $default_booking_price = '';
    public $description = '';
    public $status = 'active';

    // Dropdown lists
    public $marquees = [];
    public $branches = [];

    /**
     * Initialize component.
     */
    public function mount($hall = null)
    {
        $user = auth()->user();

        // Initialize lists
        if ($user->isSuperAdmin()) {
            $this->marquees = Marquee::orderBy('name')->get();
        } else {
            $this->marquee_id = $user->marquee_id;
            $this->branches = Branch::where('marquee_id', $this->marquee_id)->orderBy('name')->get();
            
            // Default branch for Branch Managers
            if ($user->branch_id) {
                $this->branch_id = $user->branch_id;
            }
        }

        // Check if editing
        if ($hall) {
            $this->isEditMode = true;
            $this->hallId = $hall->id;
            $this->marquee_id = $hall->marquee_id;
            
            // Reload branches for super admin based on hall's marquee
            if ($user->isSuperAdmin()) {
                $this->branches = Branch::where('marquee_id', $this->marquee_id)->orderBy('name')->get();
            }

            $this->branch_id = $hall->branch_id;
            $this->hall_name = $hall->hall_name;
            $this->hall_code = $hall->hall_code;
            $this->capacity = $hall->capacity;
            $this->hall_type = $hall->hall_type;
            $this->default_booking_price = $hall->default_booking_price;
            $this->description = $hall->description;
            $this->status = $hall->status;
        }
    }

    /**
     * React to marquee_id change (for Super Admins).
     */
    public function updatedMarqueeId($value)
    {
        if (auth()->user()->isSuperAdmin() && $value) {
            $this->branches = Branch::where('marquee_id', $value)->orderBy('name')->get();
            $this->branch_id = null; // Reset branch selection
        } else {
            $this->branches = [];
            $this->branch_id = null;
        }
    }

    /**
     * Validation rules.
     */
    protected function rules()
    {
        return [
            'marquee_id' => 'required|exists:marquees,id',
            'branch_id' => 'required|exists:branches,id',
            'hall_name' => 'required|string|max:255',
            'hall_code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('halls', 'hall_code')
                    ->ignore($this->hallId)
                    ->where('branch_id', $this->branch_id)
                    ->whereNull('deleted_at'),
            ],
            'capacity' => 'required|integer|min:1',
            'hall_type' => 'required|string|max:255',
            'default_booking_price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ];
    }

    /**
     * Custom error messages.
     */
    protected $messages = [
        'hall_code.unique' => 'This hall code is already registered in the selected branch.',
    ];

    /**
     * Save the hall (Create / Update).
     */
    public function save()
    {
        // Force set marquee_id for non-super admins to prevent manipulation
        if (!auth()->user()->isSuperAdmin()) {
            $this->marquee_id = auth()->user()->marquee_id;
            
            // Force branch_id for Branch Managers
            if (auth()->user()->branch_id) {
                $this->branch_id = auth()->user()->branch_id;
            }
        }

        $validatedData = $this->validate();

        if ($this->isEditMode) {
            $hall = Hall::findOrFail($this->hallId);
            
            // Security check
            if (!auth()->user()->isSuperAdmin() && $hall->marquee_id !== auth()->user()->marquee_id) {
                abort(403, 'Unauthorized operation.');
            }

            $hall->update($validatedData);
            session()->flash('success', 'Hall updated successfully.');
        } else {
            Hall::create($validatedData);
            session()->flash('success', 'Hall created successfully.');
        }

        return redirect()->route('halls.index');
    }

    /**
     * Render component.
     */
    public function render()
    {
        return view('livewire.hall-form');
    }
}
