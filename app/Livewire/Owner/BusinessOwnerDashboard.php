<?php

namespace App\Livewire\Owner;

use App\Models\Account;
use App\Models\AccountOpeningBalance;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Branch;
use App\Models\CashBankAccount;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Hall;
use App\Models\InventoryItem;
use App\Models\Marquee;
use App\Models\PurchaseInvoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class BusinessOwnerDashboard extends Component
{
    public ?int $selectedBranchId = null; // null = All Branches
    public string $timeframe = 'month'; // 'today', 'week', 'month', 'year'
    public ?string $viewMode = null; // 'executive', 'operations' (for owners to toggle preview)

    public function updatedSelectedBranchId()
    {
        // Reactive refresh
    }

    public function updatedTimeframe()
    {
        // Reactive refresh
    }

    public function setViewMode(?string $mode)
    {
        $this->viewMode = $mode;
    }

    public function render()
    {
        $user = auth()->user();
        $marqueeId = $user->getActiveMarqueeId();

        $marquee = $marqueeId ? Marquee::with('branches')->find($marqueeId) : null;
        $branches = $marquee ? $marquee->branches : collect();

        // Check if user is a booking officer / staff
        $isBookingOfficerRole = $user->isBookingOfficer() || (!$user->isBusinessOwner() && !$user->isSuperAdmin() && !$user->hasRole(['accountant', 'branch_manager']));
        
        // Determine effective view mode (owners can toggle, booking officers are locked to operations)
        $isBookingOfficer = $isBookingOfficerRole || ($this->viewMode === 'operations');
        $canViewFinancials = !$isBookingOfficer && ($user->isBusinessOwner() || $user->isSuperAdmin() || $user->hasRole(['accountant', 'branch_manager']));

        // 1. Date filter range
        $now = Carbon::now();
        $dateRange = match ($this->timeframe) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()], // 'month'
        };
        $startDateStr = $dateRange[0]->format('Y-m-d');
        $endDateStr = $dateRange[1]->format('Y-m-d');

        // 2. Booking Base Query
        $bookingQuery = Booking::where('marquee_id', $marqueeId);
        if ($this->selectedBranchId) {
            $bookingQuery->where('branch_id', $this->selectedBranchId);
        }

        // =========================================================================
        // GUEST & OPERATIONAL METRICS (BOTH ROLES)
        // =========================================================================
        $totalGuests = (int) (clone $bookingQuery)
            ->whereBetween('booking_date', [$startDateStr, $endDateStr])
            ->whereNotIn('booking_status', ['Cancelled', 'Rejected'])
            ->sum('guest_count');

        $totalBookingsPeriod = (clone $bookingQuery)
            ->whereBetween('booking_date', [$startDateStr, $endDateStr])
            ->whereNotIn('booking_status', ['Cancelled', 'Rejected'])
            ->count();

        $confirmedBookingsPeriod = (clone $bookingQuery)
            ->whereBetween('booking_date', [$startDateStr, $endDateStr])
            ->where('booking_status', 'Confirmed')
            ->count();

        $tentativeBookingsPeriod = (clone $bookingQuery)
            ->whereBetween('booking_date', [$startDateStr, $endDateStr])
            ->whereIn('booking_status', ['Draft', 'Tentative', 'Reserved'])
            ->count();

        $averageGuestsPerEvent = $confirmedBookingsPeriod > 0
            ? (int) round($totalGuests / $confirmedBookingsPeriod)
            : ($totalBookingsPeriod > 0 ? (int) round($totalGuests / $totalBookingsPeriod) : 0);

        // All-time operational counts
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

        // =========================================================================
        // FINANCIAL & LIQUIDITY METRICS (OWNER / EXECUTIVE ONLY)
        // =========================================================================
        $totalSales = 0.0;
        $totalPurchases = 0.0;
        $realizedRevenue = 0.0;
        $customerAdvanceHeld = 0.0;
        $pendingReceivables = 0.0;
        $operatingExpenses = 0.0;
        $netOperatingCashflow = 0.0;
        $bankBalance = 0.0;
        $cashInHand = 0.0;

        if ($canViewFinancials) {
            // Total Booked Sales in Period
            $totalSales = (float) (clone $bookingQuery)
                ->whereBetween('booking_date', [$startDateStr, $endDateStr])
                ->whereNotIn('booking_status', ['Cancelled', 'Rejected'])
                ->sum('grand_total');

            // Total Purchases in Period
            $purchaseQuery = PurchaseInvoice::where('marquee_id', $marqueeId)
                ->whereBetween('purchase_date', [$startDateStr, $endDateStr])
                ->where('status', '!=', 'Cancelled');
            if ($this->selectedBranchId) {
                $purchaseQuery->where('branch_id', $this->selectedBranchId);
            }
            $totalPurchases = (float) $purchaseQuery->sum('net_amount');

            // Realized Revenue (from recognized completed events)
            $realizedRevenue = (float) (clone $bookingQuery)
                ->where('is_revenue_recognized', true)
                ->whereBetween('booking_date', [$startDateStr, $endDateStr])
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
                ->whereBetween('expense_date', [$startDateStr, $endDateStr])
                ->sum('total_amount');

            // Net Operating Cashflow / Margin
            $netOperatingCashflow = $realizedRevenue - $operatingExpenses;

            // Bank Balance from Chart of Accounts & CashBankAccount
            $bankAccountIds = CashBankAccount::where('marquee_id', $marqueeId)
                ->where('type', 'bank')
                ->pluck('account_id')
                ->filter()
                ->unique();

            if ($bankAccountIds->isEmpty()) {
                $bankAccountIds = Account::where('marquee_id', $marqueeId)
                    ->where(function ($q) {
                        $q->where('account_code', '1002')->orWhere('name', 'like', '%Bank%');
                    })
                    ->pluck('id');
            }

            $bankOpening = (float) AccountOpeningBalance::whereIn('account_id', $bankAccountIds)
                ->selectRaw('COALESCE(SUM(debit - credit), 0) as balance')
                ->value('balance');

            $bankJv = (float) DB::table('journal_voucher_items')
                ->join('journal_vouchers', 'journal_voucher_items.journal_voucher_id', '=', 'journal_vouchers.id')
                ->whereNull('journal_vouchers.deleted_at')
                ->where('journal_vouchers.status', 'posted')
                ->where('journal_vouchers.marquee_id', $marqueeId)
                ->whereIn('journal_voucher_items.account_id', $bankAccountIds)
                ->selectRaw('COALESCE(SUM(debit - credit), 0) as balance')
                ->value('balance');

            $bankBalance = $bankOpening + $bankJv;

            // Cash in Hand from Chart of Accounts & CashBankAccount
            $cashAccountIds = CashBankAccount::where('marquee_id', $marqueeId)
                ->where('type', 'cash')
                ->pluck('account_id')
                ->filter()
                ->unique();

            if ($cashAccountIds->isEmpty()) {
                $cashAccountIds = Account::where('marquee_id', $marqueeId)
                    ->where(function ($q) {
                        $q->where('account_code', '1001')->orWhere('name', 'like', '%Cash%');
                    })
                    ->pluck('id');
            }

            $cashOpening = (float) AccountOpeningBalance::whereIn('account_id', $cashAccountIds)
                ->selectRaw('COALESCE(SUM(debit - credit), 0) as balance')
                ->value('balance');

            $cashJv = (float) DB::table('journal_voucher_items')
                ->join('journal_vouchers', 'journal_voucher_items.journal_voucher_id', '=', 'journal_vouchers.id')
                ->whereNull('journal_vouchers.deleted_at')
                ->where('journal_vouchers.status', 'posted')
                ->where('journal_vouchers.marquee_id', $marqueeId)
                ->whereIn('journal_voucher_items.account_id', $cashAccountIds)
                ->selectRaw('COALESCE(SUM(debit - credit), 0) as balance')
                ->value('balance');

            $cashInHand = $cashOpening + $cashJv;
        }

        // =========================================================================
        // TODAY'S FUNCTIONS & UPCOMING PIPELINE
        // =========================================================================
        $todayEvents = (clone $bookingQuery)
            ->with(['customer', 'hall', 'eventType', 'slot', 'branch', 'package'])
            ->whereDate('booking_date', Carbon::today()->format('Y-m-d'))
            ->whereIn('booking_status', ['Confirmed', 'Reserved', 'Completed'])
            ->orderBy('start_time', 'asc')
            ->get();

        $upcomingEvents = (clone $bookingQuery)
            ->with(['customer', 'hall', 'eventType', 'slot', 'branch', 'package'])
            ->whereBetween('booking_date', [
                Carbon::tomorrow()->format('Y-m-d'),
                Carbon::today()->addDays(7)->format('Y-m-d')
            ])
            ->whereIn('booking_status', ['Confirmed', 'Reserved'])
            ->orderBy('booking_date', 'asc')
            ->take(8)
            ->get();

        // Operational Safeguards & Alerts
        $stockBalances = DB::table('inventory_stock_ledgers')
            ->where('marquee_id', $marqueeId)
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->groupBy('item_id')
            ->select('item_id', DB::raw('COALESCE(SUM(qty_in - qty_out), 0) as balance'))
            ->pluck('balance', 'item_id');

        $lowStockItems = InventoryItem::where('marquee_id', $marqueeId)
            ->where('status', 'Active')
            ->with('unit')
            ->get()
            ->map(function ($item) use ($stockBalances) {
                $item->current_stock = (float) ($stockBalances[$item->id] ?? 0.0);
                return $item;
            })
            ->filter(function ($item) {
                return $item->current_stock <= $item->minimum_stock_level || $item->current_stock <= 5;
            })
            ->sortBy('current_stock')
            ->take(5)
            ->values();

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
            'isBookingOfficer' => $isBookingOfficer,
            'canViewFinancials' => $canViewFinancials,
            'isOwnerUser' => $user->isBusinessOwner() || $user->isSuperAdmin(),
            // Financial cards (Owner)
            'totalSales' => $totalSales,
            'totalPurchases' => $totalPurchases,
            'bankBalance' => $bankBalance,
            'cashInHand' => $cashInHand,
            'realizedRevenue' => $realizedRevenue,
            'customerAdvanceHeld' => $customerAdvanceHeld,
            'pendingReceivables' => $pendingReceivables,
            'operatingExpenses' => $operatingExpenses,
            'netOperatingCashflow' => $netOperatingCashflow,
            // Booking & Guest cards
            'totalGuests' => $totalGuests,
            'totalBookingsPeriod' => $totalBookingsPeriod,
            'confirmedBookingsPeriod' => $confirmedBookingsPeriod,
            'tentativeBookingsPeriod' => $tentativeBookingsPeriod,
            'averageGuestsPerEvent' => $averageGuestsPerEvent,
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
