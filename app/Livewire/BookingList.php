<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\BookingHistory;
use App\Models\Branch;
use App\Models\EventType;
use App\Models\Hall;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class BookingList extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Search & Basic Filters
    public $search = '';
    public $filterStatus = '';
    public $filterPaymentStatus = '';
    public $filterHall = '';
    public $filterDateStart = '';
    public $filterDateEnd = '';

    // Advanced Filters
    public $filterBranch = '';
    public $filterEventType = '';
    public $filterGuestStatus = '';
    public $filterBalanceStatus = ''; // 'all', 'outstanding', 'fully_paid'
    public $filterQuickShortcut = ''; // 'all', 'today', 'upcoming', 'next_7_days', 'this_month', 'pending', 'outstanding', 'confirmed', 'tentative'
    public $filterCreatedBy = '';
    public $showAdvancedFilters = false;

    // Action State
    public $deleteId = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => ''],
        'filterPaymentStatus' => ['except' => ''],
        'filterHall' => ['except' => ''],
        'filterBranch' => ['except' => ''],
        'filterEventType' => ['except' => ''],
        'filterGuestStatus' => ['except' => ''],
        'filterBalanceStatus' => ['except' => ''],
        'filterQuickShortcut' => ['except' => ''],
        'filterDateStart' => ['except' => ''],
        'filterDateEnd' => ['except' => ''],
        'filterCreatedBy' => ['except' => ''],
    ];

    public function updatedSearch() { $this->resetPage(); }
    public function updatedFilterStatus() { $this->resetPage(); }
    public function updatedFilterPaymentStatus() { $this->resetPage(); }
    public function updatedFilterHall() { $this->resetPage(); }
    public function updatedFilterBranch() { $this->resetPage(); }
    public function updatedFilterEventType() { $this->resetPage(); }
    public function updatedFilterGuestStatus() { $this->resetPage(); }
    public function updatedFilterBalanceStatus() { $this->resetPage(); }
    public function updatedFilterDateStart() { $this->resetPage(); }
    public function updatedFilterDateEnd() { $this->resetPage(); }
    public function updatedFilterCreatedBy() { $this->resetPage(); }

    public function toggleAdvancedFilters()
    {
        $this->showAdvancedFilters = !$this->showAdvancedFilters;
    }

    /**
     * Apply interactive summary card / shortcut pill filter.
     */
    public function applyShortcutFilter($shortcut)
    {
        $this->filterQuickShortcut = $shortcut;
        $this->filterStatus = '';
        $this->filterDateStart = '';
        $this->filterDateEnd = '';
        $this->filterBalanceStatus = '';
        $this->filterGuestStatus = '';
        $this->resetPage();

        if ($shortcut === 'confirmed') {
            $this->filterStatus = 'Confirmed';
        } elseif ($shortcut === 'tentative') {
            $this->filterStatus = 'Reserved';
        } elseif ($shortcut === 'pending') {
            $this->filterStatus = 'Draft';
        } elseif ($shortcut === 'outstanding') {
            $this->filterBalanceStatus = 'outstanding';
        }
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->filterStatus = '';
        $this->filterPaymentStatus = '';
        $this->filterHall = '';
        $this->filterBranch = '';
        $this->filterEventType = '';
        $this->filterGuestStatus = '';
        $this->filterBalanceStatus = '';
        $this->filterQuickShortcut = '';
        $this->filterDateStart = '';
        $this->filterDateEnd = '';
        $this->filterCreatedBy = '';
        $this->resetPage();
    }

    /**
     * Quick approve a pending draft booking.
     */
    public function approveBooking($id)
    {
        $booking = Booking::findOrFail($id);
        $user = auth()->user();
        
        $isAuthorized = $user->isSuperAdmin() 
            || ($user->role && in_array($user->role->name, ['owner', 'super_admin'])) 
            || ($user->marquee_id === $booking->marquee_id) 
            || (method_exists($user, 'hasPermission') && $user->hasPermission('edit_bookings'));

        if (!$isAuthorized) {
            session()->flash('error', 'Unauthorized: You do not have permission to approve bookings.');
            return;
        }

        $oldStatus = $booking->booking_status;
        $booking->update([
            'booking_status' => 'Confirmed'
        ]);

        BookingHistory::create([
            'booking_id' => $booking->id,
            'user_id' => auth()->id(),
            'status_from' => $oldStatus,
            'status_to' => 'Confirmed',
            'notes' => 'Booking approved and transitioned to Confirmed status from operational dashboard.'
        ]);

        session()->flash('success', 'Booking #' . $booking->booking_number . ' approved successfully.');
    }

    /**
     * Quick reject a pending draft booking.
     */
    public function rejectBooking($id)
    {
        $booking = Booking::findOrFail($id);
        $user = auth()->user();

        $isAuthorized = $user->isSuperAdmin() 
            || ($user->role && in_array($user->role->name, ['owner', 'super_admin'])) 
            || ($user->marquee_id === $booking->marquee_id) 
            || (method_exists($user, 'hasPermission') && $user->hasPermission('edit_bookings'));

        if (!$isAuthorized) {
            session()->flash('error', 'Unauthorized: You do not have permission to reject bookings.');
            return;
        }

        $oldStatus = $booking->booking_status;
        $booking->update([
            'booking_status' => 'Rejected'
        ]);

        BookingHistory::create([
            'booking_id' => $booking->id,
            'user_id' => auth()->id(),
            'status_from' => $oldStatus,
            'status_to' => 'Rejected',
            'notes' => 'Booking rejected from operational dashboard.'
        ]);

        session()->flash('success', 'Booking #' . $booking->booking_number . ' rejected.');
    }

    /**
     * Set the ID of the booking to cancel/delete.
     */
    public function confirmDeletion($id)
    {
        $this->deleteId = $id;
    }

    /**
     * Cancel the booking, log to history, and soft delete.
     */
    public function deleteRecord()
    {
        if (empty($this->deleteId)) {
            return;
        }

        $booking = Booking::findOrFail($this->deleteId);

        if (!auth()->user()->isSuperAdmin() && $booking->marquee_id !== auth()->user()->marquee_id) {
            session()->flash('error', 'Unauthorized operation.');
            return;
        }

        $user = auth()->user();
        $isOwner = $user->role && in_array($user->role->name, ['owner', 'super_admin']);

        if ($booking->booking_status === 'Completed' && !$isOwner) {
            session()->flash('error', 'Completed bookings cannot be cancelled or deleted.');
            $this->deleteId = null;
            return;
        }

        $oldStatus = $booking->booking_status;
        $booking->update([
            'booking_status' => 'Cancelled'
        ]);

        BookingHistory::create([
            'booking_id' => $booking->id,
            'user_id' => auth()->id(),
            'status_from' => $oldStatus,
            'status_to' => 'Cancelled',
            'notes' => 'Booking cancelled and soft deleted by user request.'
        ]);

        $booking->delete();

        session()->flash('success', 'Booking #' . $booking->booking_number . ' has been cancelled and deleted.');
        $this->deleteId = null;
        $this->resetPage();
    }

    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;

        // Fetch lookup collections for filters
        $halls = Hall::where('marquee_id', $marqueeId)->where('status', 'active')->orderBy('hall_name')->get();
        $branches = Branch::where('marquee_id', $marqueeId)->where('status', 'active')->orderBy('name')->get();
        $eventTypes = EventType::where('marquee_id', $marqueeId)->orderBy('event_type_name')->get();
        $operators = User::where('marquee_id', $marqueeId)->orderBy('name')->get();

        // ----------------------------------------------------
        // Real Database Summary Metrics (Tenant-scoped)
        // ----------------------------------------------------
        $baseQuery = Booking::where('marquee_id', $marqueeId);
        if (!empty($this->filterBranch)) {
            $baseQuery->whereHas('hall', fn($q) => $q->where('branch_id', $this->filterBranch));
        }

        $totalBookingsCount = (clone $baseQuery)->count();
        $confirmedBookingsCount = (clone $baseQuery)->where('booking_status', 'Confirmed')->count();
        $tentativeBookingsCount = (clone $baseQuery)->whereIn('booking_status', ['Reserved', 'Draft'])->count();
        $todaysEventsCount = (clone $baseQuery)->whereDate('booking_date', Carbon::today())->where('booking_status', '!=', 'Cancelled')->count();
        $upcomingEventsCount = (clone $baseQuery)->whereDate('booking_date', '>=', Carbon::today())->whereNotIn('booking_status', ['Cancelled', 'Completed'])->count();
        $thisMonthCount = (clone $baseQuery)->whereBetween('booking_date', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])->count();
        $pendingApprovalsCount = (clone $baseQuery)->whereIn('booking_status', ['Draft', 'Pending'])->count();

        // Dynamic Payment Outstanding query
        $outstandingQuery = (clone $baseQuery)->whereNotIn('booking_status', ['Cancelled'])
            ->withSum('payments as paid_amount', 'amount');
        
        $allBookingsForFinances = $outstandingQuery->get();
        $outstandingPaymentsCount = $allBookingsForFinances->filter(function($b) {
            $received = $b->paid_amount ?? 0.00;
            return ($b->grand_total - $received) > 0.01;
        })->count();

        $outstandingAmountSum = $allBookingsForFinances->sum(function($b) {
            $received = $b->paid_amount ?? 0.00;
            return max(0.00, $b->grand_total - $received);
        });

        // ----------------------------------------------------
        // Main Filtered Table Query
        // ----------------------------------------------------
        $query = Booking::with(['customer', 'hall', 'hall.branch', 'halls', 'slot', 'package', 'payments', 'eventType', 'creator', 'finalBill'])
            ->withSum('payments as paid_amount', 'amount')
            ->where('marquee_id', $marqueeId);

        // Apply Search
        if (!empty($this->search)) {
            $searchTerm = '%' . $this->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('booking_number', 'like', $searchTerm)
                  ->orWhereHas('customer', function ($cq) use ($searchTerm) {
                      $cq->where('full_name', 'like', $searchTerm)
                        ->orWhere('first_name', 'like', $searchTerm)
                        ->orWhere('last_name', 'like', $searchTerm)
                        ->orWhere('phone_number', 'like', $searchTerm)
                        ->orWhere('customer_code', 'like', $searchTerm);
                  });
            });
        }

        // Apply Filters
        if (!empty($this->filterStatus)) {
            $query->where('booking_status', $this->filterStatus);
        }

        if (!empty($this->filterPaymentStatus)) {
            $query->where('payment_status', $this->filterPaymentStatus);
        }

        if (!empty($this->filterHall)) {
            $query->where('hall_id', $this->filterHall);
        }

        if (!empty($this->filterBranch)) {
            $query->whereHas('hall', fn($q) => $q->where('branch_id', $this->filterBranch));
        }

        if (!empty($this->filterEventType)) {
            $query->where('event_type_id', $this->filterEventType);
        }

        if (!empty($this->filterGuestStatus)) {
            $query->where('guest_status', $this->filterGuestStatus);
        }

        if (!empty($this->filterCreatedBy)) {
            $query->where('created_by', $this->filterCreatedBy);
        }

        if (!empty($this->filterDateStart)) {
            $query->where('booking_date', '>=', $this->filterDateStart);
        }

        if (!empty($this->filterDateEnd)) {
            $query->where('booking_date', '<=', $this->filterDateEnd);
        }

        // Apply Quick Shortcuts
        if ($this->filterQuickShortcut === 'today') {
            $query->whereDate('booking_date', Carbon::today());
        } elseif ($this->filterQuickShortcut === 'upcoming') {
            $query->whereDate('booking_date', '>=', Carbon::today())->whereNotIn('booking_status', ['Cancelled', 'Completed']);
        } elseif ($this->filterQuickShortcut === 'next_7_days') {
            $query->whereBetween('booking_date', [Carbon::today(), Carbon::today()->addDays(7)]);
        } elseif ($this->filterQuickShortcut === 'this_month') {
            $query->whereBetween('booking_date', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
        }

        $bookings = $query->orderBy('booking_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        // Filter balance status after collection retrieval if selected
        if ($this->filterBalanceStatus === 'outstanding') {
            // Re-filter collection or use having
        }

        return view('livewire.booking-list', [
            'bookings' => $bookings,
            'halls' => $halls,
            'branches' => $branches,
            'eventTypes' => $eventTypes,
            'operators' => $operators,
            'totalBookingsCount' => $totalBookingsCount,
            'confirmedBookingsCount' => $confirmedBookingsCount,
            'tentativeBookingsCount' => $tentativeBookingsCount,
            'todaysEventsCount' => $todaysEventsCount,
            'upcomingEventsCount' => $upcomingEventsCount,
            'thisMonthCount' => $thisMonthCount,
            'pendingApprovalsCount' => $pendingApprovalsCount,
            'outstandingPaymentsCount' => $outstandingPaymentsCount,
            'outstandingAmountSum' => $outstandingAmountSum,
        ]);
    }

    /**
     * Export the filtered list of bookings to Excel (CSV format).
     */
    public function exportExcel()
    {
        $marqueeId = auth()->user()->marquee_id;

        $query = Booking::with(['customer', 'hall', 'hall.branch', 'halls', 'slot', 'package', 'payments', 'eventType', 'creator', 'finalBill'])
            ->withSum('payments as paid_amount', 'amount')
            ->where('marquee_id', $marqueeId);

        if (!empty($this->search)) {
            $searchTerm = '%' . $this->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('booking_number', 'like', $searchTerm)
                  ->orWhereHas('customer', function ($cq) use ($searchTerm) {
                      $cq->where('full_name', 'like', $searchTerm)
                        ->orWhere('phone_number', 'like', $searchTerm);
                  });
            });
        }

        if (!empty($this->filterStatus)) {
            $query->where('booking_status', $this->filterStatus);
        }

        if (!empty($this->filterPaymentStatus)) {
            $query->where('payment_status', $this->filterPaymentStatus);
        }

        if (!empty($this->filterHall)) {
            $query->where('hall_id', $this->filterHall);
        }

        if (!empty($this->filterDateStart)) {
            $query->where('booking_date', '>=', $this->filterDateStart);
        }

        if (!empty($this->filterDateEnd)) {
            $query->where('booking_date', '<=', $this->filterDateEnd);
        }

        $bookings = $query->orderBy('booking_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="bookings-export-' . now()->format('YmdHis') . '.csv"',
        ];

        $callback = function () use ($bookings) {
            $file = fopen('php://output', 'w');

            // Add UTF-8 BOM for Urdu/Excel support
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, [
                'Booking #',
                'Customer Name',
                'Phone Number',
                'Event Type',
                'Hall / Venue',
                'Shift/Slot',
                'Event Date',
                'Tentative Guests',
                'Confirmed Guests',
                'Effective Headcount',
                'Per Plate Rate',
                'Total Amount',
                'Paid Amount',
                'Balance Amount',
                'Booking Status',
                'Guest Status',
                'Payment Status'
            ]);

            foreach ($bookings as $b) {
                $received = $b->paid_amount ?? 0.00;
                $balance = max(0.00, $b->grand_total - $received);

                fputcsv($file, [
                    $b->booking_number,
                    $b->customer->full_name ?? '—',
                    $b->customer->phone_number ?? '—',
                    $b->eventType->event_type_name ?? '—',
                    $b->hall->hall_name ?? '—',
                    $b->slot->slot_name ?? 'Custom Time',
                    $b->booking_date->format('Y-m-d'),
                    $b->tentative_guests ?? $b->guest_count,
                    $b->confirmed_guests ?? '—',
                    $b->effective_guest_count,
                    $b->per_plate_price,
                    $b->grand_total,
                    $received,
                    $balance,
                    $b->booking_status,
                    $b->guest_status ?? ($b->is_guest_confirmed ? 'Confirmed' : 'Tentative'),
                    $b->payment_status
                ]);
            }

            fclose($file);
        };

        return response()->streamDownload($callback, 'bookings-export-' . now()->format('YmdHis') . '.csv', $headers);
    }
}
