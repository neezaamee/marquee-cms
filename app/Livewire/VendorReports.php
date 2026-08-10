<?php

namespace App\Livewire;

use App\Models\Vendor;
use App\Models\VendorCommissionAgreement;
use App\Models\VendorLedger;
use App\Models\VendorSale;
use App\Models\VendorSettlement;
use Livewire\Component;

class VendorReports extends Component
{
    public $reportType = 'sales'; // sales, commission, ledger, settlement, monthly
    public $vendor_id = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $year = '';
    public $month = '';

    public function mount()
    {
        $this->dateFrom = date('Y-m-01');
        $this->dateTo = date('Y-m-d');
        $this->year = date('Y');
        $this->month = date('m');
    }

    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;
        if (!empty($this->vendor_id)) {
            Vendor::where('marquee_id', $marqueeId)->findOrFail($this->vendor_id);
        }
        $vendors = Vendor::where('marquee_id', $marqueeId)->orderBy('name')->get();

        $data = [];

        if ($this->reportType === 'sales') {
            $query = VendorSale::where('marquee_id', $marqueeId)->with(['vendor', 'service', 'booking.customer']);
            if (!empty($this->vendor_id)) {
                $query->where('vendor_id', $this->vendor_id);
            }
            if (!empty($this->dateFrom)) {
                $query->where('sale_date', '>=', $this->dateFrom);
            }
            if (!empty($this->dateTo)) {
                $query->where('sale_date', '<=', $this->dateTo);
            }
            $data = $query->orderBy('sale_date', 'desc')->get();

        } elseif ($this->reportType === 'commission') {
            $query = VendorSale::where('marquee_id', $marqueeId)->whereIn('status', ['confirmed', 'settled'])->with(['vendor', 'service', 'booking']);
            if (!empty($this->vendor_id)) {
                $query->where('vendor_id', $this->vendor_id);
            }
            if (!empty($this->dateFrom)) {
                $query->where('sale_date', '>=', $this->dateFrom);
            }
            if (!empty($this->dateTo)) {
                $query->where('sale_date', '<=', $this->dateTo);
            }
            $data = $query->orderBy('sale_date', 'desc')->get();

        } elseif ($this->reportType === 'ledger') {
            $query = VendorLedger::where('marquee_id', $marqueeId)->with(['vendor', 'sale', 'booking']);
            if (!empty($this->vendor_id)) {
                $query->where('vendor_id', $this->vendor_id);
            }
            if (!empty($this->dateFrom)) {
                $query->where('transaction_date', '>=', $this->dateFrom);
            }
            if (!empty($this->dateTo)) {
                $query->where('transaction_date', '<=', $this->dateTo);
            }
            $data = $query->orderBy('transaction_date', 'desc')->orderBy('id', 'desc')->get();

        } elseif ($this->reportType === 'settlement') {
            $query = VendorSettlement::where('marquee_id', $marqueeId)->with(['vendor', 'account']);
            if (!empty($this->vendor_id)) {
                $query->where('vendor_id', $this->vendor_id);
            }
            if (!empty($this->dateFrom)) {
                $query->where('settlement_date', '>=', $this->dateFrom);
            }
            if (!empty($this->dateTo)) {
                $query->where('settlement_date', '<=', $this->dateTo);
            }
            $data = $query->orderBy('settlement_date', 'desc')->get();

        } elseif ($this->reportType === 'monthly') {
            $startDate = $this->year . '-' . str_pad($this->month, 2, '0', STR_PAD_LEFT) . '-01';
            $endDate = date('Y-m-t', strtotime($startDate));

            $query = VendorSale::where('marquee_id', $marqueeId)
                ->whereIn('status', ['confirmed', 'settled'])
                ->whereBetween('sale_date', [$startDate, $endDate])
                ->with(['vendor']);

            if (!empty($this->vendor_id)) {
                $query->where('vendor_id', $this->vendor_id);
            }

            $data = $query->selectRaw('vendor_id, COUNT(*) as total_events, SUM(sale_amount) as total_sales, SUM(commission_amount) as total_commission, SUM(vendor_net_amount) as total_net_payable')
                ->groupBy('vendor_id')
                ->get();
        }

        return view('livewire.vendor-reports', [
            'vendors' => $vendors,
            'reportData' => $data,
        ])->layout('layouts.admin');
    }
}
