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
    public $paymentNote = '';

    public function mount(Booking $booking)
    {
        $this->booking = $booking;
    }

    /**
     * Record a quick payment and log it.
     */
    public function recordPayment()
    {
        $this->validate([
            'amountPaid' => 'required|numeric|min:1',
            'paymentNote' => 'nullable|string|max:255',
        ]);

        $oldPaymentStatus = $this->booking->payment_status;
        
        // Calculate status: we just simulate paid status changes
        $newPaymentStatus = 'Partially Paid';
        if ($this->amountPaid >= ($this->booking->grand_total)) {
            $newPaymentStatus = 'Paid';
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
            'notes' => 'Received payment of Rs. ' . number_format($this->amountPaid, 2) . '. ' . $this->paymentNote,
        ]);

        $this->booking->refresh();
        $this->amountPaid = 0.00;
        $this->paymentNote = '';
        $this->showPaymentModal = false;

        session()->flash('success', 'Payment recorded successfully.');
    }

    /**
     * Transition booking status.
     */
    public function updateStatus($newStatus)
    {
        if (!in_array($newStatus, ['Draft', 'Reserved', 'Confirmed', 'Completed', 'Cancelled', 'Rejected'])) {
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
