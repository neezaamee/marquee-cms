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

            // Check if branch has active bookings
            if (\App\Models\Booking::where('branch_id', $branch->id)->exists()) {
                session()->flash('error', 'Cannot delete this branch because it contains historical or active bookings. Deactivate the branch instead.');
                $this->confirmingDeletionId = null;
                return;
            }

            // Check if branch has halls
            if (\App\Models\Hall::where('branch_id', $branch->id)->exists()) {
                session()->flash('error', 'Cannot delete this branch because it has halls assigned. Please remove or reassign the halls first.');
                $this->confirmingDeletionId = null;
                return;
            }

            // Check if branch has departments
            if (\App\Models\Department::where('branch_id', $branch->id)->exists()) {
                session()->flash('error', 'Cannot delete this branch because departments are registered under it.');
                $this->confirmingDeletionId = null;
                return;
            }

            // Check if branch has assigned users or employees
            if (\App\Models\User::where('branch_id', $branch->id)->exists() || \App\Models\Employee::where('branch_id', $branch->id)->exists()) {
                session()->flash('error', 'Cannot delete this branch because staff or employees are assigned to it.');
                $this->confirmingDeletionId = null;
                return;
            }

            // Check if it is the only head office
            if ($branch->is_head_office && Branch::where('marquee_id', $branch->marquee_id)->where('is_head_office', true)->count() <= 1 && Branch::where('marquee_id', $branch->marquee_id)->count() > 1) {
                session()->flash('error', 'Cannot delete the Main Branch / Head Office. Please designate another branch as the Main Branch first.');
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
        $user = auth()->user();
        abort_unless($user->isSuperAdmin() || $user->hasPermission('manage_settings'), 403);

        $query = Branch::with('marquee')->withCount('halls');

        if (!$user->isSuperAdmin()) {
            $query->where('marquee_id', $user->getActiveMarqueeId());
        }

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
