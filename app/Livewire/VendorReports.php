<?php

namespace App\Livewire;

use App\Models\Marquee;
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

    public function getMarqueeId(): ?int
    {
        $user = auth()->user();
        $id = $user ? ($user->getActiveMarqueeId() ?: $user->marquee_id) : null;
        if (!$id && $user?->isSuperAdmin()) {
            return Marquee::first()?->id;
        }
        return $id;
    }

    public function mount()
    {
        $this->dateFrom = date('Y-m-01');
        $this->dateTo = date('Y-m-d');
        $this->year = date('Y');
        $this->month = date('m');
    }

    public function exportCsv()
    {
        $marqueeId = $this->getMarqueeId();
        $vendorName = !empty($this->vendor_id) ? (Vendor::withoutGlobalScope('tenant')->find($this->vendor_id)?->name ?? 'All_Providers') : 'All_Providers';
        $reportTypeTitle = ucfirst($this->reportType);
        $filename = "Provider_{$reportTypeTitle}_Report_" . preg_replace('/[^A-Za-z0-9_-]/', '_', $vendorName) . '_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($marqueeId, $vendorName) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF"); // UTF-8 BOM

            fputcsv($handle, ["SERVICE PROVIDER " . strtoupper($this->reportType) . " REPORT"]);
            fputcsv($handle, ['Provider / Vendor', $vendorName]);
            fputcsv($handle, ['Period', ($this->reportType === 'monthly' ? "{$this->year}-{$this->month}" : "{$this->dateFrom} to {$this->dateTo}")]);
            fputcsv($handle, ['Generated At', date('Y-m-d H:i:s')]);
            fputcsv($handle, []);

            if ($this->reportType === 'sales') {
                fputcsv($handle, ['Date', 'Sale #', 'Service Provider', 'Booking #', 'Customer Name', 'Service', 'Sale Amount (PKR)', 'Commission %', 'Commission Income (PKR)', 'Provider Net Payable (PKR)', 'Status']);
                $query = VendorSale::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->with(['vendor', 'service', 'booking.customer']);
                if (!empty($this->vendor_id)) $query->where('vendor_id', $this->vendor_id);
                if (!empty($this->dateFrom)) $query->whereDate('sale_date', '>=', $this->dateFrom);
                if (!empty($this->dateTo)) $query->whereDate('sale_date', '<=', $this->dateTo);
                foreach ($query->orderBy('sale_date', 'desc')->get() as $row) {
                    fputcsv($handle, [
                        $row->sale_date ? $row->sale_date->format('Y-m-d') : '—',
                        $row->vendor_sale_number,
                        $row->vendor?->name ?: '—',
                        $row->booking?->booking_number ?: 'Direct Sale',
                        $row->booking?->customer?->name ?: '—',
                        $row->service?->service_name ?: '—',
                        $row->sale_amount,
                        $row->commission_rate . '%',
                        $row->commission_amount,
                        $row->vendor_net_amount,
                        ucfirst($row->status),
                    ]);
                }
            } elseif ($this->reportType === 'commission') {
                fputcsv($handle, ['Date', 'Sale #', 'Service Provider', 'Booking #', 'Customer Name', 'Service', 'Sale Amount (PKR)', 'Commission %', 'Commission Income (PKR)', 'Provider Net Payable (PKR)']);
                $query = VendorSale::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->whereIn('status', ['confirmed', 'settled'])->with(['vendor', 'service', 'booking.customer']);
                if (!empty($this->vendor_id)) $query->where('vendor_id', $this->vendor_id);
                if (!empty($this->dateFrom)) $query->whereDate('sale_date', '>=', $this->dateFrom);
                if (!empty($this->dateTo)) $query->whereDate('sale_date', '<=', $this->dateTo);
                foreach ($query->orderBy('sale_date', 'desc')->get() as $row) {
                    fputcsv($handle, [
                        $row->sale_date ? $row->sale_date->format('Y-m-d') : '—',
                        $row->vendor_sale_number,
                        $row->vendor?->name ?: '—',
                        $row->booking?->booking_number ?: 'Direct Sale',
                        $row->booking?->customer?->name ?: '—',
                        $row->service?->service_name ?: '—',
                        $row->sale_amount,
                        $row->commission_rate . '%',
                        $row->commission_amount,
                        $row->vendor_net_amount,
                    ]);
                }
            } elseif ($this->reportType === 'ledger') {
                fputcsv($handle, ['Date', 'Ref #', 'Service Provider', 'Booking #', 'Customer Name', 'Description', 'Sale Credit (PKR)', 'Commission Debit (PKR)', 'Payout Amount (PKR)', 'Running Balance (PKR)']);
                $query = VendorLedger::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->with(['vendor', 'sale', 'booking.customer']);
                if (!empty($this->vendor_id)) $query->where('vendor_id', $this->vendor_id);
                if (!empty($this->dateFrom)) $query->whereDate('transaction_date', '>=', $this->dateFrom);
                if (!empty($this->dateTo)) $query->whereDate('transaction_date', '<=', $this->dateTo);
                foreach ($query->orderBy('transaction_date', 'desc')->orderBy('id', 'desc')->get() as $row) {
                    fputcsv($handle, [
                        $row->transaction_date ? $row->transaction_date->format('Y-m-d') : '—',
                        $row->reference_number ?: '—',
                        $row->vendor?->name ?: '—',
                        $row->booking?->booking_number ?: '—',
                        $row->booking?->customer?->name ?: '—',
                        $row->description,
                        $row->sale_amount > 0 ? $row->sale_amount : '0.00',
                        $row->commission_amount > 0 ? $row->commission_amount : '0.00',
                        $row->payment_amount > 0 ? $row->payment_amount : '0.00',
                        $row->running_balance,
                    ]);
                }
            } elseif ($this->reportType === 'settlement') {
                fputcsv($handle, ['Settlement Date', 'Settlement #', 'Service Provider', 'Net Payable (PKR)', 'Paid Amount (PKR)', 'Remaining Balance (PKR)', 'Payment Method', 'Disbursing Account', 'Status']);
                $query = VendorSettlement::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->with(['vendor', 'account']);
                if (!empty($this->vendor_id)) $query->where('vendor_id', $this->vendor_id);
                if (!empty($this->dateFrom)) $query->whereDate('settlement_date', '>=', $this->dateFrom);
                if (!empty($this->dateTo)) $query->whereDate('settlement_date', '<=', $this->dateTo);
                foreach ($query->orderBy('settlement_date', 'desc')->get() as $row) {
                    fputcsv($handle, [
                        $row->settlement_date ? $row->settlement_date->format('Y-m-d') : '—',
                        $row->settlement_number,
                        $row->vendor?->name ?: '—',
                        $row->net_payable_amount,
                        $row->paid_amount,
                        $row->remaining_balance,
                        $row->payment_method,
                        $row->account?->name ?: '—',
                        ucfirst($row->status),
                    ]);
                }
            } elseif ($this->reportType === 'monthly') {
                fputcsv($handle, ['Service Provider', 'Event Sales Count', 'Total Sales Amount (PKR)', 'Commission Income (PKR)', 'Net Provider Payable (PKR)']);
                $startDate = $this->year . '-' . str_pad($this->month, 2, '0', STR_PAD_LEFT) . '-01';
                $endDate = date('Y-m-t', strtotime($startDate));
                $query = VendorSale::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)
                    ->whereIn('status', ['confirmed', 'settled'])
                    ->whereBetween('sale_date', [$startDate, $endDate])
                    ->with(['vendor']);
                if (!empty($this->vendor_id)) $query->where('vendor_id', $this->vendor_id);
                $rows = $query->selectRaw('vendor_id, COUNT(*) as total_events, SUM(sale_amount) as total_sales, SUM(commission_amount) as total_commission, SUM(vendor_net_amount) as total_net_payable')
                    ->groupBy('vendor_id')
                    ->get();
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->vendor?->name ?: '—',
                        $row->total_events,
                        $row->total_sales,
                        $row->total_commission,
                        $row->total_net_payable,
                    ]);
                }
            }

            fclose($handle);
        }, 200, $headers);
    }

    public function render()
    {
        $marqueeId = $this->getMarqueeId();
        if (!empty($this->vendor_id)) {
            Vendor::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->findOrFail($this->vendor_id);
        }
        $vendors = Vendor::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->orderBy('name')->get();

        $data = [];

        if ($this->reportType === 'sales') {
            $query = VendorSale::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->with(['vendor', 'service', 'booking.customer']);
            if (!empty($this->vendor_id)) {
                $query->where('vendor_id', $this->vendor_id);
            }
            if (!empty($this->dateFrom)) {
                $query->whereDate('sale_date', '>=', $this->dateFrom);
            }
            if (!empty($this->dateTo)) {
                $query->whereDate('sale_date', '<=', $this->dateTo);
            }
            $data = $query->orderBy('sale_date', 'desc')->get();

        } elseif ($this->reportType === 'commission') {
            $query = VendorSale::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->whereIn('status', ['confirmed', 'settled'])->with(['vendor', 'service', 'booking.customer']);
            if (!empty($this->vendor_id)) {
                $query->where('vendor_id', $this->vendor_id);
            }
            if (!empty($this->dateFrom)) {
                $query->whereDate('sale_date', '>=', $this->dateFrom);
            }
            if (!empty($this->dateTo)) {
                $query->whereDate('sale_date', '<=', $this->dateTo);
            }
            $data = $query->orderBy('sale_date', 'desc')->get();

        } elseif ($this->reportType === 'ledger') {
            $query = VendorLedger::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->with(['vendor', 'sale', 'booking.customer']);
            if (!empty($this->vendor_id)) {
                $query->where('vendor_id', $this->vendor_id);
            }
            if (!empty($this->dateFrom)) {
                $query->whereDate('transaction_date', '>=', $this->dateFrom);
            }
            if (!empty($this->dateTo)) {
                $query->whereDate('transaction_date', '<=', $this->dateTo);
            }
            $data = $query->orderBy('transaction_date', 'desc')->orderBy('id', 'desc')->get();

        } elseif ($this->reportType === 'settlement') {
            $query = VendorSettlement::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->with(['vendor', 'account']);
            if (!empty($this->vendor_id)) {
                $query->where('vendor_id', $this->vendor_id);
            }
            if (!empty($this->dateFrom)) {
                $query->whereDate('settlement_date', '>=', $this->dateFrom);
            }
            if (!empty($this->dateTo)) {
                $query->whereDate('settlement_date', '<=', $this->dateTo);
            }
            $data = $query->orderBy('settlement_date', 'desc')->get();

        } elseif ($this->reportType === 'monthly') {
            $startDate = $this->year . '-' . str_pad($this->month, 2, '0', STR_PAD_LEFT) . '-01';
            $endDate = date('Y-m-t', strtotime($startDate));

            $query = VendorSale::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)
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

        $selectedVendor = !empty($this->vendor_id) ? $vendors->firstWhere('id', (int)$this->vendor_id) : null;
        $marquee = $marqueeId ? Marquee::find($marqueeId) : null;

        if ($this->reportType === 'monthly') {
            $periodLabel = \Carbon\Carbon::createFromDate((int)($this->year ?: date('Y')), (int)($this->month ?: date('m')), 1)->format('F Y');
        } else {
            $fromStr = $this->dateFrom ? \Carbon\Carbon::parse($this->dateFrom)->format('d-M-Y') : 'Beginning';
            $toStr = $this->dateTo ? \Carbon\Carbon::parse($this->dateTo)->format('d-M-Y') : 'Current';
            $periodLabel = "{$fromStr} to {$toStr}";
        }

        return view('livewire.vendor-reports', [
            'vendors' => $vendors,
            'selectedVendor' => $selectedVendor,
            'marquee' => $marquee,
            'periodLabel' => $periodLabel,
            'reportData' => $data,
        ])->layout('layouts.admin');
    }
}
