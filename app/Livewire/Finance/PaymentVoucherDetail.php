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
        $user = auth()->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->hasPermission('manage_accounting')), 403, 'Unauthorized access to payment voucher details.');

        $voucher = PaymentVoucher::withoutGlobalScopes()->findOrFail($id);
        if (!$user->isSuperAdmin() && !$user->hasAccessToMarquee($voucher->marquee_id)) {
            abort(403, 'Unauthorized access to this payment voucher.');
        }

        $this->voucherId = $voucher->id;
        $this->disbursedDate = date('Y-m-d');
    }

    protected function getVoucher(): PaymentVoucher
    {
        $user = auth()->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->hasPermission('manage_accounting')), 403, 'Unauthorized.');

        $voucher = PaymentVoucher::withoutGlobalScopes()->findOrFail($this->voucherId);
        if (!$user->isSuperAdmin() && !$user->hasAccessToMarquee($voucher->marquee_id)) {
            abort(403, 'Unauthorized access to this payment voucher.');
        }

        return $voucher;
    }

    public function openDisburseModal()
    {
        $voucher = $this->getVoucher();
        if ($voucher->status === PaymentVoucher::STATUS_POSTED) {
            session()->flash('error', 'This voucher has already been disbursed.');
            return;
        }
        $this->showDisburseModal = true;
    }

    public function disburseAndPost()
    {
        $service = app(PaymentVoucherService::class);
        $voucher = $this->getVoucher();

        try {
            $service->disburseAndPost($voucher, auth()->id(), [
                'disbursed_date' => $this->disbursedDate ?: date('Y-m-d'),
                'notes' => $this->disbursementNotes,
            ]);

            \App\Models\ActivityLog::create([
                'user_id' => auth()->id(),
                'marquee_id' => $voucher->marquee_id,
                'action' => 'payment_voucher_disbursed',
                'description' => "Disbursed and posted Payment Voucher #{$voucher->voucher_no} to General Ledger",
                'model_type' => PaymentVoucher::class,
                'model_id' => $voucher->id,
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
        $voucher = $this->getVoucher();
        try {
            $service->approvePaymentVoucher($voucher, auth()->id());

            \App\Models\ActivityLog::create([
                'user_id' => auth()->id(),
                'marquee_id' => $voucher->marquee_id,
                'action' => 'payment_voucher_approved',
                'description' => "Approved Payment Voucher #{$voucher->voucher_no}",
                'model_type' => PaymentVoucher::class,
                'model_id' => $voucher->id,
            ]);

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
