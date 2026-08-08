<?php

namespace App\Livewire;

use App\Models\Vendor;
use App\Models\VendorService;
use Livewire\Component;

class VendorServiceManager extends Component
{
    public ?Vendor $vendor = null;

    // Modal state
    public $showServiceModal = false;
    public $serviceId = null;
    public $selectedVendorId = null;
    public $service_name = '';
    public $description = '';
    public $unit = 'Event';
    public $default_sale_price = 0.00;
    public $status = 'active';

    public function mount(?Vendor $vendor = null)
    {
        $this->vendor = $vendor;
        if ($vendor) {
            $this->selectedVendorId = $vendor->id;
        }
    }

    public function openCreateModal()
    {
        $this->resetForm();
        if ($this->vendor) {
            $this->selectedVendorId = $this->vendor->id;
        }
        $this->showServiceModal = true;
    }

    public function editService($id)
    {
        $service = VendorService::findOrFail($id);
        $this->serviceId = $service->id;
        $this->selectedVendorId = $service->vendor_id;
        $this->service_name = $service->service_name;
        $this->description = $service->description;
        $this->unit = $service->unit;
        $this->default_sale_price = $service->default_sale_price;
        $this->status = $service->status;

        $this->showServiceModal = true;
    }

    public function saveService()
    {
        $this->validate([
            'selectedVendorId' => 'required|exists:vendors,id',
            'service_name' => 'required|string|max:255',
            'unit' => 'required|string',
            'default_sale_price' => 'required|numeric|min:0',
            'status' => 'required|string|in:active,inactive',
        ]);

        $marqueeId = auth()->user()->marquee_id;

        VendorService::updateOrCreate(
            ['id' => $this->serviceId, 'marquee_id' => $marqueeId],
            [
                'vendor_id' => $this->selectedVendorId,
                'service_name' => $this->service_name,
                'description' => $this->description,
                'unit' => $this->unit,
                'default_sale_price' => floatval($this->default_sale_price),
                'status' => $this->status,
            ]
        );

        $this->showServiceModal = false;
        $this->resetForm();
        session()->flash('success', 'Vendor service saved successfully.');
    }

    public function resetForm()
    {
        $this->serviceId = null;
        $this->service_name = '';
        $this->description = '';
        $this->unit = 'Event';
        $this->default_sale_price = 0.00;
        $this->status = 'active';
    }

    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;

        $query = VendorService::where('marquee_id', $marqueeId)->with('vendor');
        if ($this->vendor) {
            $query->where('vendor_id', $this->vendor->id);
        }

        $services = $query->orderBy('service_name')->get();
        $vendors = Vendor::where('marquee_id', $marqueeId)->where('status', 'active')->orderBy('name')->get();

        return view('livewire.vendor-service-manager', [
            'services' => $services,
            'vendors' => $vendors,
        ])->layout('layouts.admin');
    }
}
