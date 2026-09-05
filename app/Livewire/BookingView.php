<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\BookingHistory;
use Livewire\Component;

class BookingView extends Component
{
    public Booking $booking;
    
    // Quick Payment Record Modal State
    public $showPaymentModal = false;
    public $amountPaid = 0.00;
    public $paymentDate = '';
    public $paymentMethod = 'Cash';
    public $paymentAccountId = null;
    public $paymentType = 'advance';
    public $transactionReference = '';
    public $chequeNumber = '';
    public $bankReference = '';
    public $paymentNote = '';

    // Accountant Post Modal State
    public $showAccountantPostModal = false;
    public $bvPostingPaymentId = null;
    public $bvTargetAccountId = '';
    public $bvPostingDate = '';
    public $bvAccountantNotes = '';

    // Booking Cancellation Modal State
    public $showBookingCancelModal = false;
    public $bkCancelRefundAmount = 0.00;
    public $bkCancelFeeAmount = 0.00;
    public $bkCancelReason = '';
    public $bkCancelPaymentMethod = 'Cash';
    public $bkCancelAccountId = null;

    // Deposit Processing Modal State
    public $showDepositModal = false;
    public $depositAction = 'refund_full';
    public $depositRefundedAmount = 0.00;
    public $depositDeductedAmount = 0.00;
    public $depositNotes = '';

    // Guest Confirmation Modal State
    public $showGuestModal = false;
    public $modalTentativeGuests = 100;
    public $modalConfirmedGuests = null;
    public $modalGuestStatus = 'Tentative';

    // Event Day Final Bill Modal State
    public $showFinalBillModal = false;
    public $fbGuestCount = 0;
    public $fbPerPlatePrice = 0.00;
    public $fbHallCharges = 0.00;
    public $fbExtraCharges = 0.00;
    public $fbDiscountAmount = 0.00;
    public $fbTaxAmount = 0.00;
    public $fbVendorCharges = 0.00;
    public $fbNotes = '';
    public $fbAddonsList = []; // Array of ['service_name' => string, 'unit_price' => float, 'quantity' => int, 'total_price' => float]
    public $newAddonName = '';
    public $newAddonPrice = 0.00;
    public $newAddonQty = 1;

    // Kitchen Slip Modal State
    public $showKitchenSlipModal = false;
    public $kitchenLang = 'bilingual';
    public $kitchenInstructions = '';

    // Vendor Service Sale Modal State (Create)
    public $showVendorSaleModal = false;
    public $vsVendorId = '';
    public $vsServiceId = '';
    public $vsCustomerCharge = 0.00;
    public $vsCustomerAdvance = 0.00;
    public $vsCustomerPaymentMethod = 'Cash';
    public $vsCustomerReference = '';
    public $vsVendorCost = 0.00;
    public $vsCommissionRate = null;
    public $vsAdvanceAmount = 0.00;
    public $vsPaymentMethod = 'Cash';
    public $vsAccountId = null;
    public $vsReference = '';
    public $vsNotes = '';
    public $vsIncludeInInvoice = true;

    // Customer Subsequent Advance Installment Modal State
    public $showCustomerPaymentModal = false;
    public $cpSaleId = '';
    public $cpVendorName = '';
    public $cpServiceName = '';
    public $cpCustomerCharge = 0.00;
    public $cpCustomerPaid = 0.00;
    public $cpCustomerRemaining = 0.00;
    public $cpPaymentAmount = 0.00;
    public $cpPaymentDate = '';
    public $cpPaymentMethod = 'Cash';
    public $cpReference = '';
    public $cpNotes = '';

    // Vendor Subsequent Installment Modal State
    public $showVendorPaymentModal = false;
    public $vpSaleId = '';
    public $vpVendorName = '';
    public $vpServiceName = '';
    public $vpRemainingBalance = 0.00;
    public $vpPaymentAmount = 0.00;
    public $vpPaymentDate = '';
    public $vpPaymentMethod = 'Cash';
    public $vpReference = '';
    public $vpAccountId = null;
    public $vpRemarks = '';

    // Vendor View Modal State
    public $showVendorViewModal = false;
    public $viewingVendorSaleId = null;

    // Vendor Edit Modal State
    public $showVendorEditModal = false;
    public $veSaleId = null;
    public $veVendorName = '';
    public $veServiceName = '';
    public $veCustomerCharge = 0.00;
    public $veVendorCost = 0.00;
    public $veCommissionRate = null;
    public $veIncludeInInvoice = true;
    public $veNotes = '';

    // Vendor Delete / Cancel Confirmation Modal State
    public $showVendorDeleteModal = false;
    public $deletingVendorSaleId = null;
    public $cancelReason = '';

    public function mount(Booking $booking)
    {
        $this->booking = $booking;
        $this->paymentDate = date('Y-m-d');
        $this->vpPaymentDate = date('Y-m-d');
        $this->cpPaymentDate = date('Y-m-d');
    }

    public function getMarqueeId(): ?int
    {
        $user = auth()->user();
        return $this->booking->marquee_id
            ?: ($user ? ($user->getActiveMarqueeId() ?: $user->marquee_id) : null);
    }

    public function openVendorSaleModal()
    {
        $this->vsVendorId = '';
        $this->vsServiceId = '';
        $this->vsCustomerCharge = 0.00;
        $this->vsCustomerAdvance = 0.00;
        $this->vsCustomerPaymentMethod = 'Cash';
        $this->vsCustomerReference = '';
        $this->vsVendorCost = 0.00;
        $this->vsCommissionRate = null;
        $this->vsAdvanceAmount = 0.00;
        $this->vsPaymentMethod = 'Cash';
        $this->vsAccountId = null;
        $this->vsReference = '';
        $this->vsNotes = '';
        $this->vsIncludeInInvoice = true;
        $this->showVendorSaleModal = true;
    }

    public function updatedVsVendorId($val)
    {
        $this->vsServiceId = '';
        $this->recalculateVendorCosts();
    }

    public function updatedVsServiceId($val)
    {
        if ($val) {
            $service = \App\Models\VendorService::withoutGlobalScope('tenant')->find($val);
            if ($service && floatval($service->default_sale_price) > 0) {
                $this->vsCustomerCharge = (float) $service->default_sale_price;
            }
        }
        $this->recalculateVendorCosts();
    }

    public function updatedVsCustomerCharge($val)
    {
        $this->recalculateVendorCosts();
    }

    public function updatedVsCommissionRate($val)
    {
        $this->recalculateVendorCosts();
    }

    protected function recalculateVendorCosts()
    {
        if (!$this->vsVendorId) {
            return;
        }

        $vendor = \App\Models\Vendor::withoutGlobalScope('tenant')->find($this->vsVendorId);
        if (!$vendor) return;

        $service = $this->vsServiceId ? \App\Models\VendorService::withoutGlobalScope('tenant')->find($this->vsServiceId) : null;
        $serviceEngine = app(\App\Services\VendorCommissionService::class);
        $bookingDate = $this->booking->booking_date
            ? ($this->booking->booking_date instanceof \DateTimeInterface
                ? $this->booking->booking_date->format('Y-m-d')
                : \Carbon\Carbon::parse($this->booking->booking_date)->format('Y-m-d'))
            : date('Y-m-d');
        $agreement = $serviceEngine->resolveAgreement($vendor, $service, $bookingDate);
        
        $customerCharge = floatval($this->vsCustomerCharge);
        $calc = $serviceEngine->calculateCommission(
            $agreement,
            $customerCharge,
            $this->vsCommissionRate !== null && $this->vsCommissionRate !== '' ? floatval($this->vsCommissionRate) : null
        );

        $this->vsVendorCost = $calc['vendor_net_amount'];
        if ($this->vsCommissionRate === null || $this->vsCommissionRate === '') {
            $this->vsCommissionRate = $calc['commission_rate'];
        }
    }

    public function saveBookingVendorSale()
    {
        $this->validate([
            'vsVendorId' => 'required|exists:vendors,id',
            'vsCustomerCharge' => 'required|numeric|min:1',
            'vsCustomerAdvance' => 'required|numeric|min:0',
            'vsVendorCost' => 'required|numeric|min:0',
            'vsAdvanceAmount' => 'nullable|numeric|min:0',
        ]);

        if (floatval($this->vsCustomerAdvance) > floatval($this->vsCustomerCharge)) {
            $this->addError('vsCustomerAdvance', 'Customer advance cannot exceed total customer charge (Rs. ' . number_format($this->vsCustomerCharge, 2) . ').');
            return;
        }

        if (floatval($this->vsAdvanceAmount) > floatval($this->vsVendorCost)) {
            $this->addError('vsAdvanceAmount', 'Advance paid to vendor cannot exceed total Vendor Cost (Rs. ' . number_format($this->vsVendorCost, 2) . ').');
            return;
        }

        $eventDate = $this->booking->booking_date
            ? ($this->booking->booking_date instanceof \DateTimeInterface
                ? $this->booking->booking_date->format('Y-m-d')
                : \Carbon\Carbon::parse($this->booking->booking_date)->format('Y-m-d'))
            : date('Y-m-d');

        $serviceEngine = app(\App\Services\VendorCommissionService::class);
        $sale = $serviceEngine->createVendorSale([
            'vendor_id' => $this->vsVendorId,
            'vendor_service_id' => $this->vsServiceId ?: null,
            'booking_id' => $this->booking->id,
            'customer_id' => $this->booking->customer_id,
            'branch_id' => $this->booking->branch_id,
            'event_date' => $eventDate,
            'sale_date' => date('Y-m-d'),
            'quantity' => 1,
            'unit' => 'Event',
            'sale_amount' => floatval($this->vsCustomerCharge),
            'customer_advance_amount' => floatval($this->vsCustomerAdvance),
            'customer_payment_method' => $this->vsCustomerPaymentMethod,
            'customer_payment_reference' => $this->vsCustomerReference ?: null,
            'commission_rate' => $this->vsCommissionRate !== null && $this->vsCommissionRate !== '' ? floatval($this->vsCommissionRate) : null,
            'advance_amount' => floatval($this->vsAdvanceAmount),
            'payment_method' => $this->vsPaymentMethod,
            'account_id' => $this->vsAccountId ?: null,
            'reference_number' => $this->vsReference ?: null,
            'include_in_invoice' => (bool) $this->vsIncludeInInvoice,
            'notes' => $this->vsNotes,
        ]);

        $this->booking->refresh();
        $this->showVendorSaleModal = false;
        session()->flash('success', 'Service Provider assigned to booking successfully. Customer advance, vendor cost, and ledger balance recorded.');
    }

    public function openCustomerPaymentModal($saleId)
    {
        $sale = \App\Models\VendorSale::with(['vendor', 'service'])->findOrFail($saleId);
        $this->cpSaleId = $sale->id;
        $this->cpVendorName = $sale->vendor->name;
        $this->cpServiceName = $sale->service?->service_name ?? 'Vendor Service';
        $this->cpCustomerCharge = (float) $sale->sale_amount;
        $this->cpCustomerPaid = (float) $sale->customer_paid;
        $this->cpCustomerRemaining = (float) $sale->customer_remaining;
        $this->cpPaymentAmount = (float) $sale->customer_remaining;
        $this->cpPaymentDate = date('Y-m-d');
        $this->cpPaymentMethod = 'Cash';
        $this->cpReference = '';
        $this->cpNotes = '';
        $this->showCustomerPaymentModal = true;
    }

    public function recordCustomerAdvancePayment()
    {
        $this->validate([
            'cpPaymentAmount' => 'required|numeric|min:1',
            'cpPaymentDate' => 'required|date',
            'cpPaymentMethod' => 'required|string',
        ]);

        $sale = \App\Models\VendorSale::findOrFail($this->cpSaleId);
        if (floatval($this->cpPaymentAmount) > floatval($sale->customer_remaining)) {
            $this->addError('cpPaymentAmount', 'Payment amount cannot exceed remaining customer balance (Rs. ' . number_format($sale->customer_remaining, 2) . ').');
            return;
        }

        $serviceEngine = app(\App\Services\VendorCommissionService::class);
        $serviceEngine->recordCustomerSalePayment($sale, floatval($this->cpPaymentAmount), [
            'payment_date' => $this->cpPaymentDate,
            'payment_method' => $this->cpPaymentMethod,
            'transaction_reference' => $this->cpReference ?: null,
            'notes' => $this->cpNotes ?: ("Customer advance payment for " . ($sale->service?->service_name ?? $sale->vendor?->name ?? 'Vendor Service')),
        ]);

        $this->booking->refresh();
        $this->showCustomerPaymentModal = false;
        session()->flash('success', 'Customer advance payment recorded successfully.');
    }

    public function openVendorViewModal($saleId)
    {
        $this->viewingVendorSaleId = $saleId;
        $this->showVendorViewModal = true;
    }

    public function openVendorEditModal($saleId)
    {
        $sale = \App\Models\VendorSale::with(['vendor', 'service'])->findOrFail($saleId);
        $this->veSaleId = $sale->id;
        $this->veVendorName = $sale->vendor->name;
        $this->veServiceName = $sale->service?->service_name ?? 'Custom Service';
        $this->veCustomerCharge = (float) $sale->sale_amount;
        $this->veVendorCost = (float) $sale->vendor_net_amount;
        $this->veCommissionRate = (float) $sale->commission_rate;
        $this->veIncludeInInvoice = (bool) $sale->include_in_invoice;
        $this->veNotes = $sale->notes ?? '';
        $this->showVendorEditModal = true;
    }

    public function updatedVeCustomerCharge($val)
    {
        $this->recalculateEditVendorCosts();
    }

    public function updatedVeCommissionRate($val)
    {
        $this->recalculateEditVendorCosts();
    }

    protected function recalculateEditVendorCosts()
    {
        $sale = \App\Models\VendorSale::find($this->veSaleId);
        if (!$sale) return;

        $customerCharge = floatval($this->veCustomerCharge);
        $rate = $this->veCommissionRate !== null && $this->veCommissionRate !== '' ? floatval($this->veCommissionRate) : (float) $sale->commission_rate;
        $commAmount = $customerCharge * ($rate / 100);
        $this->veVendorCost = max(0.00, $customerCharge - $commAmount);
    }

    public function saveEditedVendorSale()
    {
        $this->validate([
            'veCustomerCharge' => 'required|numeric|min:1',
            'veVendorCost' => 'required|numeric|min:0',
        ]);

        $sale = \App\Models\VendorSale::findOrFail($this->veSaleId);
        $serviceEngine = app(\App\Services\VendorCommissionService::class);

        $serviceEngine->updateVendorSale($sale, [
            'sale_amount' => floatval($this->veCustomerCharge),
            'vendor_cost' => floatval($this->veVendorCost),
            'commission_rate' => $this->veCommissionRate !== null && $this->veCommissionRate !== '' ? floatval($this->veCommissionRate) : null,
            'include_in_invoice' => (bool) $this->veIncludeInInvoice,
            'notes' => $this->veNotes,
        ]);

        $this->booking->refresh();
        $this->showVendorEditModal = false;
        session()->flash('success', 'Service Provider details updated successfully.');
    }

    public function confirmCancelVendorSale($saleId)
    {
        $this->deletingVendorSaleId = $saleId;
        $this->cancelReason = '';
        $this->showVendorDeleteModal = true;
    }

    public function executeDeleteOrCancelVendorSale()
    {
        $sale = \App\Models\VendorSale::findOrFail($this->deletingVendorSaleId);
        $serviceEngine = app(\App\Services\VendorCommissionService::class);

        if ((float) $sale->paid_amount > 0) {
            $serviceEngine->cancelVendorSale($sale, $this->cancelReason ?: 'Cancelled from booking view');
            session()->flash('warning', 'Vendor service cancelled. Unpaid payable obligations reversed on ledger.');
        } else {
            $serviceEngine->deleteVendorSale($sale);
            session()->flash('success', 'Vendor service removed from booking successfully.');
        }

        $this->booking->refresh();
        $this->showVendorDeleteModal = false;
    }

    public function openVendorPaymentModal($saleId)
    {
        $sale = \App\Models\VendorSale::with(['vendor', 'service'])->findOrFail($saleId);
        $this->vpSaleId = $sale->id;
        $this->vpVendorName = $sale->vendor->name;
        $this->vpServiceName = $sale->service?->service_name ?? 'Vendor Service';
        $this->vpRemainingBalance = (float) $sale->remaining_amount;
        $this->vpPaymentAmount = (float) $sale->remaining_amount;
        $this->vpPaymentDate = date('Y-m-d');
        $this->vpPaymentMethod = 'Cash';
        $this->vpReference = '';
        $this->vpAccountId = null;
        $this->vpRemarks = '';
        $this->showVendorPaymentModal = true;
    }

    public function recordVendorInstallmentPayment()
    {
        $this->validate([
            'vpPaymentAmount' => 'required|numeric|min:1',
            'vpPaymentDate' => 'required|date',
            'vpPaymentMethod' => 'required|string',
        ]);

        $sale = \App\Models\VendorSale::findOrFail($this->vpSaleId);
        if (floatval($this->vpPaymentAmount) > floatval($sale->remaining_amount)) {
            $this->addError('vpPaymentAmount', 'Payment amount cannot exceed remaining payable balance (Rs. ' . number_format($sale->remaining_amount, 2) . ').');
            return;
        }

        $serviceEngine = app(\App\Services\VendorCommissionService::class);
        $serviceEngine->recordVendorSalePayment($sale, floatval($this->vpPaymentAmount), [
            'payment_date' => $this->vpPaymentDate,
            'payment_method' => $this->vpPaymentMethod,
            'reference_number' => $this->vpReference ?: null,
            'account_id' => $this->vpAccountId ?: null,
            'remarks' => $this->vpRemarks ?: 'Installment payment against booking',
        ]);

        $this->booking->refresh();
        $this->showVendorPaymentModal = false;
        session()->flash('success', 'Vendor payment installment recorded successfully with ledger & accounting voucher.');
    }

    /**
     * Open Quick Payment Record Modal with smart defaults.
     */
    public function openPaymentModal()
    {
        $this->paymentDate = date('Y-m-d');
        $this->paymentMethod = 'Cash';
        $this->paymentAccountId = null;
        $this->transactionReference = '';
        $this->chequeNumber = '';
        $this->bankReference = '';
        $this->paymentNote = '';

        if ($this->booking->is_revenue_recognized) {
            $this->paymentType = 'receivable_payment';
            $this->amountPaid = (float) $this->booking->effective_receivable;
        } else {
            $this->paymentType = 'advance';
            $remainingToPay = max(0.00, (float) $this->booking->effective_invoice_amount - (float) $this->booking->total_paid);
            $this->amountPaid = $remainingToPay;
        }

        $this->showPaymentModal = true;
    }

    /**
     * Record a payment transaction (Stage 1: Manager Entry).
     * Saved as Pending Posting without direct Cash/Bank or Journal Voucher impact.
     */
    public function recordPayment()
    {
        $this->validate([
            'amountPaid' => 'required|numeric|min:1',
            'paymentDate' => 'required|date',
            'paymentMethod' => 'required|string',
            'paymentAccountId' => 'nullable|exists:accounts,id',
            'transactionReference' => 'nullable|string|max:255',
            'chequeNumber' => 'nullable|string|max:100',
            'bankReference' => 'nullable|string|max:100',
            'paymentNote' => 'nullable|string|max:255',
        ]);

        $financialService = app(\App\Services\BookingFinancialService::class);

        $payment = $financialService->recordPayment($this->booking, [
            'amount' => floatval($this->amountPaid),
            'payment_date' => $this->paymentDate,
            'payment_method' => $this->paymentMethod,
            'account_id' => $this->paymentAccountId ?: null,
            'payment_type' => $this->booking->is_revenue_recognized ? 'receivable_payment' : 'advance',
            'transaction_reference' => $this->transactionReference ?: null,
            'cheque_number' => $this->chequeNumber ?: null,
            'bank_reference' => $this->bankReference ?: null,
            'notes' => $this->paymentNote ?: null,
            'recorded_by' => auth()->id(),
        ]);

        $this->booking->refresh();
        $this->amountPaid = 0.00;
        $this->paymentNote = '';
        $this->transactionReference = '';
        $this->chequeNumber = '';
        $this->bankReference = '';
        $this->paymentDate = date('Y-m-d');
        $this->paymentAccountId = null;
        $this->showPaymentModal = false;

        session()->flash('success', "Payment #{$payment->payment_number} of Rs. " . number_format($payment->amount, 2) . " received and submitted for accountant posting.");
    }

    /**
     * Open Accountant Post Modal for a specific payment.
     */
    public function openAccountantPostModal($paymentId)
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->isBusinessOwner() && !$user->hasPermission('post_payments')) {
            session()->flash('error', 'You are not authorized to post payments to financial accounts.');
            return;
        }

        $payment = \App\Models\BookingPayment::findOrFail($paymentId);
        if (!$payment->isPendingPosting()) {
            session()->flash('error', 'This payment is not awaiting posting.');
            return;
        }

        $this->bvPostingPaymentId = $payment->id;
        $this->bvPostingDate = date('Y-m-d');
        $this->bvAccountantNotes = '';

        $marqueeId = $this->getMarqueeId();
        $targetCode = (strtolower($payment->payment_method) === 'cash') ? '1001' : '1002';
        $defaultAcc = \App\Models\Account::withoutGlobalScope('tenant')
            ->where('marquee_id', $marqueeId)
            ->where('account_code', $targetCode)
            ->first();

        $this->bvTargetAccountId = $payment->account_id ?: ($defaultAcc?->id ?? '');
        $this->showAccountantPostModal = true;
    }

    /**
     * Confirm Accountant Post Payment.
     */
    public function confirmAccountantPostPayment()
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->isBusinessOwner() && !$user->hasPermission('post_payments')) {
            session()->flash('error', 'Unauthorized.');
            return;
        }

        $this->validate([
            'bvPostingPaymentId' => 'required|exists:booking_payments,id',
            'bvTargetAccountId' => 'required|exists:accounts,id',
            'bvPostingDate' => 'required|date',
            'bvAccountantNotes' => 'nullable|string|max:500',
        ]);

        try {
            $financialService = app(\App\Services\BookingFinancialService::class);
            $payment = \App\Models\BookingPayment::findOrFail($this->bvPostingPaymentId);
            $financialService->postPayment($payment, [
                'account_id' => (int) $this->bvTargetAccountId,
                'posting_date' => $this->bvPostingDate,
                'accountant_notes' => $this->bvAccountantNotes,
                'posted_by' => $user->id,
            ]);

            $this->booking->refresh();
            $this->showAccountantPostModal = false;
            $this->bvPostingPaymentId = null;
            session()->flash('success', "Payment #{$payment->payment_number} posted successfully into financial accounts.");
        } catch (\Exception $e) {
            session()->flash('error', 'Posting failed: ' . $e->getMessage());
        }
    }

    /**
     * Open final bill modal and load original details as template.
     */
    public function openFinalBillModal()
    {
        $booking = $this->booking;
        
        if ($booking->finalBill) {
            $this->fbGuestCount = $booking->finalBill->guest_count;
            $this->fbPerPlatePrice = $booking->finalBill->per_plate_price;
            $this->fbHallCharges = $booking->finalBill->hall_charges;
            $this->fbDiscountAmount = $booking->finalBill->discount_amount;
            $this->fbNotes = $booking->finalBill->notes;
            
            $this->fbAddonsList = [];
            foreach ($booking->finalBill->extraServices as $addon) {
                $this->fbAddonsList[] = [
                    'service_name' => $addon->service_name,
                    'unit_price' => $addon->unit_price,
                    'quantity' => $addon->quantity,
                    'total_price' => $addon->total_price,
                ];
            }
        } else {
            $this->fbGuestCount = $booking->guest_count;
            $this->fbPerPlatePrice = $booking->per_plate_price;
            $this->fbHallCharges = $booking->hall_charges;
            $this->fbDiscountAmount = $booking->discount_amount;
            $this->fbNotes = '';
            
            $this->fbAddonsList = [];
            foreach ($booking->extraServices as $addon) {
                $this->fbAddonsList[] = [
                    'service_name' => $addon->service_name,
                    'unit_price' => $addon->unit_price,
                    'quantity' => $addon->quantity,
                    'total_price' => $addon->total_price,
                ];
            }
        }
        
        $this->recalculateFinalBill();
        $this->showFinalBillModal = true;
    }

    /**
     * Recalculate billing values of final bill.
     */
    public function recalculateFinalBill()
    {
        $this->fbGuestCount = is_numeric($this->fbGuestCount) ? intval($this->fbGuestCount) : 0;
        $this->fbPerPlatePrice = is_numeric($this->fbPerPlatePrice) ? floatval($this->fbPerPlatePrice) : 0.00;
        $this->fbHallCharges = is_numeric($this->fbHallCharges) ? floatval($this->fbHallCharges) : 0.00;
        $this->fbDiscountAmount = is_numeric($this->fbDiscountAmount) ? floatval($this->fbDiscountAmount) : 0.00;

        $packageAmount = $this->booking->no_food ? 0.00 : ($this->fbGuestCount * $this->fbPerPlatePrice);
        
        $addonsSum = 0.00;
        foreach ($this->fbAddonsList as &$addon) {
            $addon['total_price'] = floatval($addon['unit_price']) * intval($addon['quantity']);
            $addonsSum += $addon['total_price'];
        }
        $this->fbExtraCharges = $addonsSum;

        $vendorCharges = (float) \App\Models\VendorSale::where('booking_id', $this->booking->id)
            ->whereIn('status', ['confirmed', 'settled'])
            ->where('include_in_invoice', true)
            ->sum('sale_amount');
        $this->fbVendorCharges = $vendorCharges;

        $subtotal = $packageAmount + $this->fbHallCharges + $this->fbExtraCharges + $this->fbVendorCharges - $this->fbDiscountAmount;
        
        // Calculate tax based on original tax rate
        $origSubtotal = $this->booking->subtotal;
        $taxRate = $origSubtotal > 0 ? ($this->booking->tax_amount / $origSubtotal) * 100 : 13.00;
        
        $this->fbTaxAmount = round(($subtotal * $taxRate) / 100, 2);
    }

    /**
     * Add new extra service addon inside final bill modal.
     */
    public function addFbAddon()
    {
        $this->validate([
            'newAddonName' => 'required|string|max:255',
            'newAddonPrice' => 'required|numeric|min:0',
            'newAddonQty' => 'required|integer|min:1',
        ]);

        $this->fbAddonsList[] = [
            'service_name' => $this->newAddonName,
            'unit_price' => floatval($this->newAddonPrice),
            'quantity' => intval($this->newAddonQty),
            'total_price' => floatval($this->newAddonPrice) * intval($this->newAddonQty),
        ];

        $this->newAddonName = '';
        $this->newAddonPrice = 0.00;
        $this->newAddonQty = 1;

        $this->recalculateFinalBill();
    }

    /**
     * Remove addon from final bill list.
     */
    public function removeFbAddon($index)
    {
        if (isset($this->fbAddonsList[$index])) {
            unset($this->fbAddonsList[$index]);
            $this->fbAddonsList = array_values($this->fbAddonsList);
        }
        $this->recalculateFinalBill();
    }

    /**
     * Save the prepared final bill adjustments record.
     */
    public function saveFinalBill()
    {
        $this->recalculateFinalBill();

        $this->validate([
            'fbGuestCount' => 'required|integer|min:1',
            'fbPerPlatePrice' => 'required|numeric|min:0',
            'fbHallCharges' => 'required|numeric|min:0',
            'fbDiscountAmount' => 'required|numeric|min:0',
            'fbNotes' => 'nullable|string',
        ]);

        $packageAmount = $this->booking->no_food ? 0.00 : ($this->fbGuestCount * $this->fbPerPlatePrice);
        $subtotal = $packageAmount + $this->fbHallCharges + $this->fbExtraCharges - $this->fbDiscountAmount;
        $grandTotal = $subtotal + $this->fbTaxAmount + $this->booking->security_deposit;

        $finalBill = \Illuminate\Support\Facades\DB::transaction(function () use ($packageAmount, $subtotal, $grandTotal) {
            // Remove existing final bill and its details if they exist
            if ($this->booking->finalBill) {
                $this->booking->finalBill->extraServices()->delete();
                $this->booking->finalBill->delete();
            }

            // Create new final bill
            $finalBill = \App\Models\BookingFinalBill::create([
                'booking_id' => $this->booking->id,
                'guest_count' => $this->fbGuestCount,
                'per_plate_price' => $this->fbPerPlatePrice,
                'package_amount' => $packageAmount,
                'hall_charges' => $this->fbHallCharges,
                'extra_charges' => $this->fbExtraCharges,
                'discount_amount' => $this->fbDiscountAmount,
                'tax_amount' => $this->fbTaxAmount,
                'subtotal' => $subtotal,
                'grand_total' => $grandTotal,
                'notes' => $this->fbNotes ?: null,
                'created_by' => auth()->id(),
            ]);

            // Save final bill addons
            foreach ($this->fbAddonsList as $addon) {
                \App\Models\BookingFinalBillExtraService::create([
                    'final_bill_id' => $finalBill->id,
                    'service_name' => $addon['service_name'],
                    'unit_price' => $addon['unit_price'],
                    'quantity' => $addon['quantity'],
                    'total_price' => $addon['total_price'],
                ]);
            }

            // Log history
            BookingHistory::create([
                'booking_id' => $this->booking->id,
                'user_id' => auth()->id(),
                'status_from' => $this->booking->booking_status,
                'status_to' => $this->booking->booking_status,
                'notes' => 'Prepared Event-Day Final Bill. Actual guests: ' . $this->fbGuestCount . '. Grand Total adjusted to Rs. ' . number_format($grandTotal, 2),
            ]);

            return $finalBill;
        });

        // Trigger FBR Sync if POS configuration exists
        if ($finalBill) {
            $fbrService = app(\App\Services\FbrPosService::class);
            $fbrService->syncFinalBill($finalBill);
        }

        // Recalculate and update the booking's payment status based on the new final bill amount
        $this->updatePaymentStatusBasedOnFinalBill();

        $this->booking->refresh();
        $this->showFinalBillModal = false;
        
        session()->flash('success', 'Event-day final bill has been generated and validated with FBR successfully.');
    }

    /**
     * Recalculate and update booking's payment status.
     */
    public function updatePaymentStatusBasedOnFinalBill()
    {
        $totalPaid = $this->booking->payments()->sum('amount');
        $billingAmount = $this->booking->finalBill ? $this->booking->finalBill->grand_total : $this->booking->grand_total;

        $newPaymentStatus = 'Unpaid';
        if ($totalPaid > 0) {
            if ($totalPaid >= $billingAmount) {
                $newPaymentStatus = 'Paid';
            } else {
                $newPaymentStatus = 'Partially Paid';
            }
        }

        $this->booking->update([
            'payment_status' => $newPaymentStatus
        ]);
    }

    /**
     * Process refundable security deposit.
     */
    public function processDeposit()
    {
        if ($this->depositAction === 'refund_full') {
            $this->depositRefundedAmount = $this->booking->security_deposit;
            $this->depositDeductedAmount = 0.00;
            $status = 'Refunded';
        } else {
            $this->validate([
                'depositRefundedAmount' => 'required|numeric|min:0',
                'depositDeductedAmount' => 'required|numeric|min:0',
                'depositNotes' => 'required|string|min:5',
            ]);

            // Verify totals
            $sum = floatval($this->depositRefundedAmount) + floatval($this->depositDeductedAmount);
            if (abs($sum - $this->booking->security_deposit) > 0.01) {
                $this->addError('depositSum', 'Refunded + Deducted amount must equal security deposit (Rs. ' . number_format($this->booking->security_deposit, 2) . ').');
                return;
            }

            $status = floatval($this->depositDeductedAmount) > 0 ? 'Deducted' : 'Refunded';
        }

        $this->booking->update([
            'deposit_status' => $status,
            'deposit_refunded_amount' => $this->depositRefundedAmount,
            'deposit_deducted_amount' => $this->depositDeductedAmount,
            'deposit_notes' => $this->depositNotes ?: null,
        ]);

        BookingHistory::create([
            'booking_id' => $this->booking->id,
            'user_id' => auth()->id(),
            'status_from' => $this->booking->booking_status,
            'status_to' => $this->booking->booking_status,
            'notes' => 'Security deposit processed: ' . $status . '. Refunded: Rs. ' . number_format($this->depositRefundedAmount, 2) . ', Deducted: Rs. ' . number_format($this->depositDeductedAmount, 2) . '. Notes: ' . $this->depositNotes,
        ]);

        $this->booking->refresh();
        $this->showDepositModal = false;
        $this->depositNotes = '';
        $this->depositRefundedAmount = 0.00;
        $this->depositDeductedAmount = 0.00;

        session()->flash('success', 'Refundable security deposit updated.');
    }

    /**
     * Transition booking status. Handles Revenue Recognition on Completion and Cancellation Settlement.
     */
    public function updateStatus($newStatus)
    {
        if (!in_array($newStatus, ['Draft', 'Reserved', 'Confirmed', 'Completed', 'Cancelled', 'Rejected'])) {
            return;
        }

        if ($newStatus === 'Cancelled') {
            $this->openBookingCancelModal();
            return;
        }

        if ($newStatus === 'Completed') {
            if (\Carbon\Carbon::parse($this->booking->booking_date)->startOfDay()->gt(\Carbon\Carbon::today())) {
                session()->flash('error', 'Future bookings cannot be marked as Completed.');
                return;
            }

            try {
                $recService = app(\App\Services\RevenueRecognitionService::class);
                $jv = $recService->recognizeRevenue($this->booking, date('Y-m-d'), auth()->id());
                $this->booking->refresh();
                session()->flash('success', "Event Completed! Revenue recognized successfully (Voucher: {$jv->voucher_no}).");
                return;
            } catch (\Exception $e) {
                session()->flash('error', 'Failed to recognize revenue: ' . $e->getMessage());
                return;
            }
        }

        $user = auth()->user();
        $isOwner = $user->role && in_array($user->role->name, ['owner', 'super_admin', 'business_owner']);

        if ($this->booking->booking_status === 'Completed' && !$isOwner) {
            session()->flash('error', 'Completed bookings cannot be changed to another status.');
            return;
        }
        $isLocked = $this->booking->booking_status === 'Cancelled';

        if ($isLocked && !$isOwner) {
            session()->flash('error', 'Only owners or super admins can unlock or change status of Cancelled bookings.');
            return;
        }

        $oldStatus = $this->booking->booking_status;

        $this->booking->update([
            'booking_status' => $newStatus
        ]);

        BookingHistory::create([
            'booking_id' => $this->booking->id,
            'user_id' => auth()->id(),
            'status_from' => $oldStatus,
            'status_to' => $newStatus,
            'notes' => 'Booking status manually transitioned to ' . $newStatus,
        ]);

        $this->booking->refresh();
        session()->flash('success', 'Booking status updated to ' . $newStatus);
    }

    /**
     * Open Booking Cancellation Settlement Modal.
     */
    public function openBookingCancelModal()
    {
        $advanceHeld = (float) $this->booking->advance_received;
        $this->bkCancelRefundAmount = $advanceHeld;
        $this->bkCancelFeeAmount = 0.00;
        $this->bkCancelReason = '';
        $this->bkCancelPaymentMethod = 'Cash';
        $this->bkCancelAccountId = null;
        $this->showBookingCancelModal = true;
    }

    public function updatedBkCancelRefundAmount($val)
    {
        $advanceHeld = (float) $this->booking->advance_received;
        $refund = floatval($val);
        $this->bkCancelFeeAmount = max(0.00, $advanceHeld - $refund);
    }

    public function updatedBkCancelFeeAmount($val)
    {
        $advanceHeld = (float) $this->booking->advance_received;
        $fee = floatval($val);
        $this->bkCancelRefundAmount = max(0.00, $advanceHeld - $fee);
    }

    /**
     * Execute Booking Cancellation with accounting entries.
     */
    public function executeBookingCancellation()
    {
        $this->validate([
            'bkCancelRefundAmount' => 'required|numeric|min:0',
            'bkCancelFeeAmount' => 'required|numeric|min:0',
            'bkCancelReason' => 'required|string|min:3',
            'bkCancelPaymentMethod' => 'required|string',
        ]);

        $advanceHeld = (float) $this->booking->advance_received;
        $sum = floatval($this->bkCancelRefundAmount) + floatval($this->bkCancelFeeAmount);

        if (abs($sum - $advanceHeld) > 0.01) {
            $this->addError('bkCancelSum', 'Refund Amount + Cancellation Fee must equal total Advance Liability held (Rs. ' . number_format($advanceHeld, 2) . ').');
            return;
        }

        try {
            $financialService = app(\App\Services\BookingFinancialService::class);
            $financialService->processCancellation($this->booking, [
                'refund_amount' => floatval($this->bkCancelRefundAmount),
                'cancellation_fee' => floatval($this->bkCancelFeeAmount),
                'reason' => $this->bkCancelReason,
                'payment_method' => $this->bkCancelPaymentMethod,
                'account_id' => $this->bkCancelAccountId ?: null,
                'recorded_by' => auth()->id(),
            ]);

            $this->booking->refresh();
            $this->showBookingCancelModal = false;
            session()->flash('success', 'Booking cancelled and financial liability settled.');
        } catch (\Exception $e) {
            $this->addError('cancellation', 'Cancellation failed: ' . $e->getMessage());
        }
    }

    public function openGuestModal()
    {
        $this->modalTentativeGuests = $this->booking->tentative_guests ?? $this->booking->guest_count;
        $this->modalConfirmedGuests = $this->booking->confirmed_guests;
        $this->modalGuestStatus = $this->booking->guest_status ?? ($this->booking->confirmed_guests ? 'Confirmed' : 'Tentative');
        $this->showGuestModal = true;
    }

    public function confirmGuestCount()
    {
        $this->validate([
            'modalTentativeGuests' => 'required|integer|min:1',
            'modalConfirmedGuests' => 'nullable|integer|min:0',
        ]);

        $tentative = intval($this->modalTentativeGuests);
        $confirmed = (is_numeric($this->modalConfirmedGuests) && intval($this->modalConfirmedGuests) > 0) ? intval($this->modalConfirmedGuests) : null;
        
        $effectiveCount = $confirmed ?? $tentative;
        $status = !is_null($confirmed) ? 'Confirmed' : 'Tentative';

        // Recalculate package amount if applicable
        $perPlatePrice = $this->booking->per_plate_price ?? 0.00;
        $packageAmount = $this->booking->no_food ? 0.00 : ($effectiveCount * $perPlatePrice);

        $pricing = \App\Services\BookingPricingService::calculate([
            'guest_count' => $effectiveCount,
            'per_plate_price' => $perPlatePrice,
            'hall_charges' => $this->booking->hall_charges,
            'extra_charges' => $this->booking->extra_charges,
            'discount_amount' => $this->booking->discount_amount,
            'security_deposit' => $this->booking->security_deposit,
            'tax_rate' => $this->booking->subtotal > 0 ? round(($this->booking->tax_amount * 100) / $this->booking->subtotal, 2) : 13.00,
        ]);

        $this->booking->update([
            'tentative_guests' => $tentative,
            'confirmed_guests' => $confirmed,
            'guest_status' => $status,
            'guest_count' => $effectiveCount,
            'package_amount' => $pricing['package_amount'],
            'subtotal' => $pricing['subtotal'],
            'tax_amount' => $pricing['tax_amount'],
            'grand_total' => $pricing['grand_total'],
        ]);

        BookingHistory::create([
            'booking_id' => $this->booking->id,
            'user_id' => auth()->id(),
            'status_from' => $this->booking->booking_status,
            'status_to' => $this->booking->booking_status,
            'notes' => "Updated Guest Headcount: Tentative={$tentative}, Confirmed=" . ($confirmed ?? 'None') . ", Status={$status}. Effective Headcount: {$effectiveCount}.",
        ]);

        $this->booking->refresh();
        $this->showGuestModal = false;
        session()->flash('success', 'Guest headcount and status updated successfully.');
    }

    /**
     * Open Kitchen Slip printing options modal.
     */
    public function openKitchenSlipModal()
    {
        $this->kitchenInstructions = $this->booking->kitchen_special_instructions ?? $this->booking->special_instructions ?? '';
        $this->showKitchenSlipModal = true;
    }

    /**
     * Save special kitchen instructions and trigger print window redirect.
     */
    public function saveKitchenInstructionsAndPrint()
    {
        $this->booking->update([
            'kitchen_special_instructions' => $this->kitchenInstructions,
        ]);

        $this->showKitchenSlipModal = false;
        $url = route('bookings.kitchen-slip', [
            'booking' => $this->booking->id,
            'lang' => $this->kitchenLang,
            'kitchen_special_instructions' => $this->kitchenInstructions
        ]);

        $this->dispatch('open-print-window', url: $url);
    }

    public function render()
    {
        $histories = $this->booking->histories()->with('user')->get();
        $marqueeId = $this->getMarqueeId();

        $vendorSales = \App\Models\VendorSale::withoutGlobalScope('tenant')
            ->where('booking_id', $this->booking->id)
            ->with(['vendor', 'service'])
            ->get();

        $allVendors = \App\Models\Vendor::withoutGlobalScope('tenant')
            ->where('marquee_id', $marqueeId)
            ->whereIn('status', ['active', 'Active'])
            ->orderBy('name')
            ->get();

        $vsVendorServices = $this->vsVendorId
            ? \App\Models\VendorService::withoutGlobalScope('tenant')
                ->where('vendor_id', $this->vsVendorId)
                ->whereIn('status', ['active', 'Active'])
                ->orderBy('service_name')
                ->get()
            : collect();

        $accounts = \App\Models\Account::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->where('is_active', true)->orderBy('name')->get();

        $viewingVendorSale = $this->viewingVendorSaleId
            ? \App\Models\VendorSale::withoutGlobalScope('tenant')->with(['vendor', 'service', 'booking', 'ledgers.creator', 'customerPayments.recorder'])->find($this->viewingVendorSaleId)
            : null;

        $deletingVendorSale = $this->deletingVendorSaleId
            ? \App\Models\VendorSale::withoutGlobalScope('tenant')->with(['vendor', 'service'])->find($this->deletingVendorSaleId)
            : null;

        $cashBankAccounts = \App\Models\CashBankAccount::withoutGlobalScope('tenant')
            ->where('marquee_id', $marqueeId)
            ->where('status', 'active')
            ->with(['account'])
            ->get();

        $customerLedgers = $this->booking->customerLedgers()
            ->with(['creator', 'journalVoucher'])
            ->get();

        return view('livewire.booking-view', [
            'histories' => $histories,
            'vendorSales' => $vendorSales,
            'allVendors' => $allVendors,
            'vsVendorServices' => $vsVendorServices,
            'accounts' => $accounts,
            'cashBankAccounts' => $cashBankAccounts,
            'customerLedgers' => $customerLedgers,
            'viewingVendorSale' => $viewingVendorSale,
            'deletingVendorSale' => $deletingVendorSale,
        ]);
    }
}
