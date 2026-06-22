<?php

namespace App\Livewire\Finance;

use App\Models\BookingPayment;
use Livewire\Component;
use Livewire\WithPagination;

class PaymentsList extends Component
{
    use WithPagination;

    public $search = '';
    public $paymentMethod = '';
    public $startDate = '';
    public $endDate = '';

    protected $paginationTheme = 'bootstrap';

    public function updatingSearch() { $this->resetPage(); }
    public function updatingPaymentMethod() { $this->resetPage(); }
    public function updatingStartDate() { $this->resetPage(); }
    public function updatingEndDate() { $this->resetPage(); }

    public function render()
    {
        // Query only payments belonging to the tenant's bookings
        $query = BookingPayment::whereHas('booking')
            ->with(['booking.customer', 'recorder']);

        // Apply search query
        if (!empty($this->search)) {
            $term = '%' . $this->search . '%';
            $query->where(function($q) use ($term) {
                $q->where('transaction_reference', 'like', $term)
                  ->orWhere('notes', 'like', $term)
                  ->orWhereHas('booking', function($bq) use ($term) {
                      $bq->where('booking_number', 'like', $term)
                         ->orWhereHas('customer', function($cq) use ($term) {
                             $cq->where('first_name', 'like', $term)
                                ->orWhere('last_name', 'like', $term);
                         });
                  });
            });
        }

        // Apply method filter
        if (!empty($this->paymentMethod)) {
            $query->where('payment_method', $this->paymentMethod);
        }

        // Apply dates
        if (!empty($this->startDate)) {
            $query->where('payment_date', '>=', $this->startDate);
        }
        if (!empty($this->endDate)) {
            $query->where('payment_date', '<=', $this->endDate);
        }

        $payments = $query->orderBy('payment_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('livewire.finance.payments-list', [
            'payments' => $payments
        ]);
    }
}
