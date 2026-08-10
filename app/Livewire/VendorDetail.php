<?php

namespace App\Livewire;

use App\Models\Vendor;
use Livewire\Component;

class VendorDetail extends Component
{
    public Vendor $vendor;
    public $activeTab = 'overview';

    public function mount(Vendor $vendor)
    {
        // Tenant security check
        if (!auth()->user()->isSuperAdmin() && $vendor->marquee_id !== auth()->user()->marquee_id) {
            abort(403, 'Unauthorized access to this Service Provider.');
        }

        $this->vendor = $vendor;
    }

    public function setTab($tabName)
    {
        $this->activeTab = $tabName;
    }

    public function render()
    {
        $this->vendor->load(['services', 'agreements', 'sales.service', 'sales.booking', 'ledgers', 'settlements']);
        $activeAgreement = $this->vendor->activeAgreement;

        return view('livewire.vendor-detail', [
            'activeAgreement' => $activeAgreement,
        ])->layout('layouts.admin');
    }
}
