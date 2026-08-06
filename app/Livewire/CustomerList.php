<?php

namespace App\Livewire;

use App\Models\Customer;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerList extends Component
{
    use WithPagination;

    // Search and filters
    public $search = '';
    public $filterType = '';
    public $filterStatus = '';

    // Pagination styling
    protected $paginationTheme = 'bootstrap';

    // Query parameters synchronization
    protected $queryString = [
        'search' => ['except' => ''],
        'filterType' => ['except' => ''],
        'filterStatus' => ['except' => ''],
    ];

    /**
     * Reset pagination when search/filter inputs change.
     */
    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterType() { $this->resetPage(); }
    public function updatingFilterStatus() { $this->resetPage(); }

    public $confirmingDeletionId = null;

    /**
     * Set the record ID for deletion confirmation.
     */
    public function confirmDeletion(int $id)
    {
        $this->confirmingDeletionId = $id;
    }

    /**
     * Delete the confirmed customer.
     */
    public function deleteRecord()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('delete_bookings'), 403);

        if ($this->confirmingDeletionId) {
            $customer = Customer::findOrFail($this->confirmingDeletionId);

            // Tenant security check
            if (!auth()->user()->isSuperAdmin() && $customer->marquee_id !== auth()->user()->marquee_id) {
                session()->flash('error', 'Unauthorized operation.');
                $this->confirmingDeletionId = null;
                return;
            }

            $customer->delete();
            $this->confirmingDeletionId = null;
            session()->flash('success', 'Customer deleted successfully.');
        }
    }

    /**
     * Render the component.
     */
    public function render()
    {
        $query = Customer::query();

        // Apply Search
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('first_name', 'like', '%' . $this->search . '%')
                  ->orWhere('last_name', 'like', '%' . $this->search . '%')
                  ->orWhere('customer_code', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('phone_number', 'like', '%' . $this->search . '%')
                  ->orWhere('cnic_national_id', 'like', '%' . $this->search . '%')
                  ->orWhere('company_name', 'like', '%' . $this->search . '%')
                  ->orWhere('referred_by_name', 'like', '%' . $this->search . '%')
                  ->orWhere('referred_by_contact', 'like', '%' . $this->search . '%');
            });
        }

        // Apply Type Filter
        if (!empty($this->filterType)) {
            $query->where('customer_type', $this->filterType);
        }

        // Apply Status Filter
        if (!empty($this->filterStatus)) {
            $query->where('status', $this->filterStatus);
        }

        $customers = $query->withCount('bookings')->latest()->paginate(10);

        return view('livewire.customer-list', compact('customers'));
    }
}
