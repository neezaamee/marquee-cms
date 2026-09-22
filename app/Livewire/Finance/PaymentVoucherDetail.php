<?php

namespace App\Livewire\Finance;

use App\Models\PaymentVoucher;
use App\Services\PaymentVoucherService;
use Livewire\Component;

class PaymentVoucherDetail extends Component
{
    public $voucherId;
    public $showDisburseModal = false;
    public $disbursedDate = '';
    public $disbursementNotes = '';

    public function mount($id)
    {
        $this->voucherId = $id;
        $this->disbursedDate = date('Y-m-d');
    }

    public function openDisburseModal()
    {
        $voucher = PaymentVoucher::findOrFail($this->voucherId);
        if ($voucher->status === PaymentVoucher::STATUS_POSTED) {
            session()->flash('error', 'This voucher has already been disbursed.');
            return;
        }
        $this->showDisburseModal = true;
    }

    public function disburseAndPost()
    {
        $service = app(PaymentVoucherService::class);
        $voucher = PaymentVoucher::findOrFail($this->voucherId);

        try {
            $service->disburseAndPost($voucher, auth()->id(), [
                'disbursed_date' => $this->disbursedDate ?: date('Y-m-d'),
                'notes' => $this->disbursementNotes,
            ]);

            $this->showDisburseModal = false;
            session()->flash('success', "Payment Voucher {$voucher->voucher_no} disbursed and posted to General Ledger!");
        } catch (\Throwable $e) {
            session()->flash('error', 'Disbursement failed: ' . $e->getMessage());
        }
    }

    public function approveVoucher()
    {
        $service = app(PaymentVoucherService::class);
        $voucher = PaymentVoucher::findOrFail($this->voucherId);
        try {
            $service->approvePaymentVoucher($voucher, auth()->id());
            session()->flash('success', "Voucher approved successfully.");
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        $voucher = PaymentVoucher::with([
            'branch',
            'marquee',
            'financialYear',
            'cashBankAccount.account',
            'debitAccount',
            'journalVoucher.items.account',
            'supplier',
            'vendor',
            'expense',
            'preparedBy',
            'approvedBy',
            'disbursedBy'
        ])->findOrFail($this->voucherId);

        return view('livewire.finance.payment-voucher-detail', [
            'voucher' => $voucher,
            'showDisburseModal' => $this->showDisburseModal,
            'disbursedDate' => $this->disbursedDate,
            'disbursementNotes' => $this->disbursementNotes,
        ]);
    }
}
