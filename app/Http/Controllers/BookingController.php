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

    /**
     * Renders a printable Kitchen Menu Slip layout for a booking.
     */
    public function kitchenSlip(Request $request, Booking $booking)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_bookings'), 403);

        // Tenant scoping security check
        if (!auth()->user()->isSuperAdmin() && $booking->marquee_id !== auth()->user()->marquee_id) {
            abort(403, 'Unauthorized access to this kitchen slip.');
        }

        // Language selection (bilingual, english, urdu)
        $lang = $request->input('lang', 'bilingual');
        if (!in_array($lang, ['bilingual', 'english', 'urdu'])) {
            $lang = 'bilingual';
        }

        // Update special kitchen instructions if provided
        if ($request->filled('kitchen_special_instructions')) {
            $booking->kitchen_special_instructions = $request->input('kitchen_special_instructions');
        }

        // Eager load menu items & relations
        $booking->load([
            'menuItems.category.department',
            'halls',
            'hall.branch',
            'customer',
            'eventType',
            'slot',
            'package',
            'kitchenPrintLogs.printer'
        ]);

        // Calculate menu hash and manage versioning
        $currentHash = $booking->computeMenuHash();
        if (empty($booking->kitchen_printed_at) || $booking->kitchen_menu_hash !== $currentHash) {
            $booking->kitchen_print_version = ($booking->kitchen_print_version ?? 0) + 1;
            $booking->kitchen_printed_at = now();
            $booking->kitchen_menu_hash = $currentHash;
            $booking->save();
        }

        // Audit log print history
        \App\Models\KitchenPrintLog::create([
            'booking_id' => $booking->id,
            'marquee_id' => $booking->marquee_id,
            'printed_by' => auth()->id(),
            'language' => $lang,
            'version_number' => $booking->kitchen_print_version,
            'printed_at' => now(),
        ]);

        // Group Menu Items by Department
        $groupedMenuItems = [];
        $deptTranslations = [
            'Pakistani Kitchen' => ['en' => 'Pakistani Kitchen', 'ur' => 'پاکستانی کچن'],
            'BBQ Station' => ['en' => 'BBQ Station & Grill', 'ur' => 'باربی کیو سٹیشن'],
            'Chinese & Continental Kitchen' => ['en' => 'Chinese & Continental', 'ur' => 'چائنیز اور کانٹی نینٹل'],
            'Tandoor & Bakery' => ['en' => 'Tandoor & Breads', 'ur' => 'تندور اور روٹی'],
            'Sweets & Desserts Section' => ['en' => 'Sweets & Desserts', 'ur' => 'میٹھے اور سویٹس'],
            'Housekeeping & Janitorial' => ['en' => 'Housekeeping & Supplies', 'ur' => 'صفائی اور برتن'],
        ];

        foreach ($booking->menuItems as $item) {
            $deptObj = $item->category?->department;
            $deptName = $deptObj?->name;

            // Smart Department Fallback Resolution based on category name
            if (empty($deptName)) {
                $catName = $item->category?->category_name ?? '';
                if (stripos($catName, 'BBQ') !== false || stripos($item->item_name, 'Tikka') !== false || stripos($item->item_name, 'Kabab') !== false) {
                    $deptName = 'BBQ Station';
                } elseif (stripos($catName, 'Bread') !== false || stripos($item->item_name, 'Naan') !== false || stripos($item->item_name, 'Roti') !== false) {
                    $deptName = 'Tandoor & Bakery';
                } elseif (stripos($catName, 'Chinese') !== false || stripos($catName, 'Continental') !== false) {
                    $deptName = 'Chinese & Continental Kitchen';
                } elseif (stripos($catName, 'Sweet') !== false || stripos($catName, 'Dessert') !== false || stripos($item->item_name, 'Kheer') !== false || stripos($item->item_name, 'Halwa') !== false) {
                    $deptName = 'Sweets & Desserts Section';
                } elseif (stripos($catName, 'Pakistani') !== false || stripos($catName, 'Main') !== false || stripos($catName, 'Rice') !== false || stripos($item->item_name, 'Biryani') !== false || stripos($item->item_name, 'Karahi') !== false) {
                    $deptName = 'Pakistani Kitchen';
                } else {
                    $deptName = 'General Kitchen / دیگر';
                }
            }

            if (!isset($groupedMenuItems[$deptName])) {
                $groupedMenuItems[$deptName] = [
                    'title_en' => $deptTranslations[$deptName]['en'] ?? $deptName,
                    'title_ur' => $deptTranslations[$deptName]['ur'] ?? '',
                    'items' => [],
                ];
            }

            $groupedMenuItems[$deptName]['items'][] = $item;
        }

        $marquee = auth()->user()->marquee;
        $branch = $booking->hall->branch ?? null;

        return view('bookings.kitchen_slip', compact(
            'booking',
            'groupedMenuItems',
            'lang',
            'marquee',
            'branch'
        ));
    }

    /**
     * Display a calendar view of all bookings.
     */
    public function calendar()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_bookings'), 403);

        $marqueeId = auth()->user()->marquee_id;

        // Retrieve bookings (including soft deleted so cancelled/deleted are visible but color-coded)
        $bookings = Booking::withTrashed()
            ->with(['customer', 'eventType', 'hall'])
            ->where('marquee_id', $marqueeId)
            ->get();

        $events = $bookings->map(function($booking) {
            $customerName = $booking->customer->full_name ?? 'Walk-in / Guest';
            $eventName = $booking->eventType->event_type_name ?? 'Event';
            $hallName = $booking->hall->hall_name ?? 'General Hall';

            $title = "{$customerName} - {$eventName} ({$hallName})";
            if ($booking->trashed()) {
                $title .= ' [DELETED]';
            }

            // Determine class name based on status
            $className = 'bg-secondary-subtle';
            if ($booking->trashed()) {
                $className = 'bg-danger-subtle text-danger border-danger';
            } elseif ($booking->booking_status === 'Confirmed') {
                $className = 'bg-success-subtle text-success border-success';
            } elseif ($booking->booking_status === 'Completed') {
                $className = 'bg-info-subtle text-info border-info';
            } elseif ($booking->booking_status === 'Cancelled') {
                $className = 'bg-danger-subtle text-danger border-danger';
            } elseif (in_array($booking->booking_status, ['Reserved', 'Draft'])) {
                $className = 'bg-warning-subtle text-warning border-warning';
            }

            // Standardize format: YYYY-MM-DDTHH:MM:SS
            $start = $booking->booking_date->format('Y-m-d') . 'T' . $booking->start_time->format('H:i:s');
            $end = $booking->booking_date->format('Y-m-d') . 'T' . $booking->end_time->format('H:i:s');

            return [
                'id' => $booking->id,
                'title' => $title,
                'start' => $start,
                'end' => $end,
                'url' => route('bookings.show', $booking->id),
                'className' => $className,
                'description' => "Booking #: {$booking->booking_number}\nStatus: {$booking->booking_status}\nHall: {$hallName}",
            ];
        });

        return view('bookings.calendar', compact('events'));
    }
}

