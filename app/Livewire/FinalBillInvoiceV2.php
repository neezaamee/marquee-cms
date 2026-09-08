<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\CashBankAccount;
use Livewire\Component;

class FinalBillInvoiceV2 extends Component
{
    public Booking $booking;

    public function mount(Booking $booking)
    {
        $this->booking = $booking;
        $this->booking->loadMissing([
            'customer',
            'hall',
            'halls',
            'slot',
            'package',
            'eventType',
            'extraServices',
            'branch',
            'marquee',
            'hall.branch',
            'payments',
            'finalBill.extraServices',
            'finalBill.creator',
            'vendorSales.service',
            'vendorSales.vendor',
        ]);
    }

    public function render()
    {
        $isFinal = ! empty($this->booking->finalBill);
        $billing = $isFinal ? $this->booking->finalBill : $this->booking;

        // Extra services / add-ons list
        $addonsList = $billing->extraServices ?? collect();

        // Vendor sales services to be billed through invoice
        $vendorSalesList = $this->booking->vendorSales
            ? $this->booking->vendorSales->where('include_in_invoice', true)->whereIn('status', ['confirmed', 'settled'])
            : collect();

        // Payments total
        $totalPaid = $this->booking->payments
            ? $this->booking->payments->whereIn('status', ['posted', 'received'])->sum('amount')
            : 0;

        $grandTotal = (float) ($billing->grand_total ?? 0);
        $remainingBalance = max(0, $grandTotal - $totalPaid);

        // Effective tax rate calculation
        $taxRate = ($billing->subtotal > 0 && ! empty($billing->tax_amount))
            ? round(($billing->tax_amount / $billing->subtotal) * 100, 1)
            : 0;

        // Retrieve active bank accounts for customer direct transfer instructions
        $marqueeId = $this->booking->marquee_id;
        $bankAccounts = CashBankAccount::withoutGlobalScope('tenant')
            ->where('marquee_id', $marqueeId)
            ->where('status', 'active')
            ->where('type', 'bank')
            ->with('account')
            ->get();

        $branch = $this->booking->effective_branch ?? $this->booking->branch ?? ($this->booking->hall?->branch ?? null);

        // 1. Dynamic Project Invoice Number: uses branch invoice_prefix if set (e.g. INV-)
        $invoicePrefix = ($branch && ! empty($branch->invoice_prefix)) ? $branch->invoice_prefix : 'INV-';
        $projectInvoiceNumber = $invoicePrefix.str_pad($isFinal ? $billing->id : $this->booking->id, 6, '0', STR_PAD_LEFT);

        // Determine tax authority based on branch province
        $taxAuthority = 'FBR';
        if ($branch && in_array(strtolower($branch->province ?? ''), ['punjab'])) {
            $taxAuthority = 'PRA';
        } elseif ($branch && in_array(strtolower($branch->province ?? ''), ['sindh'])) {
            $taxAuthority = 'SRB';
        } elseif ($branch && in_array(strtolower($branch->province ?? ''), ['khyber pakhtunkhwa', 'kp'])) {
            $taxAuthority = 'KPRA';
        } elseif ($branch && in_array(strtolower($branch->province ?? ''), ['balochistan'])) {
            $taxAuthority = 'BRA';
        }

        // 2. Dynamic Tax Invoice Number: uses final bill fbr_invoice_number if available, or branch POS format
        $fbrInvoiceNumber = null;
        if ($isFinal && ! empty($this->booking->finalBill->fbr_invoice_number)) {
            $fbrInvoiceNumber = $this->booking->finalBill->fbr_invoice_number;
        } elseif (! empty($this->booking->fbr_invoice_number ?? null)) {
            $fbrInvoiceNumber = $this->booking->fbr_invoice_number;
        }

        if (empty($fbrInvoiceNumber)) {
            $posId = ($branch && ! empty($branch->fbr_pos_id)) ? $branch->fbr_pos_id : 'POS';
            $fbrInvoiceNumber = $taxAuthority.'-'.$posId.'-'.str_pad($isFinal ? $billing->id : $this->booking->id, 6, '0', STR_PAD_LEFT);
        }

        // QR Code data: if bill has stored qr_code, use it. Otherwise, format based on tax authority.
        $qrData = $billing->qr_code ?? ($taxAuthority === 'PRA' && $fbrInvoiceNumber ? "https://e.pra.punjab.gov.pk/VerifyInvoice?InvoiceNo={$fbrInvoiceNumber}" : $fbrInvoiceNumber);

        return view('livewire.final-bill-invoice-v2', [
            'booking' => $this->booking,
            'isFinal' => $isFinal,
            'billing' => $billing,
            'addonsList' => $addonsList,
            'vendorSalesList' => $vendorSalesList,
            'totalPaid' => $totalPaid,
            'grandTotal' => $grandTotal,
            'remainingBalance' => $remainingBalance,
            'taxRate' => $taxRate,
            'bankAccounts' => $bankAccounts,
            'projectInvoiceNumber' => $projectInvoiceNumber,
            'fbrInvoiceNumber' => $fbrInvoiceNumber,
            'taxAuthority' => $taxAuthority,
            'qrData' => $qrData,
        ]);
    }
}
