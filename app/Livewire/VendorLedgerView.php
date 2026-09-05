<?php

namespace App\Livewire;

use App\Models\Marquee;
use App\Models\Vendor;
use App\Models\VendorLedger;
use Livewire\Component;

class VendorLedgerView extends Component
{
    public ?Vendor $vendor = null;
    public $filterVendorId = '';
    public $filterBookingId = '';
    public $dateFrom = '';
    public $dateTo = '';

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

    public function render()
    {
        $user = auth()->user();
        $marqueeId = $this->getMarqueeId();

        $query = VendorLedger::withoutGlobalScope('tenant')->with(['vendor', 'sale', 'booking']);

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
            $query->where('transaction_date', '>=', $this->dateFrom);
        }

        if (!empty($this->dateTo)) {
            $query->where('transaction_date', '<=', $this->dateTo);
        }

        $ledgers = $query->orderBy('transaction_date', 'desc')->orderBy('id', 'desc')->get();

        $vendorsQuery = Vendor::withoutGlobalScope('tenant');
        if ($marqueeId) {
            $vendorsQuery->where('marquee_id', $marqueeId);
        } elseif (!$user?->isSuperAdmin()) {
            $vendorsQuery->whereRaw('1 = 0');
        }
        $vendors = $vendorsQuery->orderBy('name')->get();

        return view('livewire.vendor-ledger-view', [
            'ledgers' => $ledgers,
            'vendors' => $vendors,
        ])->layout('layouts.admin');
    }
}
