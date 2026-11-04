<?php

namespace App\Livewire;

use App\Models\Account;
use App\Models\Marquee;
use App\Models\Vendor;
use App\Models\VendorSettlement;
use App\Services\VendorCommissionService;
use Livewire\Component;
use Livewire\WithPagination;

class VendorSettlementManager extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public ?Vendor $vendor = null;

    // Filters & Pagination
    public $perPage = 15;
    public $filterVendorId = '';
    public $dateFrom = '';
    public $dateTo = '';

    public function updatingPerPage() { $this->resetPage(); }
    public function updatingFilterVendorId() { $this->resetPage(); }
    public function updatingDateFrom() { $this->resetPage(); }
    public function updatingDateTo() { $this->resetPage(); }

    // Settlement Modal State
    public $showSettlementModal = false;
    public $vendor_id = '';
    public $settlement_date = '';
    public $paid_amount = 0.00;
    public $payment_method = 'Cash';
    public $reference_number = '';
    public $account_id = null;
    public $remarks = '';

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
            $this->vendor_id = $vendor->id;
            $this->filterVendorId = $vendor->id;
            $this->paid_amount = $vendor->current_balance;
        }
        $this->settlement_date = date('Y-m-d');
    }

    public function updatedVendorId($val)
    {
        if ($val) {
            $marqueeId = $this->getMarqueeId();
            $v = Vendor::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->find($val);
            if ($v) {
                $this->paid_amount = $v->current_balance;
            }
        }
    }

    public function openCreateModal()
    {
        $this->resetForm();
        if ($this->vendor) {
            $this->vendor_id = $this->vendor->id;
            $this->paid_amount = $this->vendor->current_balance;
        }
        $this->settlement_date = date('Y-m-d');
        $this->showSettlementModal = true;
    }

    public function saveSettlement()
    {
        $this->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'paid_amount' => 'required|numeric|min:1',
            'settlement_date' => 'required|date',
            'payment_method' => 'required|string',
        ]);

        $marqueeId = $this->getMarqueeId();
        $vendorObj = Vendor::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->findOrFail($this->vendor_id);
        if (floatval($this->paid_amount) > $vendorObj->current_balance) {
            $this->addError('paid_amount', 'Payout amount cannot exceed current service provider outstanding balance (Rs. ' . number_format($vendorObj->current_balance) . ').');
            return;
        }

        $serviceEngine = app(VendorCommissionService::class);
        $serviceEngine->processSettlement($vendorObj, floatval($this->paid_amount), [
            'settlement_date' => $this->settlement_date,
            'payment_method' => $this->payment_method,
            'reference_number' => $this->reference_number ?: null,
            'account_id' => $this->account_id ?: null,
            'remarks' => $this->remarks,
        ]);

        $this->showSettlementModal = false;
        $this->resetForm();
        session()->flash('success', 'Vendor settlement payout processed successfully with ledger & accounting journal voucher.');
    }

    public function resetForm()
    {
        $this->paid_amount = 0.00;
        $this->payment_method = 'Cash';
        $this->reference_number = '';
        $this->account_id = null;
        $this->remarks = '';
        if ($this->vendor) {
            $this->vendor_id = $this->vendor->id;
            $this->paid_amount = $this->vendor->current_balance;
        } else {
            $this->vendor_id = '';
        }
    }

    public function exportCsv()
    {
        $marqueeId = $this->getMarqueeId();

        $query = VendorSettlement::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->with(['vendor', 'account', 'journalVoucher']);
        if ($this->vendor) {
            $query->where('vendor_id', $this->vendor->id);
        } elseif (!empty($this->filterVendorId)) {
            $query->where('vendor_id', $this->filterVendorId);
        }

        if (!empty($this->dateFrom)) {
            $query->whereDate('settlement_date', '>=', $this->dateFrom);
        }

        if (!empty($this->dateTo)) {
            $query->whereDate('settlement_date', '<=', $this->dateTo);
        }

        $settlements = $query->orderBy('settlement_date', 'desc')->get();

        $vendorName = $this->vendor ? $this->vendor->name : (!empty($this->filterVendorId) ? (Vendor::withoutGlobalScope('tenant')->find($this->filterVendorId)?->name ?? 'All_Providers') : 'All_Providers');
        $filename = 'Provider_Settlements_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $vendorName) . '_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($settlements, $vendorName) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel

            fputcsv($handle, ['SERVICE PROVIDER SETTLEMENTS & PAYOUTS']);
            fputcsv($handle, ['Provider / Vendor', $vendorName]);
            fputcsv($handle, ['Generated At', date('Y-m-d H:i:s')]);
            fputcsv($handle, []);

            fputcsv($handle, [
                'Settlement #',
                'Service Provider',
                'Settlement Date',
                'Net Payable (PKR)',
                'Paid Amount (PKR)',
                'Remaining Balance (PKR)',
                'Payment Method',
                'Disbursing Account',
                'Accounting JV',
                'Status',
            ]);

            foreach ($settlements as $set) {
                fputcsv($handle, [
                    $set->settlement_number,
                    $set->vendor?->name ?: '—',
                    $set->settlement_date ? $set->settlement_date->format('Y-m-d') : '—',
                    $set->net_payable_amount,
                    $set->paid_amount,
                    $set->remaining_balance,
                    $set->payment_method,
                    $set->account?->name ?: '—',
                    $set->journalVoucher?->voucher_no ?: '—',
                    ucfirst(str_replace('_', ' ', $set->status)),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    public function render()
    {
        $marqueeId = $this->getMarqueeId();

        $query = VendorSettlement::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->with(['vendor', 'account', 'journalVoucher']);
        if ($this->vendor) {
            $query->where('vendor_id', $this->vendor->id);
        } elseif (!empty($this->filterVendorId)) {
            $query->where('vendor_id', $this->filterVendorId);
        }

        if (!empty($this->dateFrom)) {
            $query->whereDate('settlement_date', '>=', $this->dateFrom);
        }

        if (!empty($this->dateTo)) {
            $query->whereDate('settlement_date', '<=', $this->dateTo);
        }

        $perPageCount = is_numeric($this->perPage) && (int)$this->perPage > 0 ? (int)$this->perPage : 15;
        $settlements = $query->orderBy('settlement_date', 'desc')->paginate($perPageCount);
        $vendors = Vendor::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->where('status', 'active')->orderBy('name')->get();
        $accounts = Account::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->where('is_active', true)->orderBy('name')->get();

        $selectedVendor = $this->vendor ?: (!empty($this->filterVendorId) ? $vendors->firstWhere('id', (int)$this->filterVendorId) : null);
        $marquee = $marqueeId ? Marquee::find($marqueeId) : null;

        $fromStr = $this->dateFrom ? \Carbon\Carbon::parse($this->dateFrom)->format('d-M-Y') : 'Beginning';
        $toStr = $this->dateTo ? \Carbon\Carbon::parse($this->dateTo)->format('d-M-Y') : 'Current';
        $periodLabel = ($this->dateFrom || $this->dateTo) ? "{$fromStr} to {$toStr}" : 'All Records';

        $totalNetPayable = (clone $query)->sum('net_payable_amount');
        $totalPaidAmount = (clone $query)->sum('paid_amount');
        $totalRemainingBalance = (clone $query)->sum('remaining_balance');

        return view('livewire.vendor-settlement-manager', [
            'settlements' => $settlements,
            'vendors' => $vendors,
            'accounts' => $accounts,
            'selectedVendor' => $selectedVendor,
            'marquee' => $marquee,
            'periodLabel' => $periodLabel,
            'totalNetPayable' => $totalNetPayable,
            'totalPaidAmount' => $totalPaidAmount,
            'totalRemainingBalance' => $totalRemainingBalance,
        ])->layout('layouts.admin');
    }
}
