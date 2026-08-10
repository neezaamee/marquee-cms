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
    public $notes = '';

    public function mount(?Vendor $vendor = null)
    {
        if ($vendor && !auth()->user()->isSuperAdmin() && $vendor->marquee_id !== auth()->user()->marquee_id) {
            abort(403, 'Unauthorized access to this Service Provider.');
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
        $this->showSaleModal = true;
    }

    public function updatedVendorId($val)
    {
        $this->vendor_service_id = '';
    }

    public function updatedBookingId($val)
    {
        if ($val) {
            $marqueeId = auth()->user()->marquee_id;
            $booking = Booking::where('marquee_id', $marqueeId)->find($val);
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
            'event_date' => 'required|date',
            'sale_date' => 'required|date',
        ]);

        $marqueeId = auth()->user()->marquee_id;
        Vendor::where('marquee_id', $marqueeId)->findOrFail($this->vendor_id);

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
        $this->notes = '';
    }

    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;

        $query = VendorSale::where('marquee_id', $marqueeId)->with(['vendor', 'service', 'booking.customer']);

        if ($this->vendor) {
            $query->where('vendor_id', $this->vendor->id);
        } elseif (!empty($this->filterVendorId)) {
            $query->where('vendor_id', $this->filterVendorId);
        }

        if (!empty($this->search)) {
            $term = '%' . $this->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('vendor_sale_number', 'like', $term)
                  ->orWhereHas('vendor', function ($vq) use ($term) {
                      $vq->where('name', 'like', $term);
                  })
                  ->orWhereHas('booking', function ($bq) use ($term) {
                      $bq->where('booking_number', 'like', $term);
                  });
            });
        }

        $sales = $query->orderBy('sale_date', 'desc')->paginate(12);

        $vendors = Vendor::where('marquee_id', $marqueeId)->where('status', 'active')->orderBy('name')->get();
        $vendorServices = $this->vendor_id
            ? VendorService::where('marquee_id', $marqueeId)->where('vendor_id', $this->vendor_id)->where('status', 'active')->get()
            : collect();
        $bookings = Booking::where('marquee_id', $marqueeId)->with('customer')->orderBy('booking_date', 'desc')->take(30)->get();

        return view('livewire.vendor-sale-manager', [
            'sales' => $sales,
            'vendors' => $vendors,
            'vendorServices' => $vendorServices,
            'bookings' => $bookings,
        ])->layout('layouts.admin');
    }
}
