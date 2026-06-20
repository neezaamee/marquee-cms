<?php

namespace App\Livewire;

use App\Models\Booking;
use Livewire\Component;

class BookingSlipV2 extends Component
{
    public Booking $booking;

    public function mount(Booking $booking)
    {
        $this->booking = $booking->load(['customer', 'hall', 'slot', 'package', 'eventType', 'extraServices', 'menuItems']);
    }

    public function render()
    {
        return view('livewire.booking-slip-v2');
    }
}
