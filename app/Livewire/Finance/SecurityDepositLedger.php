<?php

namespace App\Livewire\Finance;

use App\Models\Booking;
use App\Models\BookingHistory;
use Livewire\Component;
use Livewire\WithPagination;

class SecurityDepositLedger extends Component
{
    use WithPagination;

    // Filters
    public $search = '';
    public $statusFilter = ''; // '', 'Held', 'Refunded', 'Deducted'

    // Modal State
    public $showModal = false;
    public $selectedBookingId = null;
    public $depositAction = 'refund_full';
    public $depositRefundedAmount = 0.00;
    public $depositDeductedAmount = 0.00;
    public $depositNotes = '';

    protected $paginationTheme = 'bootstrap';

    public function updatingSearch() { $this->resetPage(); }
    public function updatingStatusFilter() { $this->resetPage(); }

    /**
     * Trigger modal to process deposit release/deduction.
     */
    public function openDepositModal($bookingId)
    {
        $booking = Booking::findOrFail($bookingId);
        
        $this->selectedBookingId = $booking->id;
        $this->depositAction = 'refund_full';
        $this->depositRefundedAmount = $booking->security_deposit;
        $this->depositDeductedAmount = 0.00;
        $this->depositNotes = '';
        
        $this->showModal = true;
    }

    /**
     * Close processing modal.
     */
    public function closeModal()
    {
        $this->showModal = false;
        $this->selectedBookingId = null;
    }

    /**
     * Handle the updated deposit action dropdown state.
     */
    public function updatedDepositAction($value)
    {
        if ($this->selectedBookingId) {
            $booking = Booking::findOrFail($this->selectedBookingId);
            if ($value === 'refund_full') {
                $this->depositRefundedAmount = $booking->security_deposit;
                $this->depositDeductedAmount = 0.00;
            } else {
                $this->depositRefundedAmount = 0.00;
                $this->depositDeductedAmount = $booking->security_deposit;
            }
        }
    }

    /**
     * Process the security deposit release/deductions.
     */
    public function processDeposit()
    {
        if (!$this->selectedBookingId) return;

        $booking = Booking::findOrFail($this->selectedBookingId);

        if ($this->depositAction === 'refund_full') {
            $this->depositRefundedAmount = $booking->security_deposit;
            $this->depositDeductedAmount = 0.00;
            $status = 'Refunded';
        } else {
            $this->validate([
                'depositRefundedAmount' => 'required|numeric|min:0',
                'depositDeductedAmount' => 'required|numeric|min:0',
                'depositNotes' => 'required|string|min:5',
            ]);

            // Verify totals
            $sum = floatval($this->depositRefundedAmount) + floatval($this->depositDeductedAmount);
            if (abs($sum - $booking->security_deposit) > 0.01) {
                $this->addError('depositSum', 'Refunded + Deducted amount must equal security deposit (Rs. ' . number_format($booking->security_deposit, 2) . ').');
                return;
            }

            $status = floatval($this->depositDeductedAmount) > 0 ? 'Deducted' : 'Refunded';
        }

        // Update database columns
        $booking->update([
            'deposit_status' => $status,
            'deposit_refunded_amount' => $this->depositRefundedAmount,
            'deposit_deducted_amount' => $this->depositDeductedAmount,
            'deposit_notes' => $this->depositNotes ?: null,
        ]);

        // Audit logging
        BookingHistory::create([
            'booking_id' => $booking->id,
            'user_id' => auth()->id(),
            'status_from' => $booking->booking_status,
            'status_to' => $booking->booking_status,
            'notes' => 'Security deposit processed via global ledger: ' . $status . '. Refunded: Rs. ' . number_format($this->depositRefundedAmount, 2) . ', Deducted: Rs. ' . number_format($this->depositDeductedAmount, 2) . '. Notes: ' . $this->depositNotes,
        ]);

        $this->closeModal();
        session()->flash('success', 'Security deposit release processed successfully.');
    }

    public function render()
    {
        // 1. Calculate Summary Totals for Deposits
        // Active Held Deposits
        $heldTotal = Booking::whereNotIn('booking_status', ['Cancelled', 'Rejected'])
            ->where('security_deposit', '>', 0)
            ->where('deposit_status', 'Held')
            ->sum('security_deposit');

        // Total Refunded
        $refundedTotal = Booking::where('security_deposit', '>', 0)
            ->sum('deposit_refunded_amount');

        // Total Deductions (Losses/Damages Auxiliary Income)
        $deductedTotal = Booking::where('security_deposit', '>', 0)
            ->sum('deposit_deducted_amount');

        // 2. Fetch Scoped List
        $query = Booking::where('security_deposit', '>', 0)
            ->with('customer');

        // Apply filters
        if (!empty($this->search)) {
            $term = '%' . $this->search . '%';
            $query->where(function($q) use ($term) {
                $q->where('booking_number', 'like', $term)
                  ->orWhereHas('customer', function($cq) use ($term) {
                      $cq->where('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhere('phone_number', 'like', $term);
                  });
            });
        }

        if (!empty($this->statusFilter)) {
            $query->where('deposit_status', $this->statusFilter);
        }

        $bookings = $query->orderBy('booking_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        // Fetch selected booking object for modal context labels
        $selectedBookingObj = $this->selectedBookingId ? Booking::find($this->selectedBookingId) : null;

        return view('livewire.finance.security-deposit-ledger', [
            'bookings' => $bookings,
            'heldTotal' => $heldTotal,
            'refundedTotal' => $refundedTotal,
            'deductedTotal' => $deductedTotal,
            'selectedBookingObj' => $selectedBookingObj,
        ]);
    }
}
