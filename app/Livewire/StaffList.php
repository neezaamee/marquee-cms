<?php

namespace App\Livewire;

use App\Models\Employee;
use Livewire\Component;
use Livewire\WithPagination;

class StaffList extends Component
{
    use WithPagination;

    public $search = '';
    public $filterStatus = '';
    public $filterDesignation = '';
    public $confirmingDeletionId = null;

    protected $paginationTheme = 'bootstrap';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => ''],
        'filterDesignation' => ['except' => ''],
    ];

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterStatus() { $this->resetPage(); }
    public function updatingFilterDesignation() { $this->resetPage(); }

    /**
     * Set the record ID for deletion confirmation.
     */
    public function confirmDeletion(int $id)
    {
        $this->confirmingDeletionId = $id;
    }

    /**
     * Delete the confirmed staff member and their CMS login.
     */
    public function deleteRecord()
    {
        // Require manage_staff permission or appropriate role
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasRole(['owner', 'branch_manager']) || auth()->user()->hasPermission('manage_staff'), 403);

        if ($this->confirmingDeletionId) {
            $staff = Employee::findOrFail($this->confirmingDeletionId);

            // Tenant security check
            if (!auth()->user()->isSuperAdmin() && $staff->marquee_id !== auth()->user()->marquee_id) {
                session()->flash('error', 'Unauthorized operation.');
                $this->confirmingDeletionId = null;
                return;
            }

            // Also delete the linked user login account if they have one
            if ($staff->user_id && $staff->user) {
                $staff->user->delete();
            }

            $staff->delete();
            $this->confirmingDeletionId = null;
            session()->flash('success', 'Employee removed successfully.');
        }
    }

    public function render()
    {
        $query = Employee::with(['branch', 'user']);

        // Search filter
        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('employee_id', 'like', '%' . $this->search . '%')
                  ->orWhere('cnic', 'like', '%' . $this->search . '%')
                  ->orWhere('mobile_number', 'like', '%' . $this->search . '%');
            });
        }

        // Status filter
        if (!empty($this->filterStatus)) {
            $query->where('status', $this->filterStatus);
        }

        // Designation filter
        if (!empty($this->filterDesignation)) {
            $query->where('designation', $this->filterDesignation);
        }

        $employees = $query->latest()->paginate(15);
        $designations = Employee::DESIGNATIONS;
        $statuses = Employee::STATUSES;

        return view('livewire.staff-list', compact('employees', 'designations', 'statuses'));
    }
}
