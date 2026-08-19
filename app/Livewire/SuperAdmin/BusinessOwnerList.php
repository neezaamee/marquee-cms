<?php

namespace App\Livewire\SuperAdmin;

use App\Models\User;
use App\Models\Role;
use Livewire\Component;
use Livewire\WithPagination;

class BusinessOwnerList extends Component
{
    use WithPagination;

    public $search = '';
    public $filter = '';
    public $confirmingDeletionId = null;

    protected $paginationTheme = 'bootstrap';

    protected $queryString = [
        'search' => ['except' => ''],
        'filter' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilter()
    {
        $this->resetPage();
    }

    /**
     * Set the record ID for deletion confirmation.
     */
    public function confirmDeletion(int $id)
    {
        $this->confirmingDeletionId = $id;
    }

    /**
     * Delete the confirmed business owner user.
     */
    public function deleteRecord()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        if ($this->confirmingDeletionId) {
            $user = User::findOrFail($this->confirmingDeletionId);
            
            // Delete pivot owner connections
            $user->ownedMarquees()->detach();
            
            // Delete user profile
            $user->delete();
            
            $this->confirmingDeletionId = null;
            session()->flash('success', 'Business Owner deleted successfully.');
        }
    }

    public function render()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        // Fetch owner and business_owner roles
        $roleIds = Role::whereIn('name', ['owner', 'business_owner'])->pluck('id');

        $query = User::whereIn('role_id', $roleIds)
            ->with(['subscriptionPlan', 'ownedMarquees']);

        // Apply subscription filters
        if ($this->filter === 'active') {
            $query->where(function ($q) {
                $q->whereNull('subscription_ends_at')
                  ->orWhere('subscription_ends_at', '>=', now()->toDateString());
            });
        } elseif ($this->filter === 'expired') {
            $query->whereNotNull('subscription_ends_at')
                  ->where('subscription_ends_at', '<', now()->toDateString());
        }

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('username', 'like', '%' . $this->search . '%')
                  ->orWhere('phone', 'like', '%' . $this->search . '%');
            });
        }

        $businessOwners = $query->paginate(10);

        return view('livewire.super-admin.business-owner-list', compact('businessOwners'))
            ->layout('layouts.admin');
    }
}
