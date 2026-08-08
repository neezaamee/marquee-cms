<?php

namespace App\Livewire;

use App\Models\Vendor;
use App\Models\VendorLedger;
use Livewire\Component;

class VendorLedgerView extends Component
{
    public ?Vendor $vendor = null;
    public $filterVendorId = '';
    public $dateFrom = '';
    public $dateTo = '';

    public function mount(?Vendor $vendor = null)
    {
        $this->vendor = $vendor;
        if ($vendor) {
            $this->filterVendorId = $vendor->id;
        }
    }

    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;

        $query = VendorLedger::where('marquee_id', $marqueeId)->with(['vendor', 'sale', 'booking']);

        if ($this->vendor) {
            $query->where('vendor_id', $this->vendor->id);
        } elseif (!empty($this->filterVendorId)) {
            $query->where('vendor_id', $this->filterVendorId);
        }

        if (!empty($this->dateFrom)) {
            $query->where('transaction_date', '>=', $this->dateFrom);
        }

        if (!empty($this->dateTo)) {
            $query->where('transaction_date', '<=', $this->dateTo);
        }

        $ledgers = $query->orderBy('transaction_date', 'desc')->orderBy('id', 'desc')->get();
        $vendors = Vendor::where('marquee_id', $marqueeId)->orderBy('name')->get();

        return view('livewire.vendor-ledger-view', [
            'ledgers' => $ledgers,
            'vendors' => $vendors,
        ]);
    }
}
