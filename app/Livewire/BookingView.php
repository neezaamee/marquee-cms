<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\BookingHistory;
use Livewire\Component;

class BookingView extends Component
{
    public Booking $booking;
    
    // Quick Payment Record Modal State
    public $showPaymentModal = false;
    public $amountPaid = 0.00;
    public $paymentDate = '';
    public $paymentMethod = 'Cash';
    public $transactionReference = '';
    public $paymentNote = '';

    // Deposit Processing Modal State
    public $showDepositModal = false;
    public $depositAction = 'refund_full';
    public $depositRefundedAmount = 0.00;
    public $depositDeductedAmount = 0.00;
    public $depositNotes = '';

    public function mount(Booking $booking)
    {
        $this->booking = $booking;
        $this->paymentDate = date('Y-m-d');
    }

    /**
     * Record a payment transaction and update payment status.
     */
    public function recordPayment()
    {
        $this->validate([
            'amountPaid' => 'required|numeric|min:1',
            'paymentDate' => 'required|date',
            'paymentMethod' => 'required|string',
            'transactionReference' => 'nullable|string|max:255',
            'paymentNote' => 'nullable|string|max:255',
        ]);

        $oldPaymentStatus = $this->booking->payment_status;
        
        // 1. Create payment transaction record
        \App\Models\BookingPayment::create([
            'booking_id' => $this->booking->id,
            'amount' => floatval($this->amountPaid),
            'payment_date' => $this->paymentDate,
            'payment_method' => $this->paymentMethod,
            'transaction_reference' => $this->transactionReference ?: null,
            'recorded_by' => auth()->id(),
            'notes' => $this->paymentNote ?: null,
        ]);

        // 2. Sum all payments to update status
        $totalPaid = $this->booking->payments()->sum('amount');
        
        $newPaymentStatus = 'Unpaid';
        if ($totalPaid > 0) {
            if ($totalPaid >= $this->booking->grand_total) {
                $newPaymentStatus = 'Paid';
            } else {
                $newPaymentStatus = 'Partially Paid';
            }
        }

        $this->booking->update([
            'payment_status' => $newPaymentStatus
        ]);

        // Add history log
        BookingHistory::create([
            'booking_id' => $this->booking->id,
            'user_id' => auth()->id(),
            'status_from' => $this->booking->booking_status,
            'status_to' => $this->booking->booking_status,
            'payment_status_from' => $oldPaymentStatus,
            'payment_status_to' => $newPaymentStatus,
            'notes' => 'Recorded ' . $this->paymentMethod . ' payment of Rs. ' . number_format($this->amountPaid, 2) . '. ' . $this->paymentNote,
        ]);

        $this->booking->refresh();
        $this->amountPaid = 0.00;
        $this->paymentNote = '';
        $this->transactionReference = '';
        $this->paymentDate = date('Y-m-d');
        $this->showPaymentModal = false;

        session()->flash('success', 'Payment recorded successfully in transactions ledger.');
    }

    /**
     * Process refundable security deposit.
     */
    public function processDeposit()
    {
        if ($this->depositAction === 'refund_full') {
            $this->depositRefundedAmount = $this->booking->security_deposit;
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
            if (abs($sum - $this->booking->security_deposit) > 0.01) {
                $this->addError('depositSum', 'Refunded + Deducted amount must equal security deposit (Rs. ' . number_format($this->booking->security_deposit, 2) . ').');
                return;
            }

            $status = floatval($this->depositDeductedAmount) > 0 ? 'Deducted' : 'Refunded';
        }

        $this->booking->update([
            'deposit_status' => $status,
            'deposit_refunded_amount' => $this->depositRefundedAmount,
            'deposit_deducted_amount' => $this->depositDeductedAmount,
            'deposit_notes' => $this->depositNotes ?: null,
        ]);

        BookingHistory::create([
            'booking_id' => $this->booking->id,
            'user_id' => auth()->id(),
            'status_from' => $this->booking->booking_status,
            'status_to' => $this->booking->booking_status,
            'notes' => 'Security deposit processed: ' . $status . '. Refunded: Rs. ' . number_format($this->depositRefundedAmount, 2) . ', Deducted: Rs. ' . number_format($this->depositDeductedAmount, 2) . '. Notes: ' . $this->depositNotes,
        ]);

        $this->booking->refresh();
        $this->showDepositModal = false;
        $this->depositNotes = '';
        $this->depositRefundedAmount = 0.00;
        $this->depositDeductedAmount = 0.00;

        session()->flash('success', 'Refundable security deposit updated.');
    }

    /**
     * Transition booking status. Only owners/superadmins can unlock completed/cancelled events.
     */
    public function updateStatus($newStatus)
    {
        if (!in_array($newStatus, ['Draft', 'Reserved', 'Confirmed', 'Completed', 'Cancelled', 'Rejected'])) {
            return;
        }

        $user = auth()->user();
        $isOwner = $user->role && in_array($user->role->name, ['owner', 'super_admin']);

        if ($this->booking->booking_status === 'Completed' && !$isOwner) {
            session()->flash('error', 'Completed bookings cannot be changed to another status.');
            return;
        }
        $isLocked = $this->booking->booking_status === 'Cancelled';

        if ($isLocked && !$isOwner) {
            session()->flash('error', 'Only owners or super admins can unlock or change status of Cancelled bookings.');
            return;
        }

        $oldStatus = $this->booking->booking_status;

        $this->booking->update([
            'booking_status' => $newStatus
        ]);

        BookingHistory::create([
            'booking_id' => $this->booking->id,
            'user_id' => auth()->id(),
            'status_from' => $oldStatus,
            'status_to' => $newStatus,
            'notes' => 'Booking status manually transitioned to ' . $newStatus,
        ]);

        $this->booking->refresh();
        session()->flash('success', 'Booking status updated to ' . $newStatus);
    }

    public function render()
    {
        $histories = $this->booking->histories()->with('user')->get();

        return view('livewire.booking-view', [
            'histories' => $histories
        ]);
    }
}
