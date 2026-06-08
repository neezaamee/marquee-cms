<?php

namespace App\Livewire;

use App\Models\Booking;
use Livewire\Component;

class BookingSlip extends Component
{
    public Booking $booking;

    public function mount(Booking $booking)
    {
        $this->booking = $booking->load(['customer', 'hall', 'slot', 'package', 'eventType']);
    }

    public function render()
    {
        return view('livewire.booking-slip');
    }
}
