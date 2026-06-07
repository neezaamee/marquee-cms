<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\Hall;
use Livewire\Component;
use Livewire\WithPagination;

class HallList extends Component
{
    use WithPagination;

    // Search and filters
    public $search = '';
    public $filterBranch = '';
    public $filterStatus = '';

    // Pagination styling
    protected $paginationTheme = 'bootstrap';

    // Query parameters synchronization
    protected $queryString = [
        'search' => ['except' => ''],
        'filterBranch' => ['except' => ''],
        'filterStatus' => ['except' => ''],
    ];

    /**
     * Reset pagination when search/filter inputs change.
     */
    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterBranch() { $this->resetPage(); }
    public function updatingFilterStatus() { $this->resetPage(); }

    /**
     * Delete a hall.
     */
    public function deleteHall(int $id)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('delete_halls'), 403);

        $hall = Hall::findOrFail($id);
        
        // Tenant security check
        if (!auth()->user()->isSuperAdmin() && $hall->marquee_id !== auth()->user()->marquee_id) {
            session()->flash('error', 'Unauthorized operation.');
            return;
        }

        $hall->delete();
        session()->flash('success', 'Hall deleted successfully.');
    }

    /**
     * Render the component.
     */
    public function render()
    {
        $user = auth()->user();
        
        // 1. Build branches list for selection filter
        $branchesQuery = Branch::query();
        if (!$user->isSuperAdmin()) {
            $branchesQuery->where('marquee_id', $user->marquee_id);
        }
        $branches = $branchesQuery->orderBy('name')->get();

        // 2. Build halls query
        $query = Hall::with(['branch', 'creator']);

        // Search filter
        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('hall_name', 'like', '%' . $this->search . '%')
                  ->orWhere('hall_code', 'like', '%' . $this->search . '%')
                  ->orWhere('hall_type', 'like', '%' . $this->search . '%');
            });
        }

        // Branch filter: if user belongs to specific branch, force scope. Otherwise use select filter
        if (!$user->isSuperAdmin() && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
            $this->filterBranch = $user->branch_id; // Sync filter select value
        } elseif (!empty($this->filterBranch)) {
            $query->where('branch_id', $this->filterBranch);
        }

        // Status filter
        if (!empty($this->filterStatus)) {
            $query->where('status', $this->filterStatus);
        }

        $halls = $query->latest()->paginate(10);

        return view('livewire.hall-list', compact('halls', 'branches'));
    }
}
