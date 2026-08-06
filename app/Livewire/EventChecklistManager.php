<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\EventChecklist;
use App\Models\Employee;
use Livewire\Component;

class EventChecklistManager extends Component
{
    public $selectedBookingId;
    public $newTaskName;
    public $newTaskCategory = 'Catering';
    public $newTaskAssigneeId;

    public function mount()
    {
        $firstBooking = Booking::orderBy('booking_date', 'desc')->first();
        if ($firstBooking) {
            $this->selectedBookingId = $firstBooking->id;
        }
    }

    public function toggleChecklistItem($itemId)
    {
        $item = EventChecklist::find($itemId);
        if ($item) {
            $newStatus = $item->status === 'Completed' ? 'Pending' : 'Completed';
            $item->update([
                'status' => $newStatus,
                'completed_at' => $newStatus === 'Completed' ? now() : null,
            ]);
        }
    }

    public function addChecklistItem()
    {
        $this->validate([
            'newTaskName' => 'required|string|max:255',
            'newTaskCategory' => 'required|string',
            'newTaskAssigneeId' => 'nullable|exists:employees,id',
        ]);

        if (!$this->selectedBookingId) {
            return;
        }

        EventChecklist::create([
            'marquee_id' => auth()->user()->marquee_id ?? 1,
            'booking_id' => $this->selectedBookingId,
            'task_name' => $this->newTaskName,
            'category' => $this->newTaskCategory,
            'status' => 'Pending',
            'assigned_to' => $this->newTaskAssigneeId ?: null,
        ]);

        $this->newTaskName = '';
        $this->newTaskAssigneeId = null;
        session()->flash('success', 'Task added to operations checklist.');
    }

    public function render()
    {
        $bookings = Booking::with(['customer', 'hall'])->orderBy('booking_date', 'desc')->get();
        $employees = Employee::where('status', 'Active')->get();

        $checklistItems = [];
        if ($this->selectedBookingId) {
            $checklistItems = EventChecklist::where('booking_id', $this->selectedBookingId)
                ->with('assignee')
                ->get();
        }

        return view('livewire.event-checklist-manager', [
            'bookings' => $bookings,
            'employees' => $employees,
            'checklistItems' => $checklistItems,
        ])->layout('layouts.admin');
    }
}
