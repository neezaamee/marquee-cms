<?php

namespace App\Livewire;

use App\Models\SubscriptionPlan;
use Livewire\Component;
use Livewire\WithPagination;

class PlansList extends Component
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
            $plan = SubscriptionPlan::findOrFail($this->confirmingDeletionId);
            $plan->delete();
            $this->confirmingDeletionId = null;
            session()->flash('success', 'Subscription plan deleted successfully.');
        }
    }

    public function render()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $query = SubscriptionPlan::query();

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        $plans = $query->orderBy('sort_order')->orderBy('name')->paginate(10);

        return view('livewire.plans-list', compact('plans'));
    }
}
