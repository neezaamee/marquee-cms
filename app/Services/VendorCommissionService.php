<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Booking;
use App\Models\JournalVoucher;
use App\Models\JournalVoucherItem;
use App\Models\Vendor;
use App\Models\VendorCommissionAgreement;
use App\Models\VendorLedger;
use App\Models\VendorSale;
use App\Models\VendorService;
use App\Models\VendorSettlement;
use Illuminate\Support\Facades\DB;

class VendorCommissionService
{
    /**
     * Resolve applicable commission agreement based on priority rules:
     * 1. Service-specific agreement
     * 2. Vendor-wide agreement
     */
    public function resolveAgreement(Vendor $vendor, ?VendorService $service = null, ?string $date = null): ?VendorCommissionAgreement
    {
        $targetDate = $date ?: now()->format('Y-m-d');

        // Priority 1: Service-specific active agreement
        if ($service) {
            $serviceAgreement = VendorCommissionAgreement::where('marquee_id', $vendor->marquee_id)
                ->where('vendor_id', $vendor->id)
                ->where('vendor_service_id', $service->id)
                ->where('status', 'active')
                ->where('effective_from', '<=', $targetDate)
                ->where(function ($q) use ($targetDate) {
                    $q->whereNull('effective_to')->orWhere('effective_to', '>=', $targetDate);
                })
                ->first();

            if ($serviceAgreement) {
                return $serviceAgreement;
            }
        }

        // Priority 2: Vendor-wide active agreement (service_id is null)
        return VendorCommissionAgreement::where('marquee_id', $vendor->marquee_id)
            ->where('vendor_id', $vendor->id)
            ->whereNull('vendor_service_id')
            ->where('status', 'active')
            ->where('effective_from', '<=', $targetDate)
            ->where(function ($q) use ($targetDate) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $targetDate);
            })
            ->first();
    }

    /**
     * Compute commission amounts using the resolved agreement or custom override rates.
     */
    public function calculateCommission(?VendorCommissionAgreement $agreement, float $saleAmount, ?float $overrideRate = null, ?string $overrideType = null): array
    {
        $type = $overrideType ?: ($agreement ? $agreement->commission_type : 'percentage');
        $rate = $overrideRate !== null ? $overrideRate : ($agreement ? (float) $agreement->commission_percentage : 0.00);

        $commissionAmount = 0.00;

        switch ($type) {
            case 'fixed_per_event':
                $commissionAmount = $agreement ? (float) $agreement->fixed_commission_amount : 0.00;
                break;

            case 'fixed_monthly':
                $commissionAmount = $agreement ? (float) $agreement->monthly_fixed_amount : 0.00;
                break;

            case 'hybrid':
                $basePct = $saleAmount * ($rate / 100);
                $fixed = $agreement ? (float) $agreement->fixed_commission_amount : 0.00;
                $commissionAmount = $basePct + $fixed;

                if ($agreement && $agreement->minimum_commission > 0 && $commissionAmount < (float) $agreement->minimum_commission) {
                    $commissionAmount = (float) $agreement->minimum_commission;
                }
                if ($agreement && $agreement->maximum_commission > 0 && $commissionAmount > (float) $agreement->maximum_commission) {
                    $commissionAmount = (float) $agreement->maximum_commission;
                }
                break;

            case 'percentage':
            default:
                $commissionAmount = $saleAmount * ($rate / 100);
                break;
        }

        $netPayable = max(0.00, $saleAmount - $commissionAmount);

        return [
            'commission_type' => $type,
            'commission_rate' => $rate,
            'commission_amount' => round($commissionAmount, 2),
            'vendor_net_amount' => round($netPayable, 2),
        ];
    }

    /**
     * Record a new Vendor Sale, update Vendor Ledger, and post Accounting Journal Entry.
     */
    public function createVendorSale(array $data): VendorSale
    {
        return DB::transaction(function () use ($data) {
            $vendor = Vendor::findOrFail($data['vendor_id']);
            $service = isset($data['vendor_service_id']) ? VendorService::find($data['vendor_service_id']) : null;
            $saleDate = $data['sale_date'] ?? now()->format('Y-m-d');
            $eventDate = $data['event_date'] ?? $saleDate;
            $saleAmount = floatval($data['sale_amount']);

            // 1. Resolve Agreement & Calculate Commission
            $agreement = isset($data['agreement_id'])
                ? VendorCommissionAgreement::find($data['agreement_id'])
                : $this->resolveAgreement($vendor, $service, $eventDate);

            $commissionCalc = $this->calculateCommission(
                $agreement,
                $saleAmount,
                $data['commission_rate'] ?? null,
                $data['commission_type'] ?? null
            );

            // 2. Create Vendor Sale (Historical Snapshot)
            $advanceAmount = floatval($data['advance_amount'] ?? 0.00);
            $advanceAmount = min($advanceAmount, $commissionCalc['vendor_net_amount']);
            $paidAmount = $advanceAmount;
            $remainingAmount = max(0.00, $commissionCalc['vendor_net_amount'] - $paidAmount);
            
            $paymentStatus = 'unpaid';
            if ($paidAmount >= $commissionCalc['vendor_net_amount'] && $paidAmount > 0) {
                $paymentStatus = 'fully_paid';
            } elseif ($paidAmount > 0) {
                $paymentStatus = 'partially_paid';
            }

            $customerAdvance = isset($data['customer_advance_amount']) ? floatval($data['customer_advance_amount']) : 0.00;
            $customerPaid = $customerAdvance;
            $customerRemaining = max(0.00, $saleAmount - $customerPaid);

            $sale = VendorSale::create([
                'marquee_id' => $vendor->marquee_id,
                'branch_id' => $data['branch_id'] ?? $vendor->branch_id,
                'vendor_id' => $vendor->id,
                'vendor_service_id' => $service?->id,
                'booking_id' => $data['booking_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'agreement_id' => $agreement?->id,
                'event_date' => $eventDate,
                'sale_date' => $saleDate,
                'quantity' => $data['quantity'] ?? 1,
                'unit' => $data['unit'] ?? ($service?->unit ?? 'Event'),
                'sale_amount' => $saleAmount,
                'customer_advance_amount' => $customerAdvance,
                'customer_paid_amount' => $customerPaid,
                'customer_remaining_amount' => $customerRemaining,
                'commission_type' => $commissionCalc['commission_type'],
                'commission_rate' => $commissionCalc['commission_rate'],
                'commission_amount' => $commissionCalc['commission_amount'],
                'vendor_net_amount' => $commissionCalc['vendor_net_amount'],
                'advance_amount' => $advanceAmount,
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remainingAmount,
                'payment_status' => $paymentStatus,
                'include_in_invoice' => isset($data['include_in_invoice']) ? (bool) $data['include_in_invoice'] : true,
                'status' => 'confirmed',
                'override_reason' => $data['override_reason'] ?? null,
                'override_by' => isset($data['override_reason']) ? auth()->id() : null,
                'notes' => $data['notes'] ?? null,
            ]);

            // If customer advance was paid and booking exists, record customer payment receipt via BookingFinancialService
            if ($customerAdvance > 0 && $sale->booking_id && $sale->booking) {
                $custPaymentMethod = $data['customer_payment_method'] ?? ($data['payment_method'] ?? 'Cash');
                $custRef = $data['customer_payment_reference'] ?? ('CUST-ADV-' . $sale->vendor_sale_number);
                $custAccountId = $data['customer_account_id'] ?? null;

                app(\App\Services\BookingFinancialService::class)->recordPayment($sale->booking, [
                    'amount' => $customerAdvance,
                    'payment_date' => $data['customer_payment_date'] ?? $saleDate,
                    'payment_method' => $custPaymentMethod,
                    'account_id' => $custAccountId,
                    'vendor_sale_id' => $sale->id,
                    'transaction_reference' => $custRef,
                    'notes' => "Customer Advance for " . ($service?->service_name ?? $vendor->name) . " (Sale #" . $sale->vendor_sale_number . ")",
                    'recorded_by' => auth()->id(),
                ]);
            }

            // 3. Post Sale Credit to Vendor Ledger
            $lastBalance = $vendor->current_balance;
            $runningBalanceAfterCredit = $lastBalance + $sale->vendor_net_amount;

            VendorLedger::create([
                'marquee_id' => $vendor->marquee_id,
                'branch_id' => $sale->branch_id,
                'vendor_id' => $vendor->id,
                'vendor_sale_id' => $sale->id,
                'booking_id' => $sale->booking_id,
                'transaction_date' => $saleDate,
                'reference_number' => $sale->vendor_sale_number,
                'transaction_type' => 'sale_credit',
                'description' => "Vendor Service Assignment: " . ($service?->service_name ?? $vendor->name) . " (Vendor Cost: Rs. " . number_format($sale->vendor_net_amount, 2) . ", Commission: Rs. " . number_format($sale->commission_amount, 2) . ")",
                'sale_amount' => $sale->sale_amount,
                'commission_amount' => $sale->commission_amount,
                'payment_amount' => 0.00,
                'running_balance' => $runningBalanceAfterCredit,
                'created_by' => auth()->id(),
            ]);

            // 4. If advance payout to vendor was made, post advance ledger entry and accounting voucher
            if ($advanceAmount > 0) {
                $runningBalanceAfterAdvance = $runningBalanceAfterCredit - $advanceAmount;
                $paymentMethod = $data['payment_method'] ?? 'Cash';
                $refNumber = $data['reference_number'] ?? ('ADV-' . $sale->vendor_sale_number);

                VendorLedger::create([
                    'marquee_id' => $vendor->marquee_id,
                    'branch_id' => $sale->branch_id,
                    'vendor_id' => $vendor->id,
                    'vendor_sale_id' => $sale->id,
                    'booking_id' => $sale->booking_id,
                    'transaction_date' => $saleDate,
                    'reference_number' => $refNumber,
                    'transaction_type' => 'advance_payment',
                    'description' => "Advance payout to vendor for " . ($service?->service_name ?? $vendor->name) . " via " . $paymentMethod,
                    'sale_amount' => 0.00,
                    'commission_amount' => 0.00,
                    'payment_amount' => $advanceAmount,
                    'running_balance' => $runningBalanceAfterAdvance,
                    'created_by' => auth()->id(),
                ]);

                // Post Advance Journal Voucher
                $this->postVendorPaymentToAccounting($vendor, $advanceAmount, [
                    'branch_id' => $sale->branch_id,
                    'transaction_date' => $saleDate,
                    'reference_number' => $refNumber,
                    'payment_method' => $paymentMethod,
                    'account_id' => $data['account_id'] ?? null,
                    'notes' => "Advance payment to vendor " . $vendor->name . " for " . ($service?->service_name ?? 'Service') . " (Sale #" . $sale->vendor_sale_number . ")",
                ]);
            }

            // 5. Post Financial Journal Voucher to Accounting Module for the sale
            $this->postVendorSaleToAccounting($sale);

            return $sale;
        });
    }

    /**
     * Update an existing Vendor Sale (e.g. adjust prices, commission, invoice inclusion, notes).
     */
    public function updateVendorSale(VendorSale $sale, array $data): VendorSale
    {
        return DB::transaction(function () use ($sale, $data) {
            $vendor = $sale->vendor;
            $service = isset($data['vendor_service_id']) ? VendorService::find($data['vendor_service_id']) : $sale->service;
            $saleAmount = isset($data['sale_amount']) ? floatval($data['sale_amount']) : (float) $sale->sale_amount;
            $customVendorCost = isset($data['vendor_cost']) && $data['vendor_cost'] !== '' ? floatval($data['vendor_cost']) : null;

            // Recalculate commission / vendor net amount
            if ($customVendorCost !== null) {
                $vendorNetAmount = $customVendorCost;
                $commissionAmount = max(0.00, $saleAmount - $vendorNetAmount);
                $commissionRate = $saleAmount > 0 ? round(($commissionAmount / $saleAmount) * 100, 2) : 0.00;
                $commissionType = 'percentage';
            } else {
                $agreement = $sale->agreement ?? $this->resolveAgreement($vendor, $service, $sale->event_date?->format('Y-m-d'));
                $overrideRate = isset($data['commission_rate']) && $data['commission_rate'] !== '' ? floatval($data['commission_rate']) : null;
                $calc = $this->calculateCommission($agreement, $saleAmount, $overrideRate);
                $commissionType = $calc['commission_type'];
                $commissionRate = $calc['commission_rate'];
                $commissionAmount = $calc['commission_amount'];
                $vendorNetAmount = $calc['vendor_net_amount'];
            }

            $paidAmount = (float) $sale->paid_amount;
            $remainingAmount = max(0.00, $vendorNetAmount - $paidAmount);
            $paymentStatus = $paidAmount <= 0 ? 'unpaid' : ($remainingAmount <= 0.01 ? 'fully_paid' : 'partially_paid');

            $oldVendorNet = (float) $sale->vendor_net_amount;
            $diffNet = $vendorNetAmount - $oldVendorNet;

            $sale->update([
                'vendor_service_id' => $service?->id,
                'sale_amount' => $saleAmount,
                'commission_type' => $commissionType,
                'commission_rate' => $commissionRate,
                'commission_amount' => $commissionAmount,
                'vendor_net_amount' => $vendorNetAmount,
                'remaining_amount' => $remainingAmount,
                'payment_status' => $paymentStatus,
                'status' => $paymentStatus === 'fully_paid' ? 'settled' : 'confirmed',
                'include_in_invoice' => isset($data['include_in_invoice']) ? (bool) $data['include_in_invoice'] : $sale->include_in_invoice,
                'notes' => $data['notes'] ?? $sale->notes,
            ]);

            // If net payable changed, record adjustment in Vendor Ledger
            if (abs($diffNet) > 0.01) {
                $currentBalance = $vendor->current_balance;
                $newBalance = max(0.00, $currentBalance + $diffNet);

                VendorLedger::create([
                    'marquee_id' => $vendor->marquee_id,
                    'branch_id' => $sale->branch_id,
                    'vendor_id' => $vendor->id,
                    'vendor_sale_id' => $sale->id,
                    'booking_id' => $sale->booking_id,
                    'transaction_date' => now()->format('Y-m-d'),
                    'reference_number' => 'ADJ-' . $sale->vendor_sale_number,
                    'transaction_type' => 'sale_credit',
                    'description' => "Cost Adjustment for " . ($service?->service_name ?? $vendor->name) . " (New Cost: Rs. " . number_format($vendorNetAmount, 2) . ", Diff: Rs. " . number_format($diffNet, 2) . ")",
                    'sale_amount' => $saleAmount,
                    'commission_amount' => $commissionAmount,
                    'payment_amount' => 0.00,
                    'running_balance' => $newBalance,
                    'created_by' => auth()->id(),
                ]);
            }

            return $sale;
        });
    }

    /**
     * Cancel a Vendor Sale and reverse unpaid ledger obligations.
     */
    public function cancelVendorSale(VendorSale $sale, string $reason = 'Cancelled by user'): bool
    {
        return DB::transaction(function () use ($sale, $reason) {
            if ($sale->status === 'cancelled') {
                return true;
            }

            $vendor = $sale->vendor;
            $unpaidAmount = (float) $sale->remaining_amount;

            $sale->update([
                'status' => 'cancelled',
                'override_reason' => $reason,
                'notes' => ($sale->notes ? $sale->notes . "\n" : "") . "Cancelled: " . $reason,
            ]);

            // If there was an unpaid payable balance, reverse it on the vendor ledger
            if ($unpaidAmount > 0) {
                $currentBalance = $vendor->current_balance;
                $newBalance = max(0.00, $currentBalance - $unpaidAmount);

                VendorLedger::create([
                    'marquee_id' => $vendor->marquee_id,
                    'branch_id' => $sale->branch_id,
                    'vendor_id' => $vendor->id,
                    'vendor_sale_id' => $sale->id,
                    'booking_id' => $sale->booking_id,
                    'transaction_date' => now()->format('Y-m-d'),
                    'reference_number' => 'CNCL-' . $sale->vendor_sale_number,
                    'transaction_type' => 'settlement_payout',
                    'description' => "Cancelled Assignment: " . ($sale->service?->service_name ?? $vendor->name) . " (Unpaid Reversal: Rs. " . number_format($unpaidAmount, 2) . " - " . $reason . ")",
                    'sale_amount' => 0.00,
                    'commission_amount' => 0.00,
                    'payment_amount' => $unpaidAmount,
                    'running_balance' => $newBalance,
                    'created_by' => auth()->id(),
                ]);
            }

            return true;
        });
    }

    /**
     * Delete a Vendor Sale if no payouts were made.
     */
    public function deleteVendorSale(VendorSale $sale): bool
    {
        return DB::transaction(function () use ($sale) {
            if ((float) $sale->paid_amount > 0) {
                // If payments were made, cancel instead of hard deleting to preserve ledger audit trail
                return $this->cancelVendorSale($sale, 'Deleted after payout disbursement');
            }

            $vendor = $sale->vendor;
            $vendorCost = (float) $sale->vendor_net_amount;

            // Remove ledger entries associated with this sale
            VendorLedger::where('vendor_sale_id', $sale->id)->delete();

            $sale->delete();

            return true;
        });
    }

    /**
     * Record a subsequent payment/installment for a specific VendorSale.
     */
    public function recordVendorSalePayment(VendorSale $sale, float $paymentAmount, array $params = []): VendorLedger
    {
        return DB::transaction(function () use ($sale, $paymentAmount, $params) {
            $vendor = $sale->vendor;
            $paymentDate = $params['payment_date'] ?? now()->format('Y-m-d');
            $paymentMethod = $params['payment_method'] ?? 'Cash';
            $refNumber = $params['reference_number'] ?? ('PAY-' . $sale->vendor_sale_number . '-' . time());
            $accountId = $params['account_id'] ?? null;
            $remarks = $params['remarks'] ?? 'Vendor installment payment';

            // 1. Update VendorSale payment metrics
            $newPaidAmount = (float) $sale->paid_amount + $paymentAmount;
            $newRemainingAmount = max(0.00, (float) $sale->vendor_net_amount - $newPaidAmount);
            $newPaymentStatus = $newRemainingAmount <= 0.01 ? 'fully_paid' : 'partially_paid';

            $sale->update([
                'paid_amount' => $newPaidAmount,
                'remaining_amount' => $newRemainingAmount,
                'payment_status' => $newPaymentStatus,
                'status' => $newPaymentStatus === 'fully_paid' ? 'settled' : 'confirmed',
            ]);

            // 2. Post to Vendor Ledger
            $currentBalance = $vendor->current_balance;
            $newRunningBalance = max(0.00, $currentBalance - $paymentAmount);

            $ledgerEntry = VendorLedger::create([
                'marquee_id' => $vendor->marquee_id,
                'branch_id' => $sale->branch_id ?? $vendor->branch_id,
                'vendor_id' => $vendor->id,
                'vendor_sale_id' => $sale->id,
                'booking_id' => $sale->booking_id,
                'transaction_date' => $paymentDate,
                'reference_number' => $refNumber,
                'transaction_type' => 'settlement_payout',
                'description' => "Installment payout for " . ($sale->service?->service_name ?? $vendor->name) . " on Booking #" . ($sale->booking?->booking_number ?? 'N/A') . " via " . $paymentMethod . " (" . $remarks . ")",
                'sale_amount' => 0.00,
                'commission_amount' => 0.00,
                'payment_amount' => $paymentAmount,
                'running_balance' => $newRunningBalance,
                'created_by' => auth()->id(),
            ]);

            // 3. Post Accounting Journal Voucher
            $this->postVendorPaymentToAccounting($vendor, $paymentAmount, [
                'branch_id' => $sale->branch_id ?? $vendor->branch_id,
                'transaction_date' => $paymentDate,
                'reference_number' => $refNumber,
                'payment_method' => $paymentMethod,
                'account_id' => $accountId,
                'notes' => "Installment payment to " . $vendor->name . " for Booking #" . ($sale->booking?->booking_number ?? 'N/A') . " - " . $remarks,
            ]);

            return $ledgerEntry;
        });
    }

    /**
     * Record a subsequent advance/installment payment received from the customer for a VendorSale.
     */
    public function recordCustomerSalePayment(VendorSale $sale, float $paymentAmount, array $params = []): \App\Models\BookingPayment
    {
        return DB::transaction(function () use ($sale, $paymentAmount, $params) {
            $paymentDate = $params['payment_date'] ?? now()->format('Y-m-d');
            $paymentMethod = $params['payment_method'] ?? 'Cash';
            $refNumber = $params['transaction_reference'] ?? ('CUST-ADV-' . $sale->vendor_sale_number . '-' . time());
            $notes = $params['notes'] ?? ("Customer advance installment for " . ($sale->service?->service_name ?? $sale->vendor?->name ?? 'Vendor Service'));

            // 1. Create BookingPayment record via BookingFinancialService
            if ($sale->booking) {
                $payment = app(\App\Services\BookingFinancialService::class)->recordPayment($sale->booking, [
                    'amount' => $paymentAmount,
                    'payment_date' => $paymentDate,
                    'payment_method' => $paymentMethod,
                    'account_id' => $params['account_id'] ?? null,
                    'vendor_sale_id' => $sale->id,
                    'transaction_reference' => $refNumber,
                    'notes' => $notes,
                    'recorded_by' => auth()->id(),
                ]);
            } else {
                $payment = \App\Models\BookingPayment::create([
                    'booking_id' => $sale->booking_id,
                    'vendor_sale_id' => $sale->id,
                    'amount' => $paymentAmount,
                    'payment_date' => $paymentDate,
                    'payment_method' => $paymentMethod,
                    'transaction_reference' => $refNumber,
                    'notes' => $notes,
                    'recorded_by' => auth()->id(),
                ]);
            }

            // 2. Recalculate customer paid and remaining balance on VendorSale
            $totalCustomerPaid = (float) \App\Models\BookingPayment::where('vendor_sale_id', $sale->id)->sum('amount');
            if ($totalCustomerPaid <= 0) {
                $totalCustomerPaid = (float) $sale->customer_paid_amount + $paymentAmount;
            }
            $customerRemaining = max(0.00, (float) $sale->sale_amount - $totalCustomerPaid);

            $sale->update([
                'customer_paid_amount' => $totalCustomerPaid,
                'customer_remaining_amount' => $customerRemaining,
            ]);

            return $payment;
        });
    }

    /**
     * Process a Vendor Settlement payout and update ledger + accounting.
     */
    public function processSettlement(Vendor $vendor, float $paidAmount, array $params): VendorSettlement
    {
        return DB::transaction(function () use ($vendor, $paidAmount, $params) {
            $settlementDate = $params['settlement_date'] ?? now()->format('Y-m-d');
            $currentBalance = $vendor->current_balance;
            $newBalance = max(0.00, $currentBalance - $paidAmount);

            // 1. Create Vendor Settlement
            $settlement = VendorSettlement::create([
                'marquee_id' => $vendor->marquee_id,
                'branch_id' => $params['branch_id'] ?? $vendor->branch_id,
                'vendor_id' => $vendor->id,
                'settlement_date' => $settlementDate,
                'total_sales_amount' => $vendor->total_sales,
                'total_commission_amount' => $vendor->total_commission,
                'net_payable_amount' => $currentBalance,
                'paid_amount' => $paidAmount,
                'remaining_balance' => $newBalance,
                'payment_method' => $params['payment_method'] ?? 'Cash',
                'reference_number' => $params['reference_number'] ?? null,
                'account_id' => $params['account_id'] ?? null,
                'status' => $newBalance <= 0.01 ? 'fully_settled' : 'partially_settled',
                'remarks' => $params['remarks'] ?? 'Vendor settlement payout.',
            ]);

            // 2. Post to Vendor Ledger
            VendorLedger::create([
                'marquee_id' => $vendor->marquee_id,
                'branch_id' => $settlement->branch_id,
                'vendor_id' => $vendor->id,
                'settlement_id' => $settlement->id,
                'transaction_date' => $settlementDate,
                'reference_number' => $settlement->settlement_number,
                'transaction_type' => 'settlement_payout',
                'description' => "Payout to vendor via " . $settlement->payment_method . " (" . ($params['remarks'] ?? 'Settlement') . ")",
                'sale_amount' => 0.00,
                'commission_amount' => 0.00,
                'payment_amount' => $paidAmount,
                'running_balance' => $newBalance,
                'created_by' => auth()->id(),
            ]);

            // 3. Post Financial Journal Voucher to Accounting Module
            $jv = $this->postSettlementToAccounting($settlement);
            if ($jv) {
                $settlement->update(['journal_voucher_id' => $jv->id]);
            }

            return $settlement;
        });
    }

    /**
     * Generate accounting journal voucher entries for a confirmed vendor sale.
     */
    protected function postVendorSaleToAccounting(VendorSale $sale): ?JournalVoucher
    {
        $assetType = AccountType::where('name', 'Current Assets')->orWhere('code', 'CURRENT_ASSETS')->orWhere('code', 'CASSET')->first();
        if (!$assetType) {
            $assetType = AccountType::create(['name' => 'Current Assets', 'code' => 'CURRENT_ASSETS', 'nature' => 'Asset']);
        }

        $revenueType = AccountType::where('name', 'Revenue')->orWhere('code', 'REV')->orWhere('code', 'OPERATING_REVENUE')->first();
        if (!$revenueType) {
            $revenueType = AccountType::create(['name' => 'Operating Revenue', 'code' => 'OPERATING_REVENUE', 'nature' => 'Income']);
        }

        $liabilityType = AccountType::where('name', 'Current Liabilities')->orWhere('code', 'CLIAB')->orWhere('code', 'CURRENT_LIABILITIES')->first();
        if (!$liabilityType) {
            $liabilityType = AccountType::create(['name' => 'Current Liabilities', 'code' => 'CURRENT_LIABILITIES', 'nature' => 'Liability']);
        }

        // Find/Create Accounts Receivable Account
        $arAccount = Account::withoutGlobalScope('tenant')->withTrashed()->where('marquee_id', $sale->marquee_id)
            ->where(function($q) {
                $q->where('account_code', '1003')
                  ->orWhere('name', 'Accounts Receivable');
            })
            ->first();

        if (!$arAccount) {
            $arAccount = Account::withoutGlobalScope('tenant')->withTrashed()->firstOrCreate(
                ['marquee_id' => $sale->marquee_id, 'name' => 'Accounts Receivable'],
                [
                    'account_code' => '1003',
                    'account_type_id' => $assetType->id,
                    'nature' => 'Asset',
                    'description' => 'Outstanding customer receivables for event services',
                    'is_active' => true,
                ]
            );
        }

        // Find/Create Vendor Commission Income Account
        $incomeAccount = Account::withoutGlobalScope('tenant')->withTrashed()->where('marquee_id', $sale->marquee_id)
            ->where(function($q) {
                $q->where('account_code', '4005')
                  ->orWhere('account_code', '4200-VEN')
                  ->orWhere('name', 'Vendor Commission Income');
            })
            ->first();

        if (!$incomeAccount) {
            $incomeAccount = Account::withoutGlobalScope('tenant')->withTrashed()->firstOrCreate(
                ['marquee_id' => $sale->marquee_id, 'name' => 'Vendor Commission Income'],
                [
                    'account_code' => '4005',
                    'account_type_id' => $revenueType->id,
                    'nature' => 'Income',
                    'description' => 'Income generated from event vendor sales commissions',
                    'is_active' => true,
                ]
            );
        }

        // Find/Create Vendor Payable Clearing Account
        $payableAccount = Account::withoutGlobalScope('tenant')->withTrashed()->where('marquee_id', $sale->marquee_id)
            ->where(function($q) {
                $q->where('account_code', '2150-VEN')
                  ->orWhere('name', 'Vendor Payable Clearing');
            })
            ->first();

        if (!$payableAccount) {
            $payableAccount = Account::withoutGlobalScope('tenant')->withTrashed()->firstOrCreate(
                ['marquee_id' => $sale->marquee_id, 'name' => 'Vendor Payable Clearing'],
                [
                    'account_code' => '2150-VEN',
                    'account_type_id' => $liabilityType->id,
                    'nature' => 'Liability',
                    'description' => 'Net liabilities payable to contracted event vendors',
                    'is_active' => true,
                ]
            );
        }

        if ($sale->sale_amount <= 0 && $sale->commission_amount <= 0 && $sale->vendor_net_amount <= 0) {
            return null;
        }

        $fy = \App\Models\FinancialYear::withoutGlobalScope('tenant')->withTrashed()->where('marquee_id', $sale->marquee_id)->where('status', 'active')->first();
        if (!$fy) {
            $fy = \App\Models\FinancialYear::create([
                'marquee_id' => $sale->marquee_id,
                'name' => 'FY ' . date('Y'),
                'start_date' => date('Y-01-01'),
                'end_date' => date('Y-12-31'),
                'status' => 'active',
                'is_default' => true,
            ]);
        }

        $jvNumber = 'JV-VS-' . $sale->id . '-' . time();
        $jv = JournalVoucher::create([
            'marquee_id' => $sale->marquee_id,
            'branch_id' => $sale->branch_id,
            'financial_year_id' => $fy->id,
            'voucher_no' => $jvNumber,
            'voucher_date' => $sale->sale_date->format('Y-m-d'),
            'reference' => $sale->vendor_sale_number,
            'notes' => "Vendor Sale #" . $sale->vendor_sale_number . " (" . $sale->vendor->name . ") Billed & Commission Income",
            'status' => 'posted',
        ]);

        // Debit Accounts Receivable (Total Sale Amount)
        if ($sale->sale_amount > 0) {
            JournalVoucherItem::create([
                'journal_voucher_id' => $jv->id,
                'account_id' => $arAccount->id,
                'debit' => $sale->sale_amount,
                'credit' => 0.00,
                'narration' => 'Total vendor service billed for sale #' . $sale->vendor_sale_number,
            ]);
        }

        // Credit Vendor Commission Income
        if ($sale->commission_amount > 0) {
            JournalVoucherItem::create([
                'journal_voucher_id' => $jv->id,
                'account_id' => $incomeAccount->id,
                'debit' => 0.00,
                'credit' => $sale->commission_amount,
                'narration' => 'Commission earned on vendor sale #' . $sale->vendor_sale_number,
            ]);
        }

        // Credit Vendor Payable Clearing
        if ($sale->vendor_net_amount > 0) {
            JournalVoucherItem::create([
                'journal_voucher_id' => $jv->id,
                'account_id' => $payableAccount->id,
                'debit' => 0.00,
                'credit' => $sale->vendor_net_amount,
                'narration' => 'Net vendor payable for sale #' . $sale->vendor_sale_number,
            ]);
        }

        return $jv;
    }

    /**
     * Generate accounting journal voucher entries for a vendor settlement payout.
     */
    protected function postSettlementToAccounting(VendorSettlement $settlement): ?JournalVoucher
    {
        $liabilityType = AccountType::where('name', 'Current Liabilities')->orWhere('code', 'CLIAB')->first();
        if (!$liabilityType) {
            $liabilityType = AccountType::create(['name' => 'Current Liabilities', 'code' => 'CLIAB', 'nature' => 'Liability']);
        }

        $payableAccount = Account::withoutGlobalScope('tenant')->withTrashed()->where('marquee_id', $settlement->marquee_id)
            ->where('name', 'Vendor Payable Clearing')
            ->first();

        if (!$payableAccount || $settlement->paid_amount <= 0) {
            return null;
        }

        $fy = \App\Models\FinancialYear::withoutGlobalScope('tenant')->withTrashed()->where('marquee_id', $settlement->marquee_id)->where('status', 'active')->first();
        if (!$fy) {
            $fy = \App\Models\FinancialYear::create([
                'marquee_id' => $settlement->marquee_id,
                'name' => 'FY ' . date('Y'),
                'start_date' => date('Y-01-01'),
                'end_date' => date('Y-12-31'),
                'status' => 'active',
                'is_default' => true,
            ]);
        }

        $jvNumber = 'JV-SET-' . $settlement->id . '-' . time();
        $jv = JournalVoucher::create([
            'marquee_id' => $settlement->marquee_id,
            'branch_id' => $settlement->branch_id,
            'financial_year_id' => $fy->id,
            'voucher_no' => $jvNumber,
            'voucher_date' => $settlement->settlement_date->format('Y-m-d'),
            'reference' => $settlement->settlement_number,
            'notes' => "Vendor Settlement Payout #" . $settlement->settlement_number . " (" . $settlement->vendor->name . ")",
            'status' => 'posted',
        ]);

        // Debit Vendor Payable Clearing
        JournalVoucherItem::create([
            'journal_voucher_id' => $jv->id,
            'account_id' => $payableAccount->id,
            'debit' => $settlement->paid_amount,
            'credit' => 0.00,
            'narration' => 'Payout against vendor ledger balance',
        ]);

        // Credit Cash / Bank Account
        if ($settlement->account_id) {
            JournalVoucherItem::create([
                'journal_voucher_id' => $jv->id,
                'account_id' => $settlement->account_id,
                'debit' => 0.00,
                'credit' => $settlement->paid_amount,
                'narration' => 'Payment issued via ' . $settlement->payment_method,
            ]);
        }

        return $jv;
    }

    /**
     * Generate accounting journal voucher entries for an advance or installment payout.
     */
    protected function postVendorPaymentToAccounting(Vendor $vendor, float $amount, array $params): ?JournalVoucher
    {
        if ($amount <= 0) {
            return null;
        }

        $liabilityType = AccountType::where('name', 'Current Liabilities')->orWhere('code', 'CLIAB')->first();
        if (!$liabilityType) {
            $liabilityType = AccountType::create(['name' => 'Current Liabilities', 'code' => 'CLIAB', 'nature' => 'Liability']);
        }

        $payableAccount = Account::withoutGlobalScope('tenant')->withTrashed()->where('marquee_id', $vendor->marquee_id)
            ->where('name', 'Vendor Payable Clearing')
            ->first();

        if (!$payableAccount) {
            $payableAccount = Account::withoutGlobalScope('tenant')->withTrashed()->firstOrCreate(
                ['marquee_id' => $vendor->marquee_id, 'name' => 'Vendor Payable Clearing'],
                [
                    'account_code' => '2150-VEN',
                    'account_type_id' => $liabilityType->id,
                    'nature' => 'Liability',
                    'description' => 'Net liabilities payable to contracted event vendors',
                    'is_active' => true,
                ]
            );
        }

        $fy = \App\Models\FinancialYear::withoutGlobalScope('tenant')->withTrashed()->where('marquee_id', $vendor->marquee_id)->where('status', 'active')->first();
        if (!$fy) {
            $fy = \App\Models\FinancialYear::create([
                'marquee_id' => $vendor->marquee_id,
                'name' => 'FY ' . date('Y'),
                'start_date' => date('Y-01-01'),
                'end_date' => date('Y-12-31'),
                'status' => 'active',
                'is_default' => true,
            ]);
        }

        $jvNumber = 'JV-VPAY-' . time() . '-' . rand(100, 999);
        $jv = JournalVoucher::create([
            'marquee_id' => $vendor->marquee_id,
            'branch_id' => $params['branch_id'] ?? $vendor->branch_id,
            'financial_year_id' => $fy->id,
            'voucher_no' => $jvNumber,
            'voucher_date' => $params['transaction_date'] ?? now()->format('Y-m-d'),
            'reference' => $params['reference_number'] ?? $jvNumber,
            'notes' => $params['notes'] ?? ("Payout to vendor " . $vendor->name),
            'status' => 'posted',
        ]);

        // Debit Vendor Payable Clearing (Reduces liability)
        JournalVoucherItem::create([
            'journal_voucher_id' => $jv->id,
            'account_id' => $payableAccount->id,
            'debit' => $amount,
            'credit' => 0.00,
            'narration' => $params['notes'] ?? 'Vendor payout',
        ]);

        // Credit Cash / Bank Account (if provided)
        if (!empty($params['account_id'])) {
            JournalVoucherItem::create([
                'journal_voucher_id' => $jv->id,
                'account_id' => $params['account_id'],
                'debit' => 0.00,
                'credit' => $amount,
                'narration' => 'Disbursed via ' . ($params['payment_method'] ?? 'Cash'),
            ]);
        }

        return $jv;
    }
}
