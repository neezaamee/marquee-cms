<?php

namespace App\Livewire;

use App\Models\Marquee;
use App\Models\Vendor;
use App\Models\VendorLedger;
use Livewire\Component;
use Livewire\WithPagination;

class VendorLedgerView extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public ?Vendor $vendor = null;
    public $filterVendorId = '';
    public $filterBookingId = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $perPage = 15;

    public function updatingFilterVendorId() { $this->resetPage(); }
    public function updatingFilterBookingId() { $this->resetPage(); }
    public function updatingDateFrom() { $this->resetPage(); }
    public function updatingDateTo() { $this->resetPage(); }
    public function updatingPerPage() { $this->resetPage(); }

    public function getMarqueeId(): ?int
    {
        $user = auth()->user();
        $id = $user ? ($user->getActiveMarqueeId() ?: $user->marquee_id) : null;
        if (!$id && $user?->isSuperAdmin()) {
            $first = Marquee::first();
            return $first ? (int) $first->id : null;
        }
        return $id;
    }

    public function mount(?Vendor $vendor = null)
    {
        $user = auth()->user();
        if ($vendor && $user && !$user->isSuperAdmin()) {
            if ($vendor->marquee_id && !$user->hasAccessToMarquee($vendor->marquee_id)) {
                abort(403, 'Unauthorized access to this Service Provider.');
            }
        }
        $this->vendor = $vendor;
        if ($vendor) {
            $this->filterVendorId = $vendor->id;
        } elseif (request()->has('filterVendorId')) {
            $this->filterVendorId = (int) request()->query('filterVendorId');
        }

        if (request()->has('booking_id')) {
            $this->filterBookingId = (int) request()->query('booking_id');
        }
    }

    public function exportCsv()
    {
        $user = auth()->user();
        $marqueeId = $this->getMarqueeId();

        $query = VendorLedger::withoutGlobalScope('tenant')->with(['vendor', 'sale', 'booking.customer']);

        if ($marqueeId) {
            $query->where('marquee_id', $marqueeId);
        } elseif (!$user?->isSuperAdmin()) {
            $query->whereRaw('1 = 0');
        }

        if ($this->vendor) {
            $query->where('vendor_id', $this->vendor->id);
        } elseif (!empty($this->filterVendorId)) {
            $query->where('vendor_id', $this->filterVendorId);
        }

        if (!empty($this->filterBookingId)) {
            $query->where('booking_id', $this->filterBookingId);
        }

        if (!empty($this->dateFrom)) {
            $query->whereDate('transaction_date', '>=', $this->dateFrom);
        }

        if (!empty($this->dateTo)) {
            $query->whereDate('transaction_date', '<=', $this->dateTo);
        }

        $ledgers = $query->orderBy('transaction_date', 'asc')->orderBy('id', 'asc')->get();

        $vendorName = $this->vendor ? $this->vendor->name : (!empty($this->filterVendorId) ? (Vendor::withoutGlobalScope('tenant')->find($this->filterVendorId)?->name ?? 'All_Providers') : 'All_Providers');
        $filename = 'Provider_Ledger_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $vendorName) . '_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($ledgers, $vendorName) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel

            fputcsv($handle, ['SERVICE PROVIDER FINANCIAL LEDGER STATEMENT']);
            fputcsv($handle, ['Provider / Vendor', $vendorName]);
            fputcsv($handle, ['Generated At', date('Y-m-d H:i:s')]);
            fputcsv($handle, []);

            fputcsv($handle, [
                'Date',
                'Reference #',
                'Service Provider',
                'Booking #',
                'Customer Name',
                'Transaction Description',
                'Sale Amount (PKR)',
                'Commission (PKR)',
                'Payout Amount (PKR)',
                'Running Balance (PKR)',
            ]);

            foreach ($ledgers as $entry) {
                fputcsv($handle, [
                    $entry->transaction_date ? $entry->transaction_date->format('Y-m-d') : '—',
                    $entry->reference_number ?: '—',
                    $entry->vendor?->name ?: '—',
                    $entry->booking?->booking_number ?: '—',
                    $entry->booking?->customer?->name ?: '—',
                    $entry->description ?: '—',
                    $entry->sale_amount > 0 ? $entry->sale_amount : '0.00',
                    $entry->commission_amount > 0 ? $entry->commission_amount : '0.00',
                    $entry->payment_amount > 0 ? $entry->payment_amount : '0.00',
                    $entry->running_balance,
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    public function render()
    {
        $user = auth()->user();
        $marqueeId = $this->getMarqueeId();

        $query = VendorLedger::withoutGlobalScope('tenant')->with(['vendor', 'sale', 'booking.customer']);

        if ($marqueeId) {
            $query->where('marquee_id', $marqueeId);
        } elseif (!$user?->isSuperAdmin()) {
            $query->whereRaw('1 = 0');
        }

        if ($this->vendor) {
            $query->where('vendor_id', $this->vendor->id);
        } elseif (!empty($this->filterVendorId)) {
            $query->where('vendor_id', $this->filterVendorId);
        }

        if (!empty($this->filterBookingId)) {
            $query->where('booking_id', $this->filterBookingId);
        }

        if (!empty($this->dateFrom)) {
            $query->whereDate('transaction_date', '>=', $this->dateFrom);
        }

        if (!empty($this->dateTo)) {
            $query->whereDate('transaction_date', '<=', $this->dateTo);
        }

        $perPageCount = is_numeric($this->perPage) && (int)$this->perPage > 0 ? (int)$this->perPage : 15;
        $ledgers = $query->orderBy('transaction_date', 'desc')->orderBy('id', 'desc')->paginate($perPageCount);

        $vendorsQuery = Vendor::withoutGlobalScope('tenant');
        if ($marqueeId) {
            $vendorsQuery->where('marquee_id', $marqueeId);
        } elseif (!$user?->isSuperAdmin()) {
            $vendorsQuery->whereRaw('1 = 0');
        }
        $vendors = $vendorsQuery->orderBy('name')->get();

        $selectedVendor = $this->vendor ?: (!empty($this->filterVendorId) ? $vendors->firstWhere('id', (int)$this->filterVendorId) : null);
        $marquee = $marqueeId ? Marquee::find($marqueeId) : null;

        $fromStr = $this->dateFrom ? \Carbon\Carbon::parse($this->dateFrom)->format('d-M-Y') : 'Beginning';
        $toStr = $this->dateTo ? \Carbon\Carbon::parse($this->dateTo)->format('d-M-Y') : 'Current';
        $periodLabel = ($this->dateFrom || $this->dateTo) ? "{$fromStr} to {$toStr}" : 'All Records';

        $totalSaleAmount = (clone $query)->sum('sale_amount');
        $totalCommissionAmount = (clone $query)->sum('commission_amount');
        $totalPaymentAmount = (clone $query)->sum('payment_amount');
        $closingBalance = $selectedVendor ? ((clone $query)->orderBy('transaction_date', 'desc')->orderBy('id', 'desc')->value('running_balance') ?? $selectedVendor->current_balance ?? 0) : 0;

        return view('livewire.vendor-ledger-view', [
            'ledgers' => $ledgers,
            'vendors' => $vendors,
            'selectedVendor' => $selectedVendor,
            'marquee' => $marquee,
            'periodLabel' => $periodLabel,
            'totalSaleAmount' => $totalSaleAmount,
            'totalCommissionAmount' => $totalCommissionAmount,
            'totalPaymentAmount' => $totalPaymentAmount,
            'closingBalance' => $closingBalance,
        ])->layout('layouts.admin');
    }
}
