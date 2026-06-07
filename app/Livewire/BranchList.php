<?php

namespace App\Livewire;

use App\Models\Branch;
use Livewire\Component;
use Livewire\WithPagination;

class BranchList extends Component
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
     * Delete the confirmed branch.
     */
    public function deleteRecord()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_settings'), 403);

        if ($this->confirmingDeletionId) {
            $branch = Branch::findOrFail($this->confirmingDeletionId);

            // Tenant security check
            if (!auth()->user()->isSuperAdmin() && $branch->marquee_id !== auth()->user()->marquee_id) {
                session()->flash('error', 'Unauthorized operation.');
                $this->confirmingDeletionId = null;
                return;
            }

            $branch->delete();
            $this->confirmingDeletionId = null;
            session()->flash('success', 'Branch deleted successfully.');
        }
    }

    public function render()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_settings'), 403);

        $query = Branch::with('marquee');

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('city', 'like', '%' . $this->search . '%')
                  ->orWhere('phone', 'like', '%' . $this->search . '%');
            });
        }

        $branches = $query->paginate(10);

        return view('livewire.branch-list', compact('branches'));
    }
}
