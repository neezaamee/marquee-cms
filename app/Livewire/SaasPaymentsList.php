<?php

namespace App\Livewire;

use App\Models\SaasPayment;
use Livewire\Component;
use Livewire\WithPagination;

class SaasPaymentsList extends Component
{
    use WithPagination;

    public $search = '';
    public $filterMethod = '';

    protected $paginationTheme = 'bootstrap';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterMethod' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterMethod()
    {
        $this->resetPage();
    }

    public function render()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $query = SaasPayment::with(['invoice', 'marquee']);

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('payment_reference', 'like', '%' . $this->search . '%')
                  ->orWhere('transaction_id', 'like', '%' . $this->search . '%')
                  ->orWhereHas('marquee', function($mq) {
                      $mq->where('name', 'like', '%' . $this->search . '%');
                  })
                  ->orWhereHas('invoice', function($inv) {
                      $inv->where('invoice_number', 'like', '%' . $this->search . '%');
                  });
            });
        }

        if (!empty($this->filterMethod)) {
            $query->where('payment_method', $this->filterMethod);
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('livewire.saas-payments-list', compact('payments'));
    }
}
