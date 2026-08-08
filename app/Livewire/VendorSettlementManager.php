<?php

namespace App\Livewire;

use App\Models\Account;
use App\Models\Vendor;
use App\Models\VendorSettlement;
use App\Services\VendorCommissionService;
use Livewire\Component;

class VendorSettlementManager extends Component
{
    public ?Vendor $vendor = null;

    // Settlement Modal State
    public $showSettlementModal = false;
    public $vendor_id = '';
    public $settlement_date = '';
    public $paid_amount = 0.00;
    public $payment_method = 'Cash';
    public $reference_number = '';
    public $account_id = null;
    public $remarks = '';

    public function mount(?Vendor $vendor = null)
    {
        $this->vendor = $vendor;
        if ($vendor) {
            $this->vendor_id = $vendor->id;
            $this->paid_amount = $vendor->current_balance;
        }
        $this->settlement_date = date('Y-m-d');
    }

    public function updatedVendorId($val)
    {
        if ($val) {
            $v = Vendor::find($val);
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

        $vendorObj = Vendor::findOrFail($this->vendor_id);
        if (floatval($this->paid_amount) > $vendorObj->current_balance) {
            $this->addError('paid_amount', 'Payout amount cannot exceed current vendor outstanding balance (Rs. ' . number_format($vendorObj->current_balance) . ').');
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
    }

    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;

        $query = VendorSettlement::where('marquee_id', $marqueeId)->with(['vendor', 'account', 'journalVoucher']);
        if ($this->vendor) {
            $query->where('vendor_id', $this->vendor->id);
        }

        $settlements = $query->orderBy('settlement_date', 'desc')->get();
        $vendors = Vendor::where('marquee_id', $marqueeId)->where('status', 'active')->orderBy('name')->get();
        $accounts = Account::where('marquee_id', $marqueeId)->where('is_active', true)->orderBy('name')->get();

        return view('livewire.vendor-settlement-manager', [
            'settlements' => $settlements,
            'vendors' => $vendors,
            'accounts' => $accounts,
        ])->layout('layouts.admin');
    }
}
