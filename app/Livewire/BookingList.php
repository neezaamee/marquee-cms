<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\BookingHistory;
use App\Models\Hall;
use Livewire\Component;
use Livewire\WithPagination;

class BookingList extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Filters & Search
    public $search = '';
    public $filterStatus = '';
    public $filterPaymentStatus = '';
    public $filterHall = '';
    public $filterDateStart = '';
    public $filterDateEnd = '';

    // Action State
    public $deleteId = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => ''],
        'filterPaymentStatus' => ['except' => ''],
        'filterHall' => ['except' => ''],
        'filterDateStart' => ['except' => ''],
        'filterDateEnd' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function updatingFilterPaymentStatus()
    {
        $this->resetPage();
    }

    public function updatingFilterHall()
    {
        $this->resetPage();
    }

    public function updatingFilterDateStart()
    {
        $this->resetPage();
    }

    public function updatingFilterDateEnd()
    {
        $this->resetPage();
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

        // Access check
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

        // Update status to Cancelled
        $booking->update([
            'booking_status' => 'Cancelled'
        ]);

        // Log to history
        BookingHistory::create([
            'booking_id' => $booking->id,
            'user_id' => auth()->id(),
            'status_from' => $oldStatus,
            'status_to' => 'Cancelled',
            'notes' => 'Booking cancelled and soft deleted by user request.'
        ]);

        // Soft delete the booking
        $booking->delete();

        session()->flash('success', 'Booking #' . $booking->booking_number . ' has been cancelled and deleted.');
        $this->deleteId = null;
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->filterStatus = '';
        $this->filterPaymentStatus = '';
        $this->filterHall = '';
        $this->filterDateStart = '';
        $this->filterDateEnd = '';
        $this->resetPage();
    }

    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;

        // Fetch halls for filters
        $halls = Hall::where('marquee_id', $marqueeId)
            ->where('status', 'active')
            ->get();

        // Build query
        $query = Booking::with(['customer', 'hall', 'slot', 'package', 'payments', 'eventType']);

        if (!empty($this->search)) {
            $searchTerm = '%' . $this->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('booking_number', 'like', $searchTerm)
                  ->orWhereHas('customer', function ($cq) use ($searchTerm) {
                      $cq->where('first_name', 'like', $searchTerm)
                        ->orWhere('last_name', 'like', $searchTerm)
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

        $bookings = $query->orderBy('booking_date', 'asc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('livewire.booking-list', [
            'bookings' => $bookings,
            'halls' => $halls,
        ]);
    }

    /**
     * Export the filtered list of bookings to Excel (CSV format).
     */
    public function exportExcel()
    {
        $query = Booking::with(['customer', 'hall', 'slot', 'package', 'payments', 'eventType']);

        if (!empty($this->search)) {
            $searchTerm = '%' . $this->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('booking_number', 'like', $searchTerm)
                  ->orWhereHas('customer', function ($cq) use ($searchTerm) {
                      $cq->where('first_name', 'like', $searchTerm)
                        ->orWhere('last_name', 'like', $searchTerm)
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
            'Content-Disposition' => 'attachment; filename="bookings-report-' . now()->format('YmdHis') . '.csv"',
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
                'Hall Name',
                'Shift/Slot',
                'Event Date',
                'Guest Count',
                'Per Head Rate',
                'Total Amount',
                'Received Amount',
                'Balance Amount',
                'Booking Status',
                'Payment Status'
            ]);

            foreach ($bookings as $b) {
                $received = $b->payments->sum('amount');
                $balance = max(0.00, $b->grand_total - $received);

                fputcsv($file, [
                    $b->booking_number,
                    $b->customer->full_name ?? '—',
                    $b->customer->phone_number ?? '—',
                    $b->eventType->event_type_name ?? '—',
                    $b->hall->hall_name ?? '—',
                    $b->slot->slot_name ?? 'Custom Time',
                    $b->booking_date->format('Y-m-d'),
                    $b->guest_count,
                    $b->per_plate_price,
                    $b->grand_total,
                    $received,
                    $balance,
                    $b->booking_status,
                    $b->payment_status
                ]);
            }

            fclose($file);
        };

        return response()->streamDownload($callback, 'bookings-report-' . now()->format('YmdHis') . '.csv', $headers);
    }
}
