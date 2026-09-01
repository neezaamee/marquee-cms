<?php

namespace App\Livewire;

use App\Models\Hall;
use App\Models\Slot;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Livewire\Component;

class AvailabilityChecker extends Component
{
    // Scopes
    public $branches = [];
    public $selectedBranchId = '';
    public $isMultiBranch = false;

    public $halls = [];
    public $selectedHallId = '';
    public $selectedDate = '';

    // Selection target type (slot vs custom time range)
    public $checkType = 'slot'; // slot, custom
    public $selectedSlotId = '';
    public $customStart = '';
    public $customEnd = '';

    // Results state
    public $availabilityChecked = false;
    public $isAvailable = false;
    public $conflictDetails = null;
    public $slotStatusList = [];

    // Dropdown options
    public $slotOptions = [];

    public function mount()
    {
        $user = auth()->user();
        $marqueeId = $user ? $user->getActiveMarqueeId() : null;
        
        $this->branches = $user ? $user->getAccessibleBranches($marqueeId) : collect();
        $this->isMultiBranch = $this->branches->count() > 1;

        if ($this->branches->isNotEmpty()) {
            if ($user && $user->branch_id && $user->hasAccessToBranch($user->branch_id, $marqueeId)) {
                $this->selectedBranchId = (string) $user->branch_id;
            } else {
                $this->selectedBranchId = (string) $this->branches->first()->id;
            }
        }

        $this->loadHalls();
        $this->selectedDate = Carbon::today()->format('Y-m-d');

        if ($this->halls->isNotEmpty()) {
            $this->selectedHallId = (string) $this->halls->first()->id;
            $this->loadSlots();
        }
    }

    public function updatedSelectedBranchId($value)
    {
        $this->selectedBranchId = (string) $value;
        $this->loadHalls();
        $this->selectedHallId = $this->halls->isNotEmpty() ? (string) $this->halls->first()->id : '';
        $this->loadSlots();
        $this->runCheck();
    }

    public function loadHalls()
    {
        $marqueeId = auth()->user()->getActiveMarqueeId();
        if (!empty($this->selectedBranchId)) {
            $this->halls = Hall::where('marquee_id', $marqueeId)
                ->where('branch_id', $this->selectedBranchId)
                ->where('status', 'active')
                ->orderBy('hall_name')
                ->get();
        } else {
            $this->halls = collect();
        }
    }

    public function updatedSelectedHallId()
    {
        $this->loadSlots();
        $this->runCheck();
    }

    public function updatedSelectedDate()
    {
        $this->loadSlots();
        $this->runCheck();
    }

    public function updatedSelectedSlotId()
    {
        $this->runCheck();
    }

    public function updatedCustomStart()
    {
        $this->runCheck();
    }

    public function updatedCustomEnd()
    {
        $this->runCheck();
    }

    public function updatedCheckType()
    {
        // Reset inputs on toggle
        $this->selectedSlotId = '';
        $this->customStart = '';
        $this->customEnd = '';
        $this->availabilityChecked = false;
        $this->conflictDetails = null;
    }

    /**
     * Fetch active slots and determine daily availability list status.
     */
    public function loadSlots()
    {
        if (empty($this->selectedHallId) || empty($this->selectedDate)) {
            $this->slotOptions = [];
            $this->slotStatusList = [];
            return;
        }

        $hall = Hall::findOrFail($this->selectedHallId);
        
        // Retrieve slots for this marquee
        $this->slotOptions = Slot::where('marquee_id', $hall->marquee_id)
            ->where('status', 'active')
            ->orderBy('start_time')
            ->get();

        $service = new AvailabilityService();
        $this->slotStatusList = [];

        foreach ($this->slotOptions as $slot) {
            $conflicting = $service->getConflictingBooking(
                $this->selectedHallId,
                $this->selectedDate,
                $slot->start_time,
                $slot->end_time
            );

            $status = 'Available';
            $conflictInfo = null;

            if ($conflicting) {
                $status = $conflicting->booking_status === 'Reserved' ? 'Reserved' : 'Booked';
                $conflictInfo = [
                    'id' => $conflicting->id,
                    'status' => $conflicting->booking_status,
                    'start' => $conflicting->start_time->format('h:i A'),
                    'end' => $conflicting->end_time->format('h:i A'),
                    'by' => $conflicting->creator?->name ?? 'System',
                ];
            }

            $this->slotStatusList[] = [
                'id' => $slot->id,
                'name' => $slot->slot_name,
                'start' => Carbon::parse($slot->start_time)->format('h:i A'),
                'end' => Carbon::parse($slot->end_time)->format('h:i A'),
                'status' => $status,
                'conflict' => $conflictInfo,
            ];
        }
    }

    /**
     * Run availability checks based on selections.
     */
    public function runCheck()
    {
        if (empty($this->selectedHallId) || empty($this->selectedDate)) {
            $this->availabilityChecked = false;
            return;
        }

        $service = new AvailabilityService();

        if ($this->checkType === 'slot') {
            if (empty($this->selectedSlotId)) {
                $this->availabilityChecked = false;
                return;
            }

            $slot = Slot::findOrFail($this->selectedSlotId);
            
            $conflicting = $service->getConflictingBooking(
                $this->selectedHallId,
                $this->selectedDate,
                $slot->start_time,
                $slot->end_time
            );

            if ($conflicting) {
                $this->isAvailable = false;
                $this->conflictDetails = $conflicting;
            } else {
                $this->isAvailable = true;
                $this->conflictDetails = null;
            }

            $this->availabilityChecked = true;
        } else {
            if (empty($this->customStart) || empty($this->customEnd)) {
                $this->availabilityChecked = false;
                return;
            }

            // Perform simple time pattern validation
            if (!preg_match('/^\d{2}:\d{2}$/', $this->customStart) || !preg_match('/^\d{2}:\d{2}$/', $this->customEnd)) {
                $this->availabilityChecked = false;
                return;
            }

            $conflicting = $service->getConflictingBooking(
                $this->selectedHallId,
                $this->selectedDate,
                $this->customStart,
                $this->customEnd
            );

            if ($conflicting) {
                $this->isAvailable = false;
                $this->conflictDetails = $conflicting;
            } else {
                $this->isAvailable = true;
                $this->conflictDetails = null;
            }

            $this->availabilityChecked = true;
        }
    }

    public function render()
    {
        return view('livewire.availability-checker');
    }
}
