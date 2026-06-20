<?php

namespace App\Livewire;

use App\Models\BillingCycle;
use Livewire\Component;
use Livewire\WithPagination;

class BillingCyclesList extends Component
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

    public function confirmDeletion(int $id)
    {
        $this->confirmingDeletionId = $id;
    }

    public function deleteRecord()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        if ($this->confirmingDeletionId) {
            $cycle = BillingCycle::findOrFail($this->confirmingDeletionId);
            $cycle->delete();
            $this->confirmingDeletionId = null;
            session()->flash('success', 'Billing cycle deleted successfully.');
        }
    }

    public function render()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $query = BillingCycle::query();

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('cycle_name', 'like', '%' . $this->search . '%');
            });
        }

        $cycles = $query->paginate(10);

        return view('livewire.billing-cycles-list', compact('cycles'));
    }
}
