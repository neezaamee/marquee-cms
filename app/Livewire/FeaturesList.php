<?php

namespace App\Livewire;

use App\Models\PlanFeature;
use Livewire\Component;
use Livewire\WithPagination;

class FeaturesList extends Component
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
            $feature = PlanFeature::findOrFail($this->confirmingDeletionId);
            $feature->delete();
            $this->confirmingDeletionId = null;
            session()->flash('success', 'Plan feature deleted successfully.');
        }
    }

    public function render()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $query = PlanFeature::query();

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('feature_name', 'like', '%' . $this->search . '%')
                  ->orWhere('feature_key', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        $features = $query->paginate(10);

        return view('livewire.features-list', compact('features'));
    }
}
