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
            // Gross Invoiced Amount (excluding security deposit which is a held liability, not earned revenue)
            $effectiveTotal = (float) $booking->effective_invoice_amount;
            $securityDeposit = (float) $booking->security_deposit;
            $grossEarnedRevenue = max(0.00, $effectiveTotal - $securityDeposit);

            // Tax component
            $taxAmount = (float) ($booking->finalBill ? $booking->finalBill->tax_amount : $booking->tax_amount);
            $netEventRevenue = max(0.00, $grossEarnedRevenue - $taxAmount);

            // Total Advance Payments Held as Liability
            $advanceHeld = (float) $booking->advance_received;

            // If advance was greater than gross earned revenue (rare overpayment), cap advance released to revenue
            $advanceToRelease = min($advanceHeld, $grossEarnedRevenue);
            $remainingReceivable = max(0.00, $grossEarnedRevenue - $advanceToRelease);

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
                'notes' => "Revenue Recognition for Booking #{$booking->booking_number} on Event Completion (Gross: Rs. " . number_format($grossEarnedRevenue, 2) . ")",
                'status' => 'posted',
            ];

            $items = [];

            // A. Debits
            // Release advance liability portion
            if ($advanceToRelease > 0) {
                $items[] = [
                    'account_id' => $advanceLiabilityAccount->id,
                    'debit' => $advanceToRelease,
                    'credit' => 0.00,
                    'narration' => "Release customer advance liability on event completion for Booking #{$booking->booking_number}",
                ];
            }

            // Create accounts receivable for unpaid portion
            if ($remainingReceivable > 0) {
                $items[] = [
                    'account_id' => $receivableAccount->id,
                    'debit' => $remainingReceivable,
                    'credit' => 0.00,
                    'narration' => "Unpaid customer balance booked to Accounts Receivable for Booking #{$booking->booking_number}",
                ];
            }

            // B. Credits
            // Net Event Revenue
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

            $jv = $this->accountingService->createJournalVoucher($header, $items);

            // 5. Post to Customer Ledger Sub-ledger
            $lastCustomerBalance = (float) CustomerLedger::where('customer_id', $booking->customer_id)
                ->orderBy('transaction_date', 'desc')
                ->orderBy('id', 'desc')
                ->value('running_balance') ?? 0.00;

            // Invoice / Revenue Recognition Debit (increases customer obligation)
            $balanceAfterInvoice = $lastCustomerBalance + $grossEarnedRevenue;

            CustomerLedger::create([
                'marquee_id' => $marqueeId,
                'branch_id' => $branchId,
                'customer_id' => $booking->customer_id,
                'booking_id' => $booking->id,
                'journal_voucher_id' => $jv->id,
                'transaction_date' => $recDate,
                'transaction_type' => 'revenue_recognition',
                'reference_number' => $jv->voucher_no,
                'description' => "Event completed: Recognized booking revenue of Rs. " . number_format($grossEarnedRevenue, 2) . " for Booking #{$booking->booking_number}",
                'debit' => $grossEarnedRevenue,
                'credit' => 0.00,
                'running_balance' => $balanceAfterInvoice,
                'created_by' => $userId,
            ]);

            // 6. Update Booking Financial State
            $financialStatus = ($remainingReceivable <= 0.01) ? 'Settled' : 'Partially Paid';
            $paymentStatus = ($remainingReceivable <= 0.01) ? 'Paid' : ($advanceHeld > 0 ? 'Partially Paid' : 'Unpaid');

            $booking->updateQuietly([
                'booking_status' => 'Completed',
                'is_revenue_recognized' => true,
                'revenue_recognized' => $grossEarnedRevenue,
                'receivable_amount' => $remainingReceivable,
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
                'notes' => "Event Completed & Revenue Recognized: Rs. " . number_format($grossEarnedRevenue, 2) . ". Advance Released: Rs. " . number_format($advanceToRelease, 2) . ". Accounts Receivable: Rs. " . number_format($remainingReceivable, 2) . ". Voucher: {$jv->voucher_no}.",
            ]);

            return $jv;
        });
    }
}
