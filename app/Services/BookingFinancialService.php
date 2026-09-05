<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Booking;
use App\Models\BookingHistory;
use App\Models\BookingPayment;
use App\Models\CashBankAccount;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\JournalVoucher;
use App\Models\JournalVoucherItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BookingFinancialService
{
    protected AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    /**
     * Resolve or create a Chart of Accounts account for a marquee tenant.
     */
    public function resolveCoaAccount(int $marqueeId, string $accountCode, string $defaultName, string $nature, string $typeCode): Account
    {
        $type = AccountType::firstOrCreate(
            ['code' => $typeCode],
            ['name' => ucwords(str_replace('_', ' ', strtolower($typeCode))), 'nature' => $nature]
        );

        $parentCode = substr($accountCode, 0, 1) . '000';
        $parent = Account::where('marquee_id', $marqueeId)->where('account_code', $parentCode)->first();

        return Account::withoutGlobalScope('tenant')->firstOrCreate(
            [
                'marquee_id' => $marqueeId,
                'account_code' => $accountCode,
            ],
            [
                'name' => $defaultName,
                'parent_id' => $parent?->id,
                'account_type_id' => $type->id,
                'nature' => $nature,
                'is_active' => true,
                'system_generated' => true,
                'description' => "System COA account for {$defaultName}",
            ]
        );
    }

    /**
     * Generate unique sequential payment number (e.g. PAY-2026-00001).
     */
    public function generateNextPaymentNumber(?int $marqueeId = null): string
    {
        $year = date('Y');
        $latestPayment = BookingPayment::orderBy('id', 'desc')->first();
        $nextSeq = $latestPayment ? ($latestPayment->id + 1) : 1;
        return "PAY-{$year}-" . str_pad((string)$nextSeq, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Stage 1: Record a customer payment transaction (Manager Payment Entry).
     * Saved as PENDING_POSTING. Does NOT affect Cash/Bank balance or create Journal Voucher.
     */
    public function recordPayment(Booking $booking, array $params): BookingPayment
    {
        return DB::transaction(function () use ($booking, $params) {
            $booking = Booking::where('id', $booking->id)->lockForUpdate()->firstOrFail();

            $amount = (float) $params['amount'];
            if ($amount <= 0) {
                throw new InvalidArgumentException("Payment amount must be greater than zero.");
            }

            $paymentDate = $params['payment_date'] ?? date('Y-m-d');
            $paymentMethod = $params['payment_method'] ?? 'Cash';
            $transactionReference = $params['transaction_reference'] ?? null;
            $chequeNumber = $params['cheque_number'] ?? null;
            $bankReference = $params['bank_reference'] ?? null;
            $notes = $params['notes'] ?? null;
            $recordedBy = $params['recorded_by'] ?? auth()->id();
            $paymentType = $params['payment_type'] ?? ($booking->is_revenue_recognized ? 'receivable_payment' : 'advance');
            $paymentNumber = $this->generateNextPaymentNumber($booking->marquee_id);

            // Create BookingPayment record in pending_posting status
            $payment = BookingPayment::create([
                'payment_number' => $paymentNumber,
                'booking_id' => $booking->id,
                'vendor_sale_id' => $params['vendor_sale_id'] ?? null,
                'account_id' => $params['account_id'] ?? null,
                'amount' => $amount,
                'status' => 'pending_posting',
                'payment_date' => $paymentDate,
                'payment_method' => $paymentMethod,
                'payment_type' => $paymentType,
                'transaction_reference' => $transactionReference,
                'cheque_number' => $chequeNumber,
                'bank_reference' => $bankReference,
                'journal_voucher_id' => null,
                'recorded_by' => $recordedBy,
                'notes' => $notes,
            ]);

            // Add Booking History Audit Log
            BookingHistory::create([
                'booking_id' => $booking->id,
                'user_id' => $recordedBy,
                'status_from' => $booking->booking_status,
                'status_to' => $booking->booking_status,
                'payment_status_from' => $booking->payment_status,
                'payment_status_to' => $booking->payment_status,
                'notes' => "Payment #{$paymentNumber} of Rs. " . number_format($amount, 2) . " recorded via {$paymentMethod}. Status: Pending Accountant Posting.",
            ]);

            $this->recalculateBookingFinancials($booking);

            return $payment;
        });
    }

    /**
     * Stage 2: Verify and POST payment into financial accounts (Accountant Action).
     * Creates double-entry Journal Voucher, updates Cash/Bank account, and credits Customer Ledger.
     */
    public function postPayment(BookingPayment $payment, array $params): BookingPayment
    {
        return DB::transaction(function () use ($payment, $params) {
            // Row-level lock on the payment record for concurrency and duplicate protection
            $payment = BookingPayment::where('id', $payment->id)->lockForUpdate()->firstOrFail();

            if ($payment->status === 'posted' || $payment->journal_voucher_id) {
                throw new InvalidArgumentException("This payment has already been posted.");
            }

            if (!in_array($payment->status, ['pending_posting', 'received'])) {
                throw new InvalidArgumentException("Only pending payments can be posted to financial accounts.");
            }

            $booking = Booking::where('id', $payment->booking_id)->lockForUpdate()->firstOrFail();
            $marqueeId = $booking->marquee_id ?? auth()->user()->getActiveMarqueeId();
            $branchId = $booking->branch_id;
            $postedBy = $params['posted_by'] ?? auth()->id();
            $postingDate = $params['posting_date'] ?? ($payment->payment_date ? $payment->payment_date->format('Y-m-d') : date('Y-m-d'));
            $accountantNotes = $params['accountant_notes'] ?? null;

            // 1. Resolve Target Receiving Cash/Bank Account
            $targetAccountId = $params['account_id'] ?? $payment->account_id;
            if (!$targetAccountId) {
                $targetCode = (strtolower($payment->payment_method) === 'cash') ? '1001' : '1002';
                $defaultAcc = $this->resolveCoaAccount(
                    $marqueeId,
                    $targetCode,
                    strtolower($payment->payment_method) === 'cash' ? 'Cash' : 'Bank',
                    'Asset',
                    'CURRENT_ASSETS'
                );
                $targetAccountId = $defaultAcc->id;
            }

            $receivingAccount = Account::withoutGlobalScope('tenant')
                ->where('marquee_id', $marqueeId)
                ->findOrFail($targetAccountId);

            // 2. Determine Balancing Account
            // If revenue is not yet recognized: CUSTOMER ADVANCE LIABILITY (2003)
            // If revenue is recognized: ACCOUNTS RECEIVABLE (1003)
            if (!$booking->is_revenue_recognized) {
                $balancingAccount = $this->resolveCoaAccount(
                    $marqueeId,
                    '2003',
                    'Customer Advances / Contract Liabilities',
                    'Liability',
                    'CURRENT_LIABILITIES'
                );
                $balancingNarration = "Customer Advance Liability for Booking #{$booking->booking_number} (Payment #{$payment->payment_number})";
                $ledgerType = 'advance_payment';
                $ledgerDesc = "Advance payment received via {$payment->payment_method} for Booking #{$booking->booking_number}";
            } else {
                $balancingAccount = $this->resolveCoaAccount(
                    $marqueeId,
                    '1003',
                    'Accounts Receivable',
                    'Asset',
                    'CURRENT_ASSETS'
                );
                $balancingNarration = "Receivable settlement for Booking #{$booking->booking_number} (Payment #{$payment->payment_number})";
                $ledgerType = 'receivable_payment';
                $ledgerDesc = "Receivable payment received via {$payment->payment_method} for Booking #{$booking->booking_number}";
            }

            // 3. Create Double-Entry Accounting Journal Voucher
            $header = [
                'marquee_id' => $marqueeId,
                'branch_id' => $branchId,
                'voucher_date' => $postingDate,
                'reference' => $payment->payment_number ?: ("PAY-BK-{$booking->id}-" . time()),
                'notes' => "Posting Payment #{$payment->payment_number} of Rs. " . number_format($payment->amount, 2) . " for Booking #{$booking->booking_number} ({$payment->payment_type}) into {$receivingAccount->name}" . ($accountantNotes ? " - Notes: {$accountantNotes}" : ""),
                'status' => 'posted',
            ];

            $items = [
                [
                    'account_id' => $receivingAccount->id,
                    'debit' => $payment->amount,
                    'credit' => 0.00,
                    'narration' => "Funds posted via {$payment->payment_method} into {$receivingAccount->name} (Payment #{$payment->payment_number})",
                ],
                [
                    'account_id' => $balancingAccount->id,
                    'debit' => 0.00,
                    'credit' => $payment->amount,
                    'narration' => $balancingNarration,
                ],
            ];

            $jv = $this->accountingService->createJournalVoucher($header, $items);

            // 4. Update Customer Ledger
            $lastCustomerBalance = (float) CustomerLedger::where('customer_id', $booking->customer_id)
                ->orderBy('transaction_date', 'desc')
                ->orderBy('id', 'desc')
                ->value('running_balance') ?? 0.00;

            $newCustomerBalance = $lastCustomerBalance - $payment->amount;

            CustomerLedger::create([
                'marquee_id' => $marqueeId,
                'branch_id' => $branchId,
                'customer_id' => $booking->customer_id,
                'booking_id' => $booking->id,
                'booking_payment_id' => $payment->id,
                'journal_voucher_id' => $jv->id,
                'transaction_date' => $postingDate,
                'transaction_type' => $ledgerType,
                'reference_number' => $payment->payment_number ?: $jv->voucher_no,
                'description' => $ledgerDesc . ($payment->notes ? " - {$payment->notes}" : ""),
                'debit' => 0.00,
                'credit' => $payment->amount,
                'running_balance' => $newCustomerBalance,
                'created_by' => $postedBy,
            ]);

            // 5. Update Payment Record to POSTED
            $payment->update([
                'status' => 'posted',
                'account_id' => $receivingAccount->id,
                'journal_voucher_id' => $jv->id,
                'posted_by' => $postedBy,
                'posted_at' => Carbon::parse($postingDate),
                'accountant_notes' => $accountantNotes,
            ]);

            // 6. Recalculate Booking Financial Metrics
            $this->recalculateBookingFinancials($booking);

            // 7. Booking History Log
            BookingHistory::create([
                'booking_id' => $booking->id,
                'user_id' => $postedBy,
                'status_from' => $booking->booking_status,
                'status_to' => $booking->booking_status,
                'payment_status_from' => $booking->getOriginal('payment_status'),
                'payment_status_to' => $booking->payment_status,
                'notes' => "Payment #{$payment->payment_number} (Rs. " . number_format($payment->amount, 2) . ") verified and POSTED to {$receivingAccount->name}. Voucher: {$jv->voucher_no}.",
            ]);

            return $payment;
        });
    }

    /**
     * Reject a pending payment (Accountant Rejection).
     */
    public function rejectPayment(BookingPayment $payment, string $reason, ?int $userId = null): BookingPayment
    {
        return DB::transaction(function () use ($payment, $reason, $userId) {
            $payment = BookingPayment::where('id', $payment->id)->lockForUpdate()->firstOrFail();

            if (!in_array($payment->status, ['pending_posting', 'received'])) {
                throw new InvalidArgumentException("Only pending payments can be rejected.");
            }

            $booking = Booking::where('id', $payment->booking_id)->firstOrFail();
            $actorId = $userId ?? auth()->id();

            $payment->update([
                'status' => 'rejected',
                'rejected_by' => $actorId,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);

            BookingHistory::create([
                'booking_id' => $booking->id,
                'user_id' => $actorId,
                'status_from' => $booking->booking_status,
                'status_to' => $booking->booking_status,
                'notes' => "Payment #{$payment->payment_number} (Rs. " . number_format($payment->amount, 2) . ") was REJECTED by accountant. Reason: {$reason}",
            ]);

            $this->recalculateBookingFinancials($booking);

            return $payment;
        });
    }

    /**
     * Reverse a previously POSTED payment with an offsetting Journal Voucher.
     */
    public function reversePayment(BookingPayment $payment, string $reason, ?int $userId = null): BookingPayment
    {
        return DB::transaction(function () use ($payment, $reason, $userId) {
            $payment = BookingPayment::where('id', $payment->id)->lockForUpdate()->firstOrFail();

            if ($payment->status !== 'posted') {
                throw new InvalidArgumentException("Only posted payments can be reversed.");
            }

            $booking = Booking::where('id', $payment->booking_id)->lockForUpdate()->firstOrFail();
            $marqueeId = $booking->marquee_id ?? auth()->user()->getActiveMarqueeId();
            $branchId = $booking->branch_id;
            $actorId = $userId ?? auth()->id();
            $reversalDate = date('Y-m-d');

            // Original debited asset account (Cash/Bank)
            $receivingAccountId = $payment->account_id;
            if (!$receivingAccountId) {
                $targetCode = (strtolower($payment->payment_method) === 'cash') ? '1001' : '1002';
                $defaultAcc = $this->resolveCoaAccount($marqueeId, $targetCode, strtolower($payment->payment_method) === 'cash' ? 'Cash' : 'Bank', 'Asset', 'CURRENT_ASSETS');
                $receivingAccountId = $defaultAcc->id;
            }
            $receivingAccount = Account::withoutGlobalScope('tenant')->findOrFail($receivingAccountId);

            // Original balancing account
            if (!$booking->is_revenue_recognized) {
                $balancingAccount = $this->resolveCoaAccount($marqueeId, '2003', 'Customer Advances / Contract Liabilities', 'Liability', 'CURRENT_LIABILITIES');
                $ledgerType = 'reversal';
            } else {
                $balancingAccount = $this->resolveCoaAccount($marqueeId, '1003', 'Accounts Receivable', 'Asset', 'CURRENT_ASSETS');
                $ledgerType = 'reversal';
            }

            // Create Reversing Journal Voucher:
            // Debit Liability/Receivable (reversing the credit)
            // Credit Cash/Bank (reversing the debit)
            $header = [
                'marquee_id' => $marqueeId,
                'branch_id' => $branchId,
                'voucher_date' => $reversalDate,
                'reference' => "REV-{$payment->payment_number}",
                'notes' => "Reversal of Payment #{$payment->payment_number} (Rs. " . number_format($payment->amount, 2) . ") for Booking #{$booking->booking_number}. Reason: {$reason}",
                'status' => 'posted',
            ];

            $items = [
                [
                    'account_id' => $balancingAccount->id,
                    'debit' => $payment->amount, // DR liability/AR reverses original credit
                    'credit' => 0.00,
                    'narration' => "Reversal of advance liability/receivable for Payment #{$payment->payment_number}",
                ],
                [
                    'account_id' => $receivingAccount->id,
                    'debit' => 0.00,
                    'credit' => $payment->amount, // CR Cash/Bank reverses original debit
                    'narration' => "Reversal credit from {$receivingAccount->name} for Payment #{$payment->payment_number}",
                ],
            ];

            $revJv = $this->accountingService->createJournalVoucher($header, $items);

            // Update Customer Ledger with reversal debit
            $lastCustomerBalance = (float) CustomerLedger::where('customer_id', $booking->customer_id)
                ->orderBy('transaction_date', 'desc')
                ->orderBy('id', 'desc')
                ->value('running_balance') ?? 0.00;

            $newCustomerBalance = $lastCustomerBalance + $payment->amount;

            CustomerLedger::create([
                'marquee_id' => $marqueeId,
                'branch_id' => $branchId,
                'customer_id' => $booking->customer_id,
                'booking_id' => $booking->id,
                'booking_payment_id' => $payment->id,
                'journal_voucher_id' => $revJv->id,
                'transaction_date' => $reversalDate,
                'transaction_type' => $ledgerType,
                'reference_number' => "REV-{$payment->payment_number}",
                'description' => "Reversal of Payment #{$payment->payment_number}. Reason: {$reason}",
                'debit' => $payment->amount,
                'credit' => 0.00,
                'running_balance' => $newCustomerBalance,
                'created_by' => $actorId,
            ]);

            // Update Payment Status to REVERSED
            $payment->update([
                'status' => 'reversed',
                'reversed_by' => $actorId,
                'reversed_at' => now(),
                'reversal_reason' => $reason,
                'reversal_journal_voucher_id' => $revJv->id,
            ]);

            $this->recalculateBookingFinancials($booking);

            BookingHistory::create([
                'booking_id' => $booking->id,
                'user_id' => $actorId,
                'status_from' => $booking->booking_status,
                'status_to' => $booking->booking_status,
                'notes' => "Payment #{$payment->payment_number} (Rs. " . number_format($payment->amount, 2) . ") was REVERSED by accountant. Reversal Voucher: {$revJv->voucher_no}. Reason: {$reason}",
            ]);

            return $payment;
        });
    }

    /**
     * Record a customer refund (e.g. pre-event advance refund).
     */
    public function recordRefund(Booking $booking, array $params): BookingPayment
    {
        return DB::transaction(function () use ($booking, $params) {
            $booking = Booking::where('id', $booking->id)->lockForUpdate()->firstOrFail();

            $amount = (float) $params['amount'];
            if ($amount <= 0) {
                throw new InvalidArgumentException("Refund amount must be greater than zero.");
            }

            if (!$booking->is_revenue_recognized && $amount > (float) $booking->advance_received) {
                throw new InvalidArgumentException("Refund amount (Rs. {$amount}) cannot exceed current advance liability held (Rs. {$booking->advance_received}).");
            }

            $refundDate = $params['refund_date'] ?? date('Y-m-d');
            $paymentMethod = $params['payment_method'] ?? 'Cash';
            $transactionReference = $params['transaction_reference'] ?? ("REF-BK-{$booking->id}-" . time());
            $notes = $params['notes'] ?? 'Customer advance refund';
            $recordedBy = $params['recorded_by'] ?? auth()->id();
            $marqueeId = $booking->marquee_id ?? auth()->user()->getActiveMarqueeId();
            $branchId = $booking->branch_id;
            $paymentNumber = $this->generateNextPaymentNumber($marqueeId);

            // Resolve Disbursing Cash/Bank Account
            $disbursingAccountId = $params['account_id'] ?? null;
            if (!$disbursingAccountId) {
                $targetCode = (strtolower($paymentMethod) === 'cash') ? '1001' : '1002';
                $defaultAcc = $this->resolveCoaAccount($marqueeId, $targetCode, strtolower($paymentMethod) === 'cash' ? 'Cash' : 'Bank', 'Asset', 'CURRENT_ASSETS');
                $disbursingAccountId = $defaultAcc->id;
            }
            $disbursingAccount = Account::withoutGlobalScope('tenant')->findOrFail($disbursingAccountId);

            // Reverses Customer Advance Liability
            $advanceLiabilityAccount = $this->resolveCoaAccount($marqueeId, '2003', 'Customer Advances / Contract Liabilities', 'Liability', 'CURRENT_LIABILITIES');

            $header = [
                'marquee_id' => $marqueeId,
                'branch_id' => $branchId,
                'voucher_date' => $refundDate,
                'reference' => $transactionReference,
                'notes' => "Refund of Rs. " . number_format($amount, 2) . " issued for Booking #{$booking->booking_number}",
                'status' => 'posted',
            ];

            $items = [
                [
                    'account_id' => $advanceLiabilityAccount->id,
                    'debit' => $amount, // Debit liability reduces customer advance liability
                    'credit' => 0.00,
                    'narration' => "Advance liability release on refund for Booking #{$booking->booking_number}",
                ],
                [
                    'account_id' => $disbursingAccount->id,
                    'debit' => 0.00,
                    'credit' => $amount, // Credit asset reduces cash/bank
                    'narration' => "Refund disbursed via {$paymentMethod} from {$disbursingAccount->name}",
                ],
            ];

            $jv = $this->accountingService->createJournalVoucher($header, $items);

            $payment = BookingPayment::create([
                'payment_number' => $paymentNumber,
                'booking_id' => $booking->id,
                'account_id' => $disbursingAccount->id,
                'amount' => $amount,
                'status' => 'posted',
                'payment_date' => $refundDate,
                'payment_method' => $paymentMethod,
                'payment_type' => 'refund',
                'transaction_reference' => $transactionReference,
                'journal_voucher_id' => $jv->id,
                'recorded_by' => $recordedBy,
                'posted_by' => $recordedBy,
                'posted_at' => Carbon::parse($refundDate),
                'notes' => $notes,
            ]);

            // Customer Ledger Update
            $lastCustomerBalance = (float) CustomerLedger::where('customer_id', $booking->customer_id)
                ->orderBy('transaction_date', 'desc')
                ->orderBy('id', 'desc')
                ->value('running_balance') ?? 0.00;

            $newCustomerBalance = $lastCustomerBalance + $amount;

            CustomerLedger::create([
                'marquee_id' => $marqueeId,
                'branch_id' => $branchId,
                'customer_id' => $booking->customer_id,
                'booking_id' => $booking->id,
                'booking_payment_id' => $payment->id,
                'journal_voucher_id' => $jv->id,
                'transaction_date' => $refundDate,
                'transaction_type' => 'refund',
                'reference_number' => $transactionReference,
                'description' => "Advance refund of Rs. " . number_format($amount, 2) . " disbursed via {$paymentMethod}. Notes: {$notes}",
                'debit' => $amount,
                'credit' => 0.00,
                'running_balance' => $newCustomerBalance,
                'created_by' => $recordedBy,
            ]);

            $this->recalculateBookingFinancials($booking);

            BookingHistory::create([
                'booking_id' => $booking->id,
                'user_id' => $recordedBy,
                'status_from' => $booking->booking_status,
                'status_to' => $booking->booking_status,
                'payment_status_from' => $booking->getOriginal('payment_status'),
                'payment_status_to' => $booking->payment_status,
                'notes' => "Refunded Rs. " . number_format($amount, 2) . " via {$paymentMethod}. Voucher: {$jv->voucher_no}.",
            ]);

            return $payment;
        });
    }

    /**
     * Process booking cancellation with refundable portion and forfeiture/cancellation charges.
     */
    public function processCancellation(Booking $booking, array $params): array
    {
        return DB::transaction(function () use ($booking, $params) {
            $booking = Booking::where('id', $booking->id)->lockForUpdate()->firstOrFail();

            $advanceHeld = (float) $booking->advance_received;
            $refundAmount = isset($params['refund_amount']) ? floatval($params['refund_amount']) : 0.00;
            $cancellationFee = isset($params['cancellation_fee']) ? floatval($params['cancellation_fee']) : 0.00;
            $cancelReason = $params['reason'] ?? 'Booking cancelled by customer';
            $cancelDate = $params['cancellation_date'] ?? date('Y-m-d');
            $paymentMethod = $params['payment_method'] ?? 'Cash';
            $recordedBy = $params['recorded_by'] ?? auth()->id();
            $marqueeId = $booking->marquee_id ?? auth()->user()->getActiveMarqueeId();
            $branchId = $booking->branch_id;

            if (round($refundAmount + $cancellationFee, 2) > round($advanceHeld, 2)) {
                throw new InvalidArgumentException("Refund (Rs. {$refundAmount}) + Cancellation Fee (Rs. {$cancellationFee}) cannot exceed total advance held (Rs. {$advanceHeld}).");
            }

            // Accounts
            $advanceLiabilityAccount = $this->resolveCoaAccount($marqueeId, '2003', 'Customer Advances / Contract Liabilities', 'Liability', 'CURRENT_LIABILITIES');
            $cancellationIncomeAccount = $this->resolveCoaAccount($marqueeId, '4004', 'Cancellation Charges Income', 'Income', 'OPERATING_REVENUE');

            $disbursingAccountId = $params['account_id'] ?? null;
            if (!$disbursingAccountId) {
                $targetCode = (strtolower($paymentMethod) === 'cash') ? '1001' : '1002';
                $defaultAcc = $this->resolveCoaAccount($marqueeId, $targetCode, strtolower($paymentMethod) === 'cash' ? 'Cash' : 'Bank', 'Asset', 'CURRENT_ASSETS');
                $disbursingAccountId = $defaultAcc->id;
            }
            $disbursingAccount = Account::withoutGlobalScope('tenant')->findOrFail($disbursingAccountId);

            // Generate Cancellation Journal Voucher
            $header = [
                'marquee_id' => $marqueeId,
                'branch_id' => $branchId,
                'voucher_date' => $cancelDate,
                'reference' => "CNCL-BK-{$booking->id}-" . time(),
                'notes' => "Booking #{$booking->booking_number} Cancellation Settlement: Refund Rs. " . number_format($refundAmount, 2) . ", Fee Rs. " . number_format($cancellationFee, 2),
                'status' => 'posted',
            ];

            $items = [];

            // 1. Full release of advance liability
            if ($advanceHeld > 0) {
                $items[] = [
                    'account_id' => $advanceLiabilityAccount->id,
                    'debit' => $advanceHeld,
                    'credit' => 0.00,
                    'narration' => "Release advance liability for cancelled Booking #{$booking->booking_number}",
                ];
            }

            // 2. Refund cash/bank credit
            if ($refundAmount > 0) {
                $items[] = [
                    'account_id' => $disbursingAccount->id,
                    'debit' => 0.00,
                    'credit' => $refundAmount,
                    'narration' => "Refund payment disbursed via {$paymentMethod} to customer",
                ];
            }

            // 3. Cancellation penalty earned income
            if ($cancellationFee > 0) {
                $items[] = [
                    'account_id' => $cancellationIncomeAccount->id,
                    'debit' => 0.00,
                    'credit' => $cancellationFee,
                    'narration' => "Cancellation forfeiture income from Booking #{$booking->booking_number}",
                ];
            }

            $jv = null;
            if (!empty($items)) {
                $jv = $this->accountingService->createJournalVoucher($header, $items);
            }

            // Record refund payment if any
            $refundPayment = null;
            if ($refundAmount > 0) {
                $paymentNumber = $this->generateNextPaymentNumber($marqueeId);
                $refundPayment = BookingPayment::create([
                    'payment_number' => $paymentNumber,
                    'booking_id' => $booking->id,
                    'account_id' => $disbursingAccount->id,
                    'amount' => $refundAmount,
                    'status' => 'posted',
                    'payment_date' => $cancelDate,
                    'payment_method' => $paymentMethod,
                    'payment_type' => 'refund',
                    'transaction_reference' => "CNCL-REF-{$booking->id}",
                    'journal_voucher_id' => $jv?->id,
                    'recorded_by' => $recordedBy,
                    'posted_by' => $recordedBy,
                    'posted_at' => Carbon::parse($cancelDate),
                    'notes' => "Cancellation refund: {$cancelReason}",
                ]);
            }

            // Customer Ledger Update
            $lastCustomerBalance = (float) CustomerLedger::where('customer_id', $booking->customer_id)
                ->orderBy('transaction_date', 'desc')
                ->orderBy('id', 'desc')
                ->value('running_balance') ?? 0.00;

            if ($refundAmount > 0) {
                $lastCustomerBalance += $refundAmount;
                CustomerLedger::create([
                    'marquee_id' => $marqueeId,
                    'branch_id' => $branchId,
                    'customer_id' => $booking->customer_id,
                    'booking_id' => $booking->id,
                    'booking_payment_id' => $refundPayment?->id,
                    'journal_voucher_id' => $jv?->id,
                    'transaction_date' => $cancelDate,
                    'transaction_type' => 'refund',
                    'reference_number' => "CNCL-REF-{$booking->id}",
                    'description' => "Cancellation refund of Rs. " . number_format($refundAmount, 2) . " via {$paymentMethod}",
                    'debit' => $refundAmount,
                    'credit' => 0.00,
                    'running_balance' => $lastCustomerBalance,
                    'created_by' => $recordedBy,
                ]);
            }

            if ($cancellationFee > 0) {
                $lastCustomerBalance += $cancellationFee;
                CustomerLedger::create([
                    'marquee_id' => $marqueeId,
                    'branch_id' => $branchId,
                    'customer_id' => $booking->customer_id,
                    'booking_id' => $booking->id,
                    'journal_voucher_id' => $jv?->id,
                    'transaction_date' => $cancelDate,
                    'transaction_type' => 'cancellation_charge',
                    'reference_number' => "CNCL-FEE-{$booking->id}",
                    'description' => "Cancellation penalty charged for Booking #{$booking->booking_number}",
                    'debit' => $cancellationFee,
                    'credit' => 0.00,
                    'running_balance' => $lastCustomerBalance,
                    'created_by' => $recordedBy,
                ]);
            }

            $oldStatus = $booking->booking_status;
            $booking->update([
                'booking_status' => 'Cancelled',
                'financial_status' => 'Cancelled',
                'advance_received' => 0.00,
                'receivable_amount' => 0.00,
                'payment_status' => $refundAmount > 0 ? 'Refunded' : $booking->payment_status,
            ]);

            BookingHistory::create([
                'booking_id' => $booking->id,
                'user_id' => $recordedBy,
                'status_from' => $oldStatus,
                'status_to' => 'Cancelled',
                'notes' => "Booking cancelled. Refund: Rs. " . number_format($refundAmount, 2) . ", Fee: Rs. " . number_format($cancellationFee, 2) . ". Reason: {$cancelReason}",
            ]);

            return [
                'booking' => $booking,
                'journal_voucher' => $jv,
                'refund_payment' => $refundPayment,
            ];
        });
    }

    /**
     * Recalculate and synchronize booking financial totals.
     * Only POSTED payments are credited toward advance_received and total_paid.
     */
    public function recalculateBookingFinancials(Booking $booking): void
    {
        $effectiveTotal = $booking->effective_invoice_amount;

        $totalAdvance = (float) $booking->payments()
            ->where('status', 'posted')
            ->where('payment_type', 'advance')
            ->sum('amount');

        $totalReceivablePayments = (float) $booking->payments()
            ->where('status', 'posted')
            ->where('payment_type', 'receivable_payment')
            ->sum('amount');

        $totalRefunds = (float) $booking->payments()
            ->where('status', 'posted')
            ->where('payment_type', 'refund')
            ->sum('amount');

        $netAdvanceReceived = max(0.00, $totalAdvance - $totalRefunds);

        if (!$booking->is_revenue_recognized) {
            $advanceReceived = $netAdvanceReceived;
            $revenueRecognized = 0.00;
            $receivableAmount = 0.00;

            if ($advanceReceived <= 0) {
                $paymentStatus = 'Unpaid';
                $financialStatus = 'Pending';
            } elseif ($advanceReceived >= $effectiveTotal) {
                $paymentStatus = 'Paid';
                $financialStatus = 'Fully Paid';
            } else {
                $paymentStatus = 'Partially Paid';
                $financialStatus = 'Partially Paid';
            }
        } else {
            // Revenue is recognized
            $advanceReceived = 0.00; // Transferred to revenue
            $revenueRecognized = $effectiveTotal - (float) $booking->security_deposit;
            
            $totalCollected = (float) $booking->total_paid;
            $receivableAmount = max(0.00, $effectiveTotal - $totalCollected);

            if ($receivableAmount <= 0.01) {
                $paymentStatus = 'Paid';
                $financialStatus = 'Settled';
            } else {
                $paymentStatus = $totalCollected > 0 ? 'Partially Paid' : 'Unpaid';
                $financialStatus = 'Partially Paid';
            }
        }

        $booking->updateQuietly([
            'advance_received' => $advanceReceived,
            'revenue_recognized' => $revenueRecognized,
            'receivable_amount' => $receivableAmount,
            'payment_status' => $paymentStatus,
            'financial_status' => $financialStatus,
        ]);
    }
}
