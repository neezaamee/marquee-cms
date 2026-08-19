<?php

namespace App\Livewire;

use App\Models\Marquee;
use Livewire\Component;
use Livewire\WithPagination;

class MarqueeList extends Component
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
     * Delete the confirmed marquee.
     */
    public function deleteRecord()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        if ($this->confirmingDeletionId) {
            $marquee = Marquee::findOrFail($this->confirmingDeletionId);
            $marquee->delete();
            $this->confirmingDeletionId = null;
            session()->flash('success', 'Marquee tenant deleted successfully.');
        }
    }

    public function render()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $query = Marquee::with(['owners.subscriptionPlan']);

        // Apply filters
        if ($this->filter === 'active') {
            $query->where('status', 'active')
                  ->whereHas('owners', function($q) {
                      $q->where(function($sq) {
                          $sq->whereNull('subscription_ends_at')
                            ->orWhere('subscription_ends_at', '>=', now()->toDateString());
                      });
                  });
        } elseif ($this->filter === 'suspended') {
            $query->where(function($q) {
                $q->whereIn('status', ['suspended', 'inactive'])
                  ->orWhereDoesntHave('owners')
                  ->orWhereHas('owners', function($sq) {
                      $sq->whereNotNull('subscription_ends_at')
                        ->where('subscription_ends_at', '<', now()->toDateString());
                  });
            });
        }

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('city', 'like', '%' . $this->search . '%')
                  ->orWhere('phone', 'like', '%' . $this->search . '%');
            });
        }

        $marquees = $query->paginate(10);

        return view('livewire.marquee-list', compact('marquees'));
    }
}
