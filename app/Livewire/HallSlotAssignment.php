<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\Hall;
use App\Models\HallSlot;
use App\Models\Marquee;
use App\Models\Slot;
use Livewire\Component;

class HallSlotAssignment extends Component
{
    // Scopes
    public $marquee_id = null;
    public $branch_id = null;
    public $selectedHallId = null;

    // Selections
    public $assignedSlotIds = [];

    // Dropdowns data
    public $marquees = [];
    public $branches = [];
    public $halls = [];
    public $activeSlots = [];

    /**
     * Mount component.
     */
    public function mount()
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            $this->marquees = Marquee::orderBy('name')->get();
        } else {
            $this->marquee_id = $user->marquee_id;
            $this->branches = Branch::where('marquee_id', $this->marquee_id)->orderBy('name')->get();
            $this->activeSlots = Slot::where('marquee_id', $this->marquee_id)->where('status', 'active')->orderBy('start_time')->get();

            if ($user->branch_id && !$user->isBusinessOwner()) {
                $this->branch_id = $user->branch_id;
                $this->updatedBranchId($this->branch_id);
            }
        }
    }

    /**
     * Super Admin changes Marquee.
     */
    public function updatedMarqueeId($value)
    {
        $this->branches = $value ? Branch::where('marquee_id', $value)->orderBy('name')->get() : [];
        $this->activeSlots = $value ? Slot::where('marquee_id', $value)->where('status', 'active')->orderBy('start_time')->get() : [];
        $this->branch_id = null;
        $this->halls = [];
        $this->selectedHallId = null;
        $this->assignedSlotIds = [];
    }

    /**
     * User changes Branch.
     */
    public function updatedBranchId($value)
    {
        $this->halls = $value ? Hall::where('branch_id', $value)->where('status', 'active')->orderBy('hall_name')->get() : [];
        $this->selectedHallId = null;
        $this->assignedSlotIds = [];
    }

    /**
     * User selects a Hall.
     */
    public function updatedSelectedHallId($value)
    {
        if ($value) {
            $hall = Hall::findOrFail($value);
            
            // Security check
            if (!auth()->user()->isSuperAdmin() && $hall->marquee_id !== auth()->user()->marquee_id) {
                abort(403, 'Unauthorized.');
            }

            // Fetch assigned slot IDs
            $this->assignedSlotIds = $hall->slots()->pluck('slots.id')->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->assignedSlotIds = [];
        }
    }

    /**
     * Toggle a slot assignment for the selected hall.
     */
    public function toggleSlotAssignment(int $slotId)
    {
        if (!$this->selectedHallId) return;

        $hall = Hall::findOrFail($this->selectedHallId);
        $slot = Slot::findOrFail($slotId);

        // Security check
        if (!auth()->user()->isSuperAdmin() && 
            ($hall->marquee_id !== auth()->user()->marquee_id || $slot->marquee_id !== auth()->user()->marquee_id)) {
            abort(403, 'Unauthorized.');
        }

        $strSlotId = (string)$slotId;

        if (in_array($strSlotId, $this->assignedSlotIds)) {
            // Remove assignment
            $hall->slots()->detach($slotId);
            $this->assignedSlotIds = array_diff($this->assignedSlotIds, [$strSlotId]);
            session()->flash('success', 'Slot assignment removed.');
        } else {
            // Add assignment
            $hall->slots()->attach($slotId, [
                'marquee_id' => $hall->marquee_id,
                'created_by' => auth()->id(),
                'status' => 'active',
            ]);
            $this->assignedSlotIds[] = $strSlotId;
            session()->flash('success', 'Slot assigned successfully.');
        }
    }

    /**
     * Render component.
     */
    public function render()
    {
        return view('livewire.hall-slot-assignment');
    }
}
