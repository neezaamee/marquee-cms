<?php

namespace App\Livewire\Owner;

use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Hall;
use App\Models\InventoryItem;
use App\Models\Marquee;
use Carbon\Carbon;
use Livewire\Component;

class BusinessOwnerDashboard extends Component
{
    public ?int $selectedBranchId = null; // null = All Branches
    public string $timeframe = 'month'; // 'today', 'week', 'month', 'year'

    public function updatedSelectedBranchId()
    {
        // Reactive refresh
    }

    public function updatedTimeframe()
    {
        // Reactive refresh
    }

    public function render()
    {
        $user = auth()->user();
        $marqueeId = $user->getActiveMarqueeId();

        $marquee = $marqueeId ? Marquee::with('branches')->find($marqueeId) : null;
        $branches = $marquee ? $marquee->branches : collect();

        // 1. Date filter range
        $now = Carbon::now();
        $dateRange = match ($this->timeframe) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()], // 'month'
        };

        // 2. Financial Metrics
        $bookingQuery = Booking::where('marquee_id', $marqueeId);
        if ($this->selectedBranchId) {
            $bookingQuery->where('branch_id', $this->selectedBranchId);
        }

        // Realized Revenue (from recognized completed events)
        $realizedRevenue = (float) (clone $bookingQuery)
            ->where('is_revenue_recognized', true)
            ->whereBetween('booking_date', [$dateRange[0]->format('Y-m-d'), $dateRange[1]->format('Y-m-d')])
            ->sum('revenue_recognized');

        // Customer Advances Held (Liability - not yet recognized)
        $customerAdvanceHeld = (float) (clone $bookingQuery)
            ->where('is_revenue_recognized', false)
            ->sum('advance_received');

        // Pending Receivables (Outstanding customer balances)
        $pendingReceivables = (float) (clone $bookingQuery)
            ->where('receivable_amount', '>', 0)
            ->sum('receivable_amount');

        // Operating Expenses
        $expenseQuery = Expense::where('marquee_id', $marqueeId)
            ->where('status', 'Approved');
        if ($this->selectedBranchId) {
            $expenseQuery->where('branch_id', $this->selectedBranchId);
        }
        $operatingExpenses = (float) $expenseQuery
            ->whereBetween('expense_date', [$dateRange[0]->format('Y-m-d'), $dateRange[1]->format('Y-m-d')])
            ->sum('total_amount');

        // Net Operating Cashflow / Margin
        $netOperatingCashflow = $realizedRevenue - $operatingExpenses;

        // 3. Operational Counts
        $totalBookings = (clone $bookingQuery)->count();
        $confirmedBookings = (clone $bookingQuery)->where('booking_status', 'Confirmed')->count();
        $completedBookings = (clone $bookingQuery)->where('booking_status', 'Completed')->count();
        $draftBookings = (clone $bookingQuery)->where('booking_status', 'Draft')->count();

        $activeHallsCount = Hall::where('marquee_id', $marqueeId)
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->where('status', 'active')
            ->count();

        $staffCount = Employee::where('marquee_id', $marqueeId)
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->where('status', 'Active')
            ->count();

        // 4. Today's Live Functions
        $todayEvents = (clone $bookingQuery)
            ->with(['customer', 'hall', 'eventType', 'slot', 'branch'])
            ->whereDate('booking_date', Carbon::today()->format('Y-m-d'))
            ->whereIn('booking_status', ['Confirmed', 'Reserved', 'Completed'])
            ->orderBy('start_time', 'asc')
            ->get();

        // 5. Upcoming 7-Day Event Pipeline
        $upcomingEvents = (clone $bookingQuery)
            ->with(['customer', 'hall', 'eventType', 'slot', 'branch'])
            ->whereBetween('booking_date', [
                Carbon::tomorrow()->format('Y-m-d'),
                Carbon::today()->addDays(7)->format('Y-m-d')
            ])
            ->whereIn('booking_status', ['Confirmed', 'Reserved'])
            ->orderBy('booking_date', 'asc')
            ->take(6)
            ->get();

        // 6. Operational Safeguards & Alerts
        $lowStockItems = InventoryItem::where('marquee_id', $marqueeId)
            ->with('unit')
            ->where(function ($q) {
                $q->whereColumn('current_stock', '<=', 'min_stock_level')
                  ->orWhere('current_stock', '<=', 5);
            })
            ->take(5)
            ->get();

        $overdueReceivablesCount = (clone $bookingQuery)
            ->where('booking_date', '<', Carbon::today()->format('Y-m-d'))
            ->where('receivable_amount', '>', 0)
            ->count();

        $unprintedKitchenSlipsCount = (clone $bookingQuery)
            ->whereDate('booking_date', Carbon::today()->format('Y-m-d'))
            ->whereNull('kitchen_printed_at')
            ->count();

        return view('livewire.owner.business-owner-dashboard', [
            'marquee' => $marquee,
            'branches' => $branches,
            'realizedRevenue' => $realizedRevenue,
            'customerAdvanceHeld' => $customerAdvanceHeld,
            'pendingReceivables' => $pendingReceivables,
            'operatingExpenses' => $operatingExpenses,
            'netOperatingCashflow' => $netOperatingCashflow,
            'totalBookings' => $totalBookings,
            'confirmedBookings' => $confirmedBookings,
            'completedBookings' => $completedBookings,
            'draftBookings' => $draftBookings,
            'activeHallsCount' => $activeHallsCount,
            'staffCount' => $staffCount,
            'todayEvents' => $todayEvents,
            'upcomingEvents' => $upcomingEvents,
            'lowStockItems' => $lowStockItems,
            'overdueReceivablesCount' => $overdueReceivablesCount,
            'unprintedKitchenSlipsCount' => $unprintedKitchenSlipsCount,
        ]);
    }
}
