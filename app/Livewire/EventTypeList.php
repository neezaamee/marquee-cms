<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\EventType;
use Livewire\Component;
use Livewire\WithPagination;

class EventTypeList extends Component
{
    use WithPagination;

    // Filters
    public $search = '';
    public $filterStatus = '';
    public $filterBranch = '';

    protected $paginationTheme = 'bootstrap';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => ''],
        'filterBranch' => ['except' => ''],
    ];

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterStatus() { $this->resetPage(); }
    public function updatingFilterBranch() { $this->resetPage(); }

    public $confirmingDeletionId = null;

    /**
     * Set the record ID for deletion confirmation.
     */
    public function confirmDeletion(int $id)
    {
        $this->confirmingDeletionId = $id;
    }

    /**
     * Delete the confirmed event type (soft delete).
     */
    public function deleteRecord()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('event-types.delete'), 403);

        if ($this->confirmingDeletionId) {
            $eventType = EventType::findOrFail($this->confirmingDeletionId);

            // Scope Check
            if (!auth()->user()->isSuperAdmin() && $eventType->marquee_id !== auth()->user()->marquee_id) {
                session()->flash('error', 'Unauthorized operation.');
                $this->confirmingDeletionId = null;
                return;
            }

            // Prevent deletion of system default event types
            if ($eventType->is_system_default) {
                session()->flash('error', 'System default event types cannot be deleted.');
                $this->confirmingDeletionId = null;
                return;
            }

            $eventType->delete();
            $this->confirmingDeletionId = null;
            session()->flash('success', 'Event type deleted successfully.');
        }
    }

    public function render()
    {
        $user = auth()->user();

        // Load branches for filter selection
        $branchesQuery = Branch::query();
        if (!$user->isSuperAdmin()) {
            $branchesQuery->where('marquee_id', $user->marquee_id);
        }
        $branches = $branchesQuery->orderBy('name')->get();

        // Build Event Types Query
        $query = EventType::with(['branch', 'creator']);

        // Real-time search by name/code
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('event_type_name', 'like', '%' . $this->search . '%')
                  ->orWhere('event_type_code', 'like', '%' . $this->search . '%');
            });
        }

        // Branch filter
        if ($user->branch_id) {
            // Force branch scoping for branch managers
            $query->where(function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id)
                  ->orWhereNull('branch_id');
            });
            $this->filterBranch = $user->branch_id;
        } elseif (!empty($this->filterBranch)) {
            $query->where('branch_id', $this->filterBranch);
        }

        // Status filter
        if (!empty($this->filterStatus)) {
            $query->where('status', $this->filterStatus);
        }

        // Pagination and Sorting by sort_order
        $eventTypes = $query->orderBy('sort_order', 'asc')
            ->orderBy('event_type_name', 'asc')
            ->paginate(15);

        return view('livewire.event-type-list', compact('eventTypes', 'branches'));
    }
}
