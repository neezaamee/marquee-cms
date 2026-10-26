<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Booking;
use App\Models\BookingHistory;
use App\Models\CustomerLedger;
use App\Models\JournalVoucher;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RevenueRecognitionService
{
    protected AccountingService $accountingService;
    protected BookingFinancialService $financialService;

    public function __construct(AccountingService $accountingService, BookingFinancialService $financialService)
    {
        $this->accountingService = $accountingService;
        $this->financialService = $financialService;
    }

    /**
     * Determine if a booking is eligible for revenue recognition.
     */
    public function isEligibleForRecognition(Booking $booking): bool
    {
        if ($booking->is_revenue_recognized) {
            return false;
        }

        if (in_array($booking->booking_status, ['Cancelled', 'Rejected'])) {
            return false;
        }

        return true;
    }

    /**
     * Recognize revenue upon event completion in a strictly idempotent, atomic transaction.
     */
    public function recognizeRevenue(Booking $booking, ?string $recognitionDate = null, ?int $performedBy = null): JournalVoucher
    {
        return DB::transaction(function () use ($booking, $recognitionDate, $performedBy) {
            // Pessimistic Row Locking to prevent race conditions
            $booking = Booking::where('id', $booking->id)->lockForUpdate()->firstOrFail();

            // 1. Idempotency Check: if already recognized, return existing Journal Voucher
            if ($booking->is_revenue_recognized) {
                $existingJv = JournalVoucher::where('reference', "REV-REC-BK-{$booking->id}")->first();
                if ($existingJv) {
                    return $existingJv;
                }
            }

            if (in_array($booking->booking_status, ['Cancelled', 'Rejected'])) {
                throw new InvalidArgumentException("Cannot recognize revenue for a {$booking->booking_status} booking.");
            }

            $marqueeId = $booking->marquee_id ?? auth()->user()->getActiveMarqueeId();
            $branchId = $booking->branch_id;
            $userId = $performedBy ?? auth()->id();
            $recDate = $recognitionDate ?: ($booking->booking_date ? $booking->booking_date->format('Y-m-d') : date('Y-m-d'));

            // 2. Financial Amount Calculations
            // Pure Event Gross (Venue, Catering, Packages excluding security deposit)
            $eventGrandTotal = (float) ($booking->finalBill ? $booking->finalBill->grand_total : $booking->grand_total);
            $securityDeposit = (float) $booking->security_deposit;
            $eventGrossRevenue = max(0.00, $eventGrandTotal - $securityDeposit);

            // Vendor Charges included in the invoice (which were already billed to 1003 via VendorCommissionService)
            $vendorCharges = (float) $booking->vendorSales()
                ->whereIn('status', ['confirmed', 'settled'])
                ->where('include_in_invoice', true)
                ->sum('sale_amount');

            // Total Customer Bill (Gross Event + Vendor Services)
            $totalCustomerBill = $eventGrossRevenue + $vendorCharges;

            // Tax component (strictly on pure event charges)
            $taxAmount = (float) ($booking->finalBill ? $booking->finalBill->tax_amount : $booking->tax_amount);
            $netEventRevenue = max(0.00, $eventGrossRevenue - $taxAmount);

            // Total Advance Payments Held as Liability in Account 2003
            $advanceHeld = (float) $booking->advance_received;

            // Total advance released to satisfy the overall customer bill
            $totalAdvanceToRelease = min($advanceHeld, $totalCustomerBill);

            // Total unpaid customer obligation remaining across the entire booking
            $totalRemainingReceivable = max(0.00, $totalCustomerBill - $totalAdvanceToRelease);

            // Since VendorCommissionService already created a debit of $vendorCharges in 1003 (Accounts Receivable),
            // the target net receivable on 1003 after this recognition should be exactly $totalRemainingReceivable.
            // If totalRemainingReceivable < vendorCharges (e.g. fully paid), the difference must CREDIT 1003 to clear it!
            $netArAdjustment = round($totalRemainingReceivable - $vendorCharges, 2);

            // 3. Resolve Chart of Accounts
            $advanceLiabilityAccount = $this->financialService->resolveCoaAccount(
                $marqueeId,
                '2003',
                'Customer Advances / Contract Liabilities',
                'Liability',
                'CURRENT_LIABILITIES'
            );

            $receivableAccount = $this->financialService->resolveCoaAccount(
                $marqueeId,
                '1003',
                'Accounts Receivable',
                'Asset',
                'CURRENT_ASSETS'
            );

            $revenueAccount = $this->financialService->resolveCoaAccount(
                $marqueeId,
                '4001',
                'Hall Booking Revenue',
                'Income',
                'OPERATING_REVENUE'
            );

            $taxAccount = $this->financialService->resolveCoaAccount(
                $marqueeId,
                '2004',
                'Sales Tax Payable',
                'Liability',
                'CURRENT_LIABILITIES'
            );

            // 4. Construct Balanced Journal Voucher
            $header = [
                'marquee_id' => $marqueeId,
                'branch_id' => $branchId,
                'voucher_date' => $recDate,
                'reference' => "REV-REC-BK-{$booking->id}",
                'notes' => "Revenue Recognition for Booking #{$booking->booking_number} on Event Completion (Gross Event: Rs. " . number_format($eventGrossRevenue, 2) . ($vendorCharges > 0 ? ", Vendor Services: Rs. " . number_format($vendorCharges, 2) : "") . ")",
                'status' => 'posted',
            ];

            $items = [];

            // A. Debits
            // Release customer advance liability
            if ($totalAdvanceToRelease > 0) {
                $items[] = [
                    'account_id' => $advanceLiabilityAccount->id,
                    'debit' => $totalAdvanceToRelease,
                    'credit' => 0.00,
                    'narration' => "Release customer advance liability on event completion for Booking #{$booking->booking_number}",
                ];
            }

            // If remaining receivable exceeds existing vendor charges in 1003, debit the incremental receivable
            if ($netArAdjustment > 0) {
                $items[] = [
                    'account_id' => $receivableAccount->id,
                    'debit' => $netArAdjustment,
                    'credit' => 0.00,
                    'narration' => "Unpaid customer balance booked to Accounts Receivable for Booking #{$booking->booking_number}",
                ];
            }

            // B. Credits
            // Earned Net Event Revenue (pure hall venue/catering income)
            if ($netEventRevenue > 0) {
                $items[] = [
                    'account_id' => $revenueAccount->id,
                    'debit' => 0.00,
                    'credit' => $netEventRevenue,
                    'narration' => "Earned event revenue recognized for Booking #{$booking->booking_number}",
                ];
            }

            // Sales Tax Component
            if ($taxAmount > 0) {
                $items[] = [
                    'account_id' => $taxAccount->id,
                    'debit' => 0.00,
                    'credit' => $taxAmount,
                    'narration' => "Sales tax payable recognized for Booking #{$booking->booking_number}",
                ];
            }

            // If advance covered all or part of vendor charges, credit 1003 to clear/reduce the vendor receivable!
            if ($netArAdjustment < 0) {
                $items[] = [
                    'account_id' => $receivableAccount->id,
                    'debit' => 0.00,
                    'credit' => abs($netArAdjustment),
                    'narration' => "Customer advance applied to clear vendor service receivable for Booking #{$booking->booking_number}",
                ];
            }

            $jv = $this->accountingService->createJournalVoucher($header, $items);

            // 5. Post to Customer Ledger Sub-ledger
            $lastCustomerBalance = (float) CustomerLedger::where('customer_id', $booking->customer_id)
                ->orderBy('transaction_date', 'desc')
                ->orderBy('id', 'desc')
                ->value('running_balance') ?? 0.00;

            // Invoice / Revenue Recognition Debit (increases customer obligation by total customer bill)
            $balanceAfterInvoice = $lastCustomerBalance + $totalCustomerBill;

            CustomerLedger::create([
                'marquee_id' => $marqueeId,
                'branch_id' => $branchId,
                'customer_id' => $booking->customer_id,
                'booking_id' => $booking->id,
                'journal_voucher_id' => $jv->id,
                'transaction_date' => $recDate,
                'transaction_type' => 'revenue_recognition',
                'reference_number' => $jv->voucher_no,
                'description' => "Event completed: Recognized booking revenue of Rs. " . number_format($totalCustomerBill, 2) . " for Booking #{$booking->booking_number}",
                'debit' => $totalCustomerBill,
                'credit' => 0.00,
                'running_balance' => $balanceAfterInvoice,
                'created_by' => $userId,
            ]);

            // 6. Update Booking Financial State
            $financialStatus = ($totalRemainingReceivable <= 0.01) ? 'Settled' : 'Partially Paid';
            $paymentStatus = ($totalRemainingReceivable <= 0.01) ? 'Paid' : ($advanceHeld > 0 ? 'Partially Paid' : 'Unpaid');

            $booking->updateQuietly([
                'booking_status' => 'Completed',
                'is_revenue_recognized' => true,
                'revenue_recognized' => $totalCustomerBill,
                'receivable_amount' => $totalRemainingReceivable,
                'advance_received' => 0.00, // Transferred out of liability
                'revenue_recognized_at' => now(),
                'financial_status' => $financialStatus,
                'payment_status' => $paymentStatus,
            ]);

            // 7. Audit History Entry
            BookingHistory::create([
                'booking_id' => $booking->id,
                'user_id' => $userId,
                'status_from' => $booking->getOriginal('booking_status'),
                'status_to' => 'Completed',
                'payment_status_from' => $booking->getOriginal('payment_status'),
                'payment_status_to' => $paymentStatus,
                'notes' => "Event Completed & Revenue Recognized: Rs. " . number_format($totalCustomerBill, 2) . ". Advance Released: Rs. " . number_format($totalAdvanceToRelease, 2) . ". Accounts Receivable: Rs. " . number_format($totalRemainingReceivable, 2) . ". Voucher: {$jv->voucher_no}.",
            ]);

            return $jv;
        });
    }

    /**
     * Re-sync and update an existing Revenue Recognition Journal Voucher for an already-completed booking.
     */
    public function syncRevenueRecognitionVoucher(Booking $booking): ?JournalVoucher
    {
        return DB::transaction(function () use ($booking) {
            $booking = Booking::where('id', $booking->id)->lockForUpdate()->firstOrFail();
            if (!$booking->is_revenue_recognized) {
                return $this->recognizeRevenue($booking);
            }

            $jv = JournalVoucher::where('reference', "REV-REC-BK-{$booking->id}")->first();
            if (!$jv) {
                return $this->recognizeRevenue($booking);
            }

            $eventGrandTotal = (float) ($booking->finalBill ? $booking->finalBill->grand_total : $booking->grand_total);
            $securityDeposit = (float) $booking->security_deposit;
            $eventGrossRevenue = max(0.00, $eventGrandTotal - $securityDeposit);

            $vendorCharges = (float) $booking->vendorSales()
                ->whereIn('status', ['confirmed', 'settled'])
                ->where('include_in_invoice', true)
                ->sum('sale_amount');

            $totalCustomerBill = $eventGrossRevenue + $vendorCharges;
            $taxAmount = (float) ($booking->finalBill ? $booking->finalBill->tax_amount : $booking->tax_amount);
            $netEventRevenue = max(0.00, $eventGrossRevenue - $taxAmount);

            $totalPaid = (float) $booking->total_paid;
            $totalAdvanceToRelease = min($totalPaid, $totalCustomerBill);
            $totalRemainingReceivable = max(0.00, $totalCustomerBill - $totalAdvanceToRelease);
            $netArAdjustment = round($totalRemainingReceivable - $vendorCharges, 2);

            $marqueeId = $booking->marquee_id ?? auth()->user()->getActiveMarqueeId();
            $advanceLiabilityAccount = $this->financialService->resolveCoaAccount($marqueeId, '2003', 'Customer Advances / Contract Liabilities', 'Liability', 'CURRENT_LIABILITIES');
            $receivableAccount = $this->financialService->resolveCoaAccount($marqueeId, '1003', 'Accounts Receivable', 'Asset', 'CURRENT_ASSETS');
            $revenueAccount = $this->financialService->resolveCoaAccount($marqueeId, '4001', 'Hall Booking Revenue', 'Income', 'OPERATING_REVENUE');
            $taxAccount = $this->financialService->resolveCoaAccount($marqueeId, '2004', 'Sales Tax Payable', 'Liability', 'CURRENT_LIABILITIES');

            // Delete old items and insert corrected balanced items
            $jv->items()->delete();

            if ($totalAdvanceToRelease > 0) {
                $jv->items()->create([
                    'account_id' => $advanceLiabilityAccount->id,
                    'debit' => $totalAdvanceToRelease,
                    'credit' => 0.00,
                    'narration' => "Release customer advance liability on event completion for Booking #{$booking->booking_number}",
                ]);
            }

            if ($netArAdjustment > 0) {
                $jv->items()->create([
                    'account_id' => $receivableAccount->id,
                    'debit' => $netArAdjustment,
                    'credit' => 0.00,
                    'narration' => "Unpaid customer balance booked to Accounts Receivable for Booking #{$booking->booking_number}",
                ]);
            }

            if ($netEventRevenue > 0) {
                $jv->items()->create([
                    'account_id' => $revenueAccount->id,
                    'debit' => 0.00,
                    'credit' => $netEventRevenue,
                    'narration' => "Earned event revenue recognized for Booking #{$booking->booking_number}",
                ]);
            }

            if ($taxAmount > 0) {
                $jv->items()->create([
                    'account_id' => $taxAccount->id,
                    'debit' => 0.00,
                    'credit' => $taxAmount,
                    'narration' => "Sales tax payable recognized for Booking #{$booking->booking_number}",
                ]);
            }

            if ($netArAdjustment < 0) {
                $jv->items()->create([
                    'account_id' => $receivableAccount->id,
                    'debit' => 0.00,
                    'credit' => abs($netArAdjustment),
                    'narration' => "Customer advance applied to clear vendor service receivable for Booking #{$booking->booking_number}",
                ]);
            }

            $booking->updateQuietly([
                'revenue_recognized' => $totalCustomerBill,
                'receivable_amount' => $totalRemainingReceivable,
                'advance_received' => 0.00,
                'financial_status' => ($totalRemainingReceivable <= 0.01) ? 'Settled' : 'Partially Paid',
                'payment_status' => ($totalRemainingReceivable <= 0.01) ? 'Paid' : 'Partially Paid',
            ]);

            return $jv;
        });
    }
}
