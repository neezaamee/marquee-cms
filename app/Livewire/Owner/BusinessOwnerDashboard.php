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
use App\Models\Lead;
use App\Models\Marquee;
use App\Models\PettyCashAccount;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class BusinessOwnerDashboard extends Component
{
    public ?int $selectedMarqueeId = null; // null = Default/Active Marquee
    public ?int $selectedBranchId = null; // null = All Branches
    public string $timeframe = 'this_month'; // 'today', 'this_week', 'this_month', 'this_quarter', 'this_year', 'last_30_days', 'custom'
    public string $filterRange = 'this_month'; // Synchronized alias matching Purchase Dashboard
    public string $customDateFrom = '';
    public string $customDateTo = '';
    public ?string $viewMode = null; // 'executive', 'operations' (for owners to toggle preview)

    protected $queryString = [
        'selectedMarqueeId' => ['except' => null],
        'selectedBranchId' => ['except' => null],
        'timeframe' => ['except' => 'this_month'],
        'filterRange' => ['except' => 'this_month'],
        'customDateFrom' => ['except' => ''],
        'customDateTo' => ['except' => ''],
    ];

    public function mount()
    {
        $user = auth()->user();
        if ($user) {
            $activeId = session('active_marquee_id') ?: $user->getActiveMarqueeId();
            if ($user->isSuperAdmin() && !session('active_marquee_id')) {
                $marqueeWithData = Marquee::whereHas('bookings')->first();
                $activeId = $marqueeWithData ? $marqueeWithData->id : ($activeId ?: Marquee::first()?->id);
            }
            $this->selectedMarqueeId = $activeId ? (int) $activeId : null;
        }

        $this->filterRange = $this->timeframe;
        $this->customDateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->customDateTo = now()->endOfMonth()->format('Y-m-d');
    }

    public function updatedSelectedMarqueeId()
    {
        if ($this->selectedMarqueeId) {
            session(['active_marquee_id' => (int) $this->selectedMarqueeId]);
            $user = auth()->user();
            if ($user && $user->isBusinessOwner()) {
                $user->update([
                    'marquee_id' => (int) $this->selectedMarqueeId,
                    'branch_id' => null,
                ]);
            }
        }
        $this->selectedBranchId = null;
    }

    public function updatedSelectedBranchId()
    {
        // Reactive refresh
    }

    public function updatedFilterRange()
    {
        $this->timeframe = $this->filterRange;
    }

    public function updatedTimeframe()
    {
        $this->filterRange = $this->timeframe;
    }

    public function updatedCustomDateFrom()
    {
        // Reactive refresh
    }

    public function updatedCustomDateTo()
    {
        // Reactive refresh
    }

    public function setFilterRange(string $range)
    {
        $this->timeframe = $range;
        $this->filterRange = $range;
    }

    public function setViewMode(?string $mode)
    {
        $this->viewMode = $mode;
    }

    protected function getDateRange(): array
    {
        $now = Carbon::now();
        switch ($this->timeframe) {
            case 'today':
                return [$now->copy()->startOfDay(), $now->copy()->endOfDay(), 'Today'];
            case 'this_week':
            case 'week':
                return [$now->copy()->startOfWeek(), $now->copy()->endOfWeek(), 'This Week'];
            case 'this_month':
            case 'month':
                return [$now->copy()->startOfMonth(), $now->copy()->endOfMonth(), 'This Month'];
            case 'this_quarter':
            case 'quarter':
                return [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter(), 'This Quarter'];
            case 'this_year':
            case 'year':
                return [$now->copy()->startOfYear(), $now->copy()->endOfYear(), 'This Year'];
            case 'last_30_days':
                return [$now->copy()->subDays(30)->startOfDay(), $now->copy()->endOfDay(), 'Last 30 Days'];
            case 'custom':
                $from = $this->customDateFrom ? Carbon::parse($this->customDateFrom)->startOfDay() : $now->copy()->startOfMonth();
                $to = $this->customDateTo ? Carbon::parse($this->customDateTo)->endOfDay() : $now->copy()->endOfMonth();
                return [$from, $to, 'Custom Range'];
            default:
                return [$now->copy()->startOfMonth(), $now->copy()->endOfMonth(), 'This Month'];
        }
    }

    public function render()
    {
        $user = auth()->user();
        $accessibleMarquees = $user ? ($user->isSuperAdmin() ? Marquee::where('status', 'active')->orderBy('name')->get() : $user->getAccessibleMarquees()->where('status', 'active')) : collect();

        if (!$this->selectedMarqueeId) {
            $this->selectedMarqueeId = session('active_marquee_id') ?: ($user ? $user->getActiveMarqueeId() : null);
            if (!$this->selectedMarqueeId && $accessibleMarquees->isNotEmpty()) {
                $this->selectedMarqueeId = (int) $accessibleMarquees->first()->id;
            }
        }

        $marqueeId = $this->selectedMarqueeId ?: ($user ? ($user->getActiveMarqueeId() ?: Marquee::first()?->id) : null);

        $marquee = $marqueeId ? Marquee::with('branches')->find($marqueeId) : null;
        $branches = $marquee ? $marquee->branches : collect();

        // Check if user is a booking officer / staff
        $isBookingOfficerRole = $user->isBookingOfficer() || (!$user->isBusinessOwner() && !$user->isSuperAdmin() && !$user->hasRole(['accountant', 'branch_manager']));
        
        // Determine effective view mode (owners can toggle, booking officers are locked to operations)
        $isBookingOfficer = $isBookingOfficerRole || ($this->viewMode === 'operations');
        $canViewFinancials = !$isBookingOfficer && ($user->isBusinessOwner() || $user->isSuperAdmin() || $user->hasRole(['accountant', 'branch_manager']));

        // 1. Date filter range
        [$startDate, $endDate, $periodLabel] = $this->getDateRange();
        $startDateStr = $startDate->format('Y-m-d');
        $endDateStr = $endDate->format('Y-m-d');

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
        $totalPayablesAndLiabilities = 0.0;
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

            // Total Payables & Liabilities (Supplier dues, Vendor dues, Credit expenses, and GL Payables)
            $supplierPayables = 0.0;
            $suppliers = Supplier::withoutGlobalScope('tenant')
                ->where('marquee_id', $marqueeId)
                ->whereIn('status', ['active', 'Active'])
                ->get();
            foreach ($suppliers as $supp) {
                if ($supp->current_balance > 0) {
                    $supplierPayables += (float) $supp->current_balance;
                }
            }

            $vendorPayables = 0.0;
            $vendors = Vendor::withoutGlobalScope('tenant')
                ->where('marquee_id', $marqueeId)
                ->whereIn('status', ['active', 'Active'])
                ->get();
            foreach ($vendors as $v) {
                if ($v->current_balance > 0) {
                    $vendorPayables += (float) $v->current_balance;
                }
            }

            $creditExpensePayables = (float) Expense::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)
                ->where('payment_method', Expense::METHOD_CREDIT)
                ->where('payment_status', 'Unpaid')
                ->sum('total_amount');

            $subledgerPayables = $supplierPayables + $vendorPayables + $creditExpensePayables;

            $apAccountIds = Account::where('marquee_id', $marqueeId)
                ->where(function ($q) {
                    $q->where('account_code', '2001')
                      ->orWhere('name', 'like', '%Accounts Payable%');
                })
                ->pluck('id');

            $glApOpening = (float) AccountOpeningBalance::whereIn('account_id', $apAccountIds)
                ->selectRaw('COALESCE(SUM(credit - debit), 0) as balance')
                ->value('balance');

            $glApJv = (float) DB::table('journal_voucher_items')
                ->join('journal_vouchers', 'journal_voucher_items.journal_voucher_id', '=', 'journal_vouchers.id')
                ->whereNull('journal_vouchers.deleted_at')
                ->where('journal_vouchers.status', 'posted')
                ->where('journal_vouchers.marquee_id', $marqueeId)
                ->whereIn('journal_voucher_items.account_id', $apAccountIds)
                ->selectRaw('COALESCE(SUM(credit - debit), 0) as balance')
                ->value('balance');

            $glApBalance = max(0, $glApOpening + $glApJv);

            $totalPayablesAndLiabilities = max($subledgerPayables, $glApBalance);

            // Operating Expenses (Recognized / Posted / Approved operational expenses)
            $expenseQuery = Expense::where('marquee_id', $marqueeId)
                ->whereNotIn('status', [Expense::STATUS_DRAFT, Expense::STATUS_REJECTED, Expense::STATUS_CANCELLED]);
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

            // Cash in Hand from Chart of Accounts, CashBankAccount & PettyCashAccount
            $cashAccountIds = CashBankAccount::where('marquee_id', $marqueeId)
                ->where('type', 'cash')
                ->pluck('account_id')
                ->merge(
                    PettyCashAccount::where('marquee_id', $marqueeId)
                        ->pluck('gl_account_id')
                )
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

        // =========================================================================
        // ANALYTICS & QUICK STATS
        // =========================================================================
        // Financial & commercial quick ratios
        $avgBookingValue = $totalBookingsPeriod > 0 ? (float) ($totalSales / $totalBookingsPeriod) : 0.0;
        $avgSpendPerGuest = $totalGuests > 0 ? (float) ($totalSales / $totalGuests) : 0.0;
        $collectionRate = $totalSales > 0 ? (float) round((max(0, $totalSales - $pendingReceivables) / $totalSales) * 100, 1) : 0.0;
        $advanceCoverageRatio = $totalSales > 0 ? (float) round(($customerAdvanceHeld / $totalSales) * 100, 1) : 0.0;
        $profitMarginPct = $realizedRevenue > 0 ? (float) round(($netOperatingCashflow / $realizedRevenue) * 100, 1) : 0.0;
        $expenseToRevenueRatio = $realizedRevenue > 0 ? (float) round(($operatingExpenses / $realizedRevenue) * 100, 1) : 0.0;

        // CRM & Customer Analytics
        $totalCustomersCount = Customer::where('marquee_id', $marqueeId)->count();
        $leadsInPeriodCount = Lead::where('marquee_id', $marqueeId)
            ->whereBetween('created_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->count();
        $convertedLeadsCount = Lead::where('marquee_id', $marqueeId)
            ->whereBetween('created_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->where('status', 'converted')
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->count();
        $leadConversionRate = $leadsInPeriodCount > 0 ? (float) round(($convertedLeadsCount / $leadsInPeriodCount) * 100, 1) : 0.0;

        // Event Type Analytics Breakdown
        $eventTypeBreakdown = DB::table('bookings')
            ->join('event_types', 'event_types.id', '=', 'bookings.event_type_id')
            ->where('bookings.marquee_id', $marqueeId)
            ->whereBetween('bookings.booking_date', [$startDateStr, $endDateStr])
            ->whereNotIn('bookings.booking_status', ['Cancelled', 'Rejected'])
            ->when($this->selectedBranchId, fn($q) => $q->where('bookings.branch_id', $this->selectedBranchId))
            ->select(
                'event_types.id',
                'event_types.event_type_name',
                DB::raw('COUNT(bookings.id) as booking_count'),
                DB::raw('SUM(bookings.guest_count) as total_guests'),
                DB::raw('SUM(bookings.grand_total) as total_revenue')
            )
            ->groupBy('event_types.id', 'event_types.event_type_name')
            ->orderByDesc('total_revenue')
            ->take(6)
            ->get();

        // Hall Venue Distribution
        $hallBreakdown = DB::table('bookings')
            ->leftJoin('halls', 'halls.id', '=', 'bookings.hall_id')
            ->where('bookings.marquee_id', $marqueeId)
            ->whereBetween('bookings.booking_date', [$startDateStr, $endDateStr])
            ->whereNotIn('bookings.booking_status', ['Cancelled', 'Rejected'])
            ->when($this->selectedBranchId, fn($q) => $q->where('bookings.branch_id', $this->selectedBranchId))
            ->select(
                DB::raw("COALESCE(halls.hall_name, 'Main Hall') as hall_name"),
                DB::raw('COUNT(bookings.id) as booking_count'),
                DB::raw('SUM(bookings.guest_count) as total_guests'),
                DB::raw('SUM(bookings.grand_total) as total_revenue')
            )
            ->groupBy(DB::raw("COALESCE(halls.hall_name, 'Main Hall')"))
            ->orderByDesc('total_revenue')
            ->take(5)
            ->get();

        // Shift Slot Utilization
        $slotBreakdown = DB::table('bookings')
            ->leftJoin('slots', 'slots.id', '=', 'bookings.slot_id')
            ->where('bookings.marquee_id', $marqueeId)
            ->whereBetween('bookings.booking_date', [$startDateStr, $endDateStr])
            ->whereNotIn('bookings.booking_status', ['Cancelled', 'Rejected'])
            ->when($this->selectedBranchId, fn($q) => $q->where('bookings.branch_id', $this->selectedBranchId))
            ->select(
                DB::raw("COALESCE(slots.slot_name, 'Standard Shift') as slot_name"),
                DB::raw('COUNT(bookings.id) as booking_count'),
                DB::raw('SUM(bookings.guest_count) as total_guests'),
                DB::raw('SUM(bookings.grand_total) as total_revenue')
            )
            ->groupBy(DB::raw("COALESCE(slots.slot_name, 'Standard Shift')"))
            ->orderByDesc('booking_count')
            ->get();

        // 6-Month Rolling Commercial & P&L Trend
        $monthlyPerformanceTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $mStart = now()->subMonths($i)->startOfMonth()->format('Y-m-d');
            $mEnd = now()->subMonths($i)->endOfMonth()->format('Y-m-d');
            $mLabel = now()->subMonths($i)->format('M Y');

            $mSales = (float) Booking::where('marquee_id', $marqueeId)
                ->whereBetween('booking_date', [$mStart, $mEnd])
                ->whereNotIn('booking_status', ['Cancelled', 'Rejected'])
                ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
                ->sum('grand_total');

            $mRealized = (float) Booking::where('marquee_id', $marqueeId)
                ->where('is_revenue_recognized', true)
                ->whereBetween('booking_date', [$mStart, $mEnd])
                ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
                ->sum('revenue_recognized');

            $mExpenses = (float) Expense::where('marquee_id', $marqueeId)
                ->whereNotIn('status', [Expense::STATUS_DRAFT, Expense::STATUS_REJECTED, Expense::STATUS_CANCELLED])
                ->whereBetween('expense_date', [$mStart, $mEnd])
                ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
                ->sum('total_amount');

            $mBookingsCount = Booking::where('marquee_id', $marqueeId)
                ->whereBetween('booking_date', [$mStart, $mEnd])
                ->whereNotIn('booking_status', ['Cancelled', 'Rejected'])
                ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
                ->count();

            $mNet = $mRealized - $mExpenses;

            $monthlyPerformanceTrend[] = [
                'month' => $mLabel,
                'sales' => $mSales,
                'realized' => $mRealized,
                'expenses' => $mExpenses,
                'net' => $mNet,
                'bookings_count' => $mBookingsCount,
            ];
        }

        // Top 5 Expense Categories Breakdown
        $topExpenseCategories = DB::table('expenses')
            ->join('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->where('expenses.marquee_id', $marqueeId)
            ->whereNotIn('expenses.status', [Expense::STATUS_DRAFT, Expense::STATUS_REJECTED, Expense::STATUS_CANCELLED])
            ->whereBetween('expenses.expense_date', [$startDateStr, $endDateStr])
            ->when($this->selectedBranchId, fn($q) => $q->where('expenses.branch_id', $this->selectedBranchId))
            ->select(
                'expense_categories.name as category_name',
                DB::raw('COUNT(expenses.id) as expense_count'),
                DB::raw('SUM(expenses.total_amount) as total_amount')
            )
            ->groupBy('expense_categories.id', 'expense_categories.name')
            ->orderByDesc('total_amount')
            ->take(5)
            ->get();

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
            'totalPayablesAndLiabilities' => $totalPayablesAndLiabilities,
            'operatingExpenses' => $operatingExpenses,
            'netOperatingCashflow' => $netOperatingCashflow,
            // Quick stats & Ratios
            'avgBookingValue' => $avgBookingValue,
            'avgSpendPerGuest' => $avgSpendPerGuest,
            'collectionRate' => $collectionRate,
            'advanceCoverageRatio' => $advanceCoverageRatio,
            'profitMarginPct' => $profitMarginPct,
            'expenseToRevenueRatio' => $expenseToRevenueRatio,
            'totalCustomersCount' => $totalCustomersCount,
            'leadsInPeriodCount' => $leadsInPeriodCount,
            'convertedLeadsCount' => $convertedLeadsCount,
            'leadConversionRate' => $leadConversionRate,
            // Visual Analytics Breakdowns
            'eventTypeBreakdown' => $eventTypeBreakdown,
            'hallBreakdown' => $hallBreakdown,
            'slotBreakdown' => $slotBreakdown,
            'monthlyPerformanceTrend' => $monthlyPerformanceTrend,
            'topExpenseCategories' => $topExpenseCategories,
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
            'periodLabel' => $periodLabel,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'customDateFrom' => $this->customDateFrom,
            'customDateTo' => $this->customDateTo,
            'timeframe' => $this->timeframe,
            'filterRange' => $this->timeframe,
            'accessibleMarquees' => $accessibleMarquees,
            'selectedMarqueeId' => $this->selectedMarqueeId,
        ]);
    }
}
