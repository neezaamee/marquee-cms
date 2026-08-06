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
     * Renders a printable slip layout (V3) for a booking.
     */
    public function slipV3(Booking $booking)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_bookings'), 403);

        // Tenant scoping security check
        if (!auth()->user()->isSuperAdmin() && $booking->marquee_id !== auth()->user()->marquee_id) {
            abort(403, 'Unauthorized access to this booking.');
        }

        return view('bookings.slip_v3', compact('booking'));
    }

    /**
     * Generates and downloads a PDF of the booking slip/invoice using DomPDF.
     */
    public function downloadPdf(Booking $booking)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_bookings'), 403);

        // Tenant scoping security check
        if (!auth()->user()->isSuperAdmin() && $booking->marquee_id !== auth()->user()->marquee_id) {
            abort(403, 'Unauthorized access to this booking.');
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('bookings.pdf', compact('booking'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream('Booking_Invoice_' . $booking->booking_number . '.pdf');
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

    /**
     * Display a printable and filterable report of bookings.
     */
    public function report(Request $request)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_bookings'), 403);

        $query = Booking::with(['customer', 'hall', 'slot', 'package', 'payments', 'eventType']);

        if ($request->filled('search')) {
            $searchTerm = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('booking_number', 'like', $searchTerm)
                  ->orWhereHas('customer', function ($cq) use ($searchTerm) {
                      $cq->where('first_name', 'like', $searchTerm)
                        ->orWhere('last_name', 'like', $searchTerm)
                        ->orWhere('phone_number', 'like', $searchTerm);
                  });
            });
        }

        if ($request->filled('filterStatus')) {
            $query->where('booking_status', $request->input('filterStatus'));
        }

        if ($request->filled('filterPaymentStatus')) {
            $query->where('payment_status', $request->input('filterPaymentStatus'));
        }

        if ($request->filled('filterHall')) {
            $query->where('hall_id', $request->input('filterHall'));
        }

        if ($request->filled('filterDateStart')) {
            $query->where('booking_date', '>=', $request->input('filterDateStart'));
        }

        if ($request->filled('filterDateEnd')) {
            $query->where('booking_date', '<=', $request->input('filterDateEnd'));
        }

        $bookings = $query->orderBy('booking_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $marquee = auth()->user()->marquee;

        return view('bookings.report', compact('bookings', 'marquee'));
    }
}

