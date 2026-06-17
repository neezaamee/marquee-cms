<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class UserList extends Component
{
    use WithPagination;

    public $search = '';
    public $confirmingDeletionId = null;

    protected $paginationTheme = 'bootstrap';

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function updatingSearch()
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
     * Delete the confirmed user.
     */
    public function deleteRecord()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_staff'), 403);

        if ($this->confirmingDeletionId) {
            // Prevent self-deletion
            if ($this->confirmingDeletionId === auth()->id()) {
                session()->flash('error', 'You cannot delete your own account.');
                $this->confirmingDeletionId = null;
                return;
            }

            $user = User::findOrFail($this->confirmingDeletionId);

            // Tenant security check
            if (!auth()->user()->isSuperAdmin() && $user->marquee_id !== auth()->user()->marquee_id) {
                session()->flash('error', 'Unauthorized operation.');
                $this->confirmingDeletionId = null;
                return;
            }

            $user->delete();
            $this->confirmingDeletionId = null;
            session()->flash('success', 'User deleted successfully.');
        }
    }

    public function render()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_staff'), 403);

        $query = User::with(['role', 'branch', 'marquee']);

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('username', 'like', '%' . $this->search . '%')
                  ->orWhere('phone', 'like', '%' . $this->search . '%');
            });
        }

        $users = $query->paginate(10);

        return view('livewire.user-list', compact('users'));
    }
}
