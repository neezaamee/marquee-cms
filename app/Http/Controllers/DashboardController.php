<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Hall;
use App\Models\Package;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Show the application dashboard.
     */
    public function index()
    {
        $user = auth()->user();
        $marqueeId = $user->marquee_id;

        // Onboarding checklist status calculation
        $setupChecklist = [];
        $isSetupCompleted = true;

        if (!$user->isSuperAdmin()) {
            if ($marqueeId && $user->marquee) {
                $marquee = $user->marquee;
                $isSetupCompleted = (bool) $marquee->is_setup_completed;

                if (!$isSetupCompleted) {
                    $setupChecklist = [
                        'marquee_info' => !empty($marquee->business_type),
                        'branch' => \App\Models\Branch::where('marquee_id', $marqueeId)->exists(),
                        'hall' => \App\Models\Hall::where('marquee_id', $marqueeId)->exists(),
                        'financial_year' => \App\Models\FinancialYear::where('marquee_id', $marqueeId)->exists(),
                        'event_types' => \App\Models\EventType::where('marquee_id', $marqueeId)->exists(),
                    ];
                    
                    if (!in_array(false, $setupChecklist, true)) {
                        $marquee->update(['is_setup_completed' => true]);
                        $isSetupCompleted = true;
                    }
                }
            } else {
                $isSetupCompleted = false;
                $setupChecklist = [
                    'marquee_info' => false,
                    'branch' => false,
                    'hall' => false,
                    'financial_year' => false,
                    'event_types' => false,
                ];
            }
        }

        // Scoping is automatically handled by the BelongsToTenant global scope
        $totalBookings = Booking::count();
        $activeHalls = Hall::where('status', 'active')->count();
        $menuPackages = Package::where('status', 'Active')->count();

        // Calculate Monthly Revenue based on payment recorded date
        $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
        $endOfMonth = Carbon::now()->endOfMonth()->toDateString();
        $monthlyRevenue = BookingPayment::whereHas('booking')
            ->whereBetween('payment_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        // Fetch next 5 upcoming bookings
        $recentBookings = Booking::with(['customer', 'hall', 'eventType', 'slot'])
            ->whereDate('booking_date', '>=', Carbon::today()->toDateString())
            ->whereIn('booking_status', ['Reserved', 'Confirmed'])
            ->orderBy('booking_date', 'asc')
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'totalBookings',
            'activeHalls',
            'menuPackages',
            'monthlyRevenue',
            'recentBookings',
            'isSetupCompleted',
            'setupChecklist'
        ));
    }
}
