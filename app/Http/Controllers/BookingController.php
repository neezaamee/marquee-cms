<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    /**
     * Display a listing of bookings.
     */
    public function index()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_bookings'), 403);
        return view('bookings.index');
    }

    /**
     * Show the form for creating a new booking (wizard).
     */
    public function create()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('create_bookings'), 403);
        return view('bookings.create');
    }

    /**
     * Display the specified booking details.
     */
    public function show(Booking $booking)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_bookings'), 403);

        // Tenant scoping security check
        if (!auth()->user()->isSuperAdmin() && $booking->marquee_id !== auth()->user()->marquee_id) {
            abort(403, 'Unauthorized access to this booking.');
        }

        return view('bookings.show', compact('booking'));
    }

    /**
     * Show the form for editing the specified booking.
     */
    public function edit(Booking $booking)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('edit_bookings'), 403);

        // Tenant scoping security check
        if (!auth()->user()->isSuperAdmin() && $booking->marquee_id !== auth()->user()->marquee_id) {
            abort(403, 'Unauthorized access to this booking.');
        }

        return view('bookings.edit', compact('booking'));
    }

    /**
     * Renders a printable slip layout for a booking.
     */
    public function slip(Booking $booking)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_bookings'), 403);

        // Tenant scoping security check
        if (!auth()->user()->isSuperAdmin() && $booking->marquee_id !== auth()->user()->marquee_id) {
            abort(403, 'Unauthorized access to this booking.');
        }

        return view('bookings.slip', compact('booking'));
    }

    /**
     * Renders a printable slip layout (V2) for a booking.
     */
    public function slipV2(Booking $booking)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_bookings'), 403);

        // Tenant scoping security check
        if (!auth()->user()->isSuperAdmin() && $booking->marquee_id !== auth()->user()->marquee_id) {
            abort(403, 'Unauthorized access to this booking.');
        }

        return view('bookings.slip_v2', compact('booking'));
    }

    /**
     * Renders a printable payment receipt for a specific payment.
     */
    public function paymentReceipt(\App\Models\BookingPayment $payment)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_bookings'), 403);

        $booking = $payment->booking;

        // Tenant scoping security check
        if (!auth()->user()->isSuperAdmin() && $booking->marquee_id !== auth()->user()->marquee_id) {
            abort(403, 'Unauthorized access to this payment receipt.');
        }

        return view('bookings.receipt', compact('payment', 'booking'));
    }
}

