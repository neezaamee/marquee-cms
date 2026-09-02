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

            if (request()->has('hall_id') || request()->has('selectedHallId')) {
                $hallId = (int) (request()->query('hall_id') ?: request()->query('selectedHallId'));
                $hall = Hall::find($hallId);
                if ($hall) {
                    $this->marquee_id = $hall->marquee_id;
                    $this->branches = Branch::where('marquee_id', $this->marquee_id)->where('status', 'active')->orderBy('name')->get();
                    $this->activeSlots = Slot::where('marquee_id', $this->marquee_id)->where('status', 'active')->orderBy('start_time')->get();
                    $this->branch_id = $hall->branch_id;
                    $this->updatedBranchId($this->branch_id);
                    $this->selectedHallId = $hall->id;
                    $this->updatedSelectedHallId($this->selectedHallId);
                }
            } elseif (request()->has('branch_id')) {
                $this->branch_id = (int) request()->query('branch_id');
                $targetBranch = Branch::find($this->branch_id);
                if ($targetBranch) {
                    $this->marquee_id = $targetBranch->marquee_id;
                    $this->branches = Branch::where('marquee_id', $this->marquee_id)->where('status', 'active')->orderBy('name')->get();
                    $this->activeSlots = Slot::where('marquee_id', $this->marquee_id)->where('status', 'active')->orderBy('start_time')->get();
                    $this->updatedBranchId($this->branch_id);
                }
            }
        } else {
            $this->marquees = $user->getAccessibleMarquees();
            $this->marquee_id = $user->getActiveMarqueeId();
            $this->branches = $user->getAccessibleBranches($this->marquee_id);
            $this->activeSlots = Slot::where('marquee_id', $this->marquee_id)->where('status', 'active')->orderBy('start_time')->get();

            if (request()->has('hall_id') || request()->has('selectedHallId')) {
                $hallId = (int) (request()->query('hall_id') ?: request()->query('selectedHallId'));
                $hall = Hall::find($hallId);
                if ($hall && $user->hasAccessToMarquee($hall->marquee_id)) {
                    $this->marquee_id = $hall->marquee_id;
                    $this->branches = $user->getAccessibleBranches($this->marquee_id);
                    $this->activeSlots = Slot::where('marquee_id', $this->marquee_id)->where('status', 'active')->orderBy('start_time')->get();
                    $this->branch_id = $hall->branch_id;
                    $this->updatedBranchId($this->branch_id);
                    $this->selectedHallId = $hall->id;
                    $this->updatedSelectedHallId($this->selectedHallId);
                }
            } elseif (request()->has('branch_id')) {
                $reqBranchId = (int) request()->query('branch_id');
                if ($user->hasAccessToBranch($reqBranchId, $this->marquee_id)) {
                    $this->branch_id = $reqBranchId;
                    $this->updatedBranchId($this->branch_id);
                }
            } elseif ($user->branch_id && !$user->isBusinessOwner() && !$user->isAreaManager()) {
                $this->branch_id = $user->branch_id;
                $this->updatedBranchId($this->branch_id);
            } elseif ($this->branches->count() === 1) {
                $this->branch_id = $this->branches->first()->id;
                $this->updatedBranchId($this->branch_id);
            }
        }
    }

    /**
     * Super Admin or Multi-Business Owner changes Marquee.
     */
    public function updatedMarqueeId($value)
    {
        $user = auth()->user();
        if ($value && ($user->isSuperAdmin() || $user->hasAccessToMarquee($value))) {
            $this->marquee_id = (int) $value;
            $this->branches = $user->getAccessibleBranches($this->marquee_id);
            $this->activeSlots = Slot::where('marquee_id', $this->marquee_id)->where('status', 'active')->orderBy('start_time')->get();
            $this->branch_id = null;
            $this->halls = [];
            $this->selectedHallId = null;
            $this->assignedSlotIds = [];

            if ($this->branches->count() === 1) {
                $this->branch_id = $this->branches->first()->id;
                $this->updatedBranchId($this->branch_id);
            }
        } else {
            $this->branches = [];
            $this->activeSlots = [];
            $this->branch_id = null;
            $this->halls = [];
            $this->selectedHallId = null;
            $this->assignedSlotIds = [];
        }
    }

    /**
     * User changes Branch.
     */
    public function updatedBranchId($value)
    {
        $user = auth()->user();
        if ($value) {
            $branch = Branch::find($value);
            if ($branch && ($user->isSuperAdmin() || $user->hasAccessToBranch($branch->id, $this->marquee_id))) {
                $this->halls = Hall::where('branch_id', $branch->id)
                    ->where('status', 'active')
                    ->orderBy('hall_name')
                    ->get();
            } else {
                $this->halls = [];
            }
        } else {
            $this->halls = [];
        }

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
            $user = auth()->user();

            // Security check
            if (!$user->isSuperAdmin() && !$user->hasAccessToMarquee($hall->marquee_id)) {
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
        $user = auth()->user();

        // Security check
        $hasHallAccess = $user->isSuperAdmin() || $user->hasAccessToMarquee($hall->marquee_id);
        $hasSlotAccess = $user->isSuperAdmin() || $user->hasAccessToMarquee($slot->marquee_id);

        if (!$hasHallAccess || !$hasSlotAccess || $hall->marquee_id !== $slot->marquee_id) {
            abort(403, 'Unauthorized.');
        }

        $strSlotId = (string)$slotId;

        if (in_array($strSlotId, $this->assignedSlotIds)) {
            // Remove assignment
            $hall->slots()->detach($slotId);
            $this->assignedSlotIds = array_values(array_diff($this->assignedSlotIds, [$strSlotId]));
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
