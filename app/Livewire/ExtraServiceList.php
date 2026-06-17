<?php

namespace App\Livewire;

use App\Models\ExtraService;
use Livewire\Component;
use Livewire\WithPagination;

class ExtraServiceList extends Component
{
    use WithPagination;

    // Search and filter fields
    public $search = '';
    public $filterStatus = '';

    protected $paginationTheme = 'bootstrap';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => ''],
    ];

    public function updatingSearch() { $this->resetPage(); }
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
     * Delete the confirmed extra service.
     */
    public function deleteRecord()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_settings'), 403);

        if ($this->confirmingDeletionId) {
            $extraService = ExtraService::findOrFail($this->confirmingDeletionId);

            // Scope Check
            if (!auth()->user()->isSuperAdmin() && $extraService->marquee_id !== auth()->user()->marquee_id) {
                session()->flash('error', 'Unauthorized operation.');
                $this->confirmingDeletionId = null;
                return;
            }

            $extraService->delete();
            $this->confirmingDeletionId = null;
            session()->flash('success', 'Add-on deleted successfully.');
        }
    }

    public function render()
    {
        $query = ExtraService::query();

        // Real-time search
        if (!empty($this->search)) {
            $query->where('service_name', 'like', '%' . $this->search . '%');
        }

        // Status filter
        if (!empty($this->filterStatus)) {
            $query->where('status', $this->filterStatus);
        }

        $extraServices = $query->orderBy('service_name', 'asc')
            ->paginate(15);

        return view('livewire.extra-service-list', compact('extraServices'));
    }
}
