<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Vendor;
use App\Models\VendorSale;
use App\Models\VendorService;
use App\Services\VendorCommissionService;
use Livewire\Component;
use Livewire\WithPagination;

class VendorSaleManager extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public ?Vendor $vendor = null;
    public $search = '';
    public $filterVendorId = '';
    public $filterStatus = '';

    // Sale Modal state
    public $showSaleModal = false;
    public $vendor_id = '';
    public $vendor_service_id = '';
    public $booking_id = '';
    public $customer_id = '';
    public $event_date = '';
    public $sale_date = '';
    public $quantity = 1;
    public $unit = 'Event';
    public $sale_amount = 0.00;
    public $commission_rate = null;
    public $advance_amount = 0.00;
    public $payment_method = 'Cash';
    public $account_id = null;
    public $reference_number = '';
    public $include_in_invoice = true;
    public $notes = '';

    public function getMarqueeId(): ?int
    {
        $user = auth()->user();
        $id = $user ? ($user->getActiveMarqueeId() ?: $user->marquee_id) : null;
        if (!$id && $user?->isSuperAdmin()) {
            return \App\Models\Marquee::first()?->id;
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
            $this->vendor_id = $vendor->id;
        }
        $this->sale_date = date('Y-m-d');
        $this->event_date = date('Y-m-d');
    }

    public function openCreateModal()
    {
        $this->resetForm();
        if ($this->vendor) {
            $this->vendor_id = $this->vendor->id;
        }
        $this->sale_date = date('Y-m-d');
        $this->event_date = date('Y-m-d');
        $this->include_in_invoice = true;
        $this->showSaleModal = true;
    }

    public function updatedVendorId($val)
    {
        $this->vendor_service_id = '';
    }

    public function updatedBookingId($val)
    {
        if ($val) {
            $marqueeId = $this->getMarqueeId();
            $booking = Booking::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->find($val);
            if ($booking) {
                $this->customer_id = $booking->customer_id;
                $this->event_date = $booking->booking_date->format('Y-m-d');
            }
        }
    }

    public function saveSale()
    {
        $this->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'sale_amount' => 'required|numeric|min:1',
            'advance_amount' => 'nullable|numeric|min:0',
            'event_date' => 'required|date',
            'sale_date' => 'required|date',
        ]);

        $marqueeId = $this->getMarqueeId();
        Vendor::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->findOrFail($this->vendor_id);

        $serviceEngine = app(VendorCommissionService::class);

        $serviceEngine->createVendorSale([
            'vendor_id' => $this->vendor_id,
            'vendor_service_id' => $this->vendor_service_id ?: null,
            'booking_id' => $this->booking_id ?: null,
            'customer_id' => $this->customer_id ?: null,
            'event_date' => $this->event_date,
            'sale_date' => $this->sale_date,
            'quantity' => floatval($this->quantity),
            'unit' => $this->unit,
            'sale_amount' => floatval($this->sale_amount),
            'commission_rate' => $this->commission_rate !== null && $this->commission_rate !== '' ? floatval($this->commission_rate) : null,
            'advance_amount' => floatval($this->advance_amount),
            'payment_method' => $this->payment_method,
            'account_id' => $this->account_id ?: null,
            'reference_number' => $this->reference_number ?: null,
            'include_in_invoice' => (bool) $this->include_in_invoice,
            'notes' => $this->notes,
        ]);

        $this->showSaleModal = false;
        $this->resetForm();
        session()->flash('success', 'Vendor sale recorded successfully with automated ledger and accounting entries.');
    }

    public function resetForm()
    {
        $this->vendor_service_id = '';
        $this->booking_id = '';
        $this->customer_id = '';
        $this->event_date = date('Y-m-d');
        $this->sale_date = date('Y-m-d');
        $this->quantity = 1;
        $this->unit = 'Event';
        $this->sale_amount = 0.00;
        $this->commission_rate = null;
        $this->advance_amount = 0.00;
        $this->payment_method = 'Cash';
        $this->account_id = null;
        $this->reference_number = '';
        $this->notes = '';
        if ($this->vendor) {
            $this->vendor_id = $this->vendor->id;
        } else {
            $this->vendor_id = '';
        }
    }

    public function render()
    {
        $marqueeId = $this->getMarqueeId();

        $query = VendorSale::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->with(['vendor', 'service', 'booking.customer']);

        if ($this->vendor) {
            $query->where('vendor_id', $this->vendor->id);
        } elseif (!empty($this->filterVendorId)) {
            $query->where('vendor_id', $this->filterVendorId);
        }

        if (!empty($this->filterStatus)) {
            $query->where('status', $this->filterStatus);
        }

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('vendor_sale_number', 'like', '%' . $this->search . '%')
                  ->orWhereHas('vendor', function($vq) {
                      $vq->where('name', 'like', '%' . $this->search . '%');
                  })
                  ->orWhereHas('booking', function($bq) {
                      $bq->where('booking_number', 'like', '%' . $this->search . '%');
                  });
            });
        }

        $sales = $query->orderBy('sale_date', 'desc')->orderBy('id', 'desc')->paginate(10);
        $vendors = Vendor::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->where('status', 'active')->orderBy('name')->get();
        $vendorServices = $this->vendor_id ? VendorService::withoutGlobalScope('tenant')->where('vendor_id', $this->vendor_id)->where('status', 'active')->get() : collect();
        $bookings = Booking::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->whereNotIn('booking_status', ['Cancelled'])->orderBy('booking_date', 'desc')->limit(30)->get();
        $accounts = \App\Models\Account::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->where('is_active', true)->orderBy('name')->get();

        return view('livewire.vendor-sale-manager', [
            'sales' => $sales,
            'vendors' => $vendors,
            'vendorServices' => $vendorServices,
            'bookings' => $bookings,
            'accounts' => $accounts,
        ])->layout('layouts.admin');
    }
}
