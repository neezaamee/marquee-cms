<?php

namespace App\Services;
namespace App\Livewire\Finance;

use App\Models\Booking;
use App\Models\Branch;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerAdvanceLiabilityReport extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $branchId = 'all';
    public $search = '';
    public $dateFrom = '';
    public $dateTo = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingBranchId()
    {
        $this->resetPage();
    }

    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;

        $branches = Branch::where('marquee_id', $marqueeId)->orderBy('name')->get();

        $query = Booking::where('bookings.marquee_id', $marqueeId)
            ->where('bookings.is_revenue_recognized', false)
            ->where('bookings.advance_received', '>', 0)
            ->whereNotIn('bookings.booking_status', ['Cancelled', 'Rejected'])
            ->with(['customer', 'branch', 'hall', 'eventType', 'payments.account']);

        if ($this->branchId !== 'all' && $this->branchId !== '') {
            $query->where('bookings.branch_id', $this->branchId);
        }

        if ($this->search) {
            $s = '%' . trim($this->search) . '%';
            $query->where(function($q) use ($s) {
                $q->where('bookings.booking_number', 'like', $s)
                  ->orWhereHas('customer', function($cq) use ($s) {
                      $cq->where('first_name', 'like', $s)
                         ->orWhere('last_name', 'like', $s)
                         ->orWhere('phone_number', 'like', $s)
                         ->orWhere('customer_code', 'like', $s);
                  });
            });
        }

        if ($this->dateFrom) {
            $query->where('bookings.booking_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->where('bookings.booking_date', '<=', $this->dateTo);
        }

        $totalLiabilitySum = (clone $query)->sum('advance_received');
        $totalBookingValueSum = (clone $query)->sum('grand_total');
        $activeAdvancesCount = (clone $query)->count();

        $bookings = $query->orderBy('bookings.booking_date', 'asc')->paginate(15);

        return view('livewire.finance.customer-advance-liability-report', [
            'branches' => $branches,
            'bookings' => $bookings,
            'totalLiabilitySum' => (float) $totalLiabilitySum,
            'totalBookingValueSum' => (float) $totalBookingValueSum,
            'activeAdvancesCount' => $activeAdvancesCount,
        ]);
    }
}
