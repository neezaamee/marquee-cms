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
                'commission_type' => $commissionCalc['commission_type'],
                'commission_rate' => $commissionCalc['commission_rate'],
                'commission_amount' => $commissionCalc['commission_amount'],
                'vendor_net_amount' => $commissionCalc['vendor_net_amount'],
                'status' => 'confirmed',
                'override_reason' => $data['override_reason'] ?? null,
                'override_by' => isset($data['override_reason']) ? auth()->id() : null,
                'notes' => $data['notes'] ?? null,
            ]);

            // 3. Post to Vendor Ledger
            $lastBalance = $vendor->current_balance;
            $newBalance = $lastBalance + $sale->vendor_net_amount;

            VendorLedger::create([
                'marquee_id' => $vendor->marquee_id,
                'branch_id' => $sale->branch_id,
                'vendor_id' => $vendor->id,
                'vendor_sale_id' => $sale->id,
                'booking_id' => $sale->booking_id,
                'transaction_date' => $saleDate,
                'reference_number' => $sale->vendor_sale_number,
                'transaction_type' => 'sale_credit',
                'description' => "Vendor Sale for " . ($service?->service_name ?? $vendor->name) . " (Commission: Rs. " . number_format($sale->commission_amount, 2) . ")",
                'sale_amount' => $sale->sale_amount,
                'commission_amount' => $sale->commission_amount,
                'payment_amount' => 0.00,
                'running_balance' => $newBalance,
                'created_by' => auth()->id(),
            ]);

            // 4. Post Financial Journal Voucher to Accounting Module
            $this->postVendorSaleToAccounting($sale);

            return $sale;
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
        $revenueType = AccountType::where('name', 'Revenue')->orWhere('code', 'REV')->first();
        if (!$revenueType) {
            $revenueType = AccountType::create(['name' => 'Revenue', 'code' => 'REV', 'nature' => 'Income']);
        }

        $liabilityType = AccountType::where('name', 'Current Liabilities')->orWhere('code', 'CLIAB')->first();
        if (!$liabilityType) {
            $liabilityType = AccountType::create(['name' => 'Current Liabilities', 'code' => 'CLIAB', 'nature' => 'Liability']);
        }

        // Find/Create Vendor Commission Income Account
        $incomeAccount = Account::firstOrCreate(
            ['marquee_id' => $sale->marquee_id, 'name' => 'Vendor Commission Income'],
            [
                'account_code' => '4200-VEN',
                'account_type_id' => $revenueType->id,
                'nature' => 'Income',
                'description' => 'Income generated from event vendor sales commissions',
                'is_active' => true,
            ]
        );

        // Find/Create Vendor Payable Clearing Account
        $payableAccount = Account::firstOrCreate(
            ['marquee_id' => $sale->marquee_id, 'name' => 'Vendor Payable Clearing'],
            [
                'account_code' => '2150-VEN',
                'account_type_id' => $liabilityType->id,
                'nature' => 'Liability',
                'description' => 'Net liabilities payable to contracted event vendors',
                'is_active' => true,
            ]
        );

        if ($sale->commission_amount <= 0 && $sale->vendor_net_amount <= 0) {
            return null;
        }

        $fy = \App\Models\FinancialYear::where('marquee_id', $sale->marquee_id)->where('status', 'active')->first();
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
            'notes' => "Vendor Sale #" . $sale->vendor_sale_number . " (" . $sale->vendor->name . ") Commission Income",
            'status' => 'posted',
        ]);

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

        $payableAccount = Account::where('marquee_id', $settlement->marquee_id)
            ->where('name', 'Vendor Payable Clearing')
            ->first();

        if (!$payableAccount || $settlement->paid_amount <= 0) {
            return null;
        }

        $fy = \App\Models\FinancialYear::where('marquee_id', $settlement->marquee_id)->where('status', 'active')->first();
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
}
