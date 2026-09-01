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
     * Record a customer payment transaction (Advance or Receivable Settlement) with double-entry journal posting.
     */
    public function recordPayment(Booking $booking, array $params): BookingPayment
    {
        return DB::transaction(function () use ($booking, $params) {
            // Lock booking row
            $booking = Booking::where('id', $booking->id)->lockForUpdate()->firstOrFail();

            $amount = (float) $params['amount'];
            if ($amount <= 0) {
                throw new InvalidArgumentException("Payment amount must be greater than zero.");
            }

            $paymentDate = $params['payment_date'] ?? date('Y-m-d');
            $paymentMethod = $params['payment_method'] ?? 'Cash';
            $transactionReference = $params['transaction_reference'] ?? null;
            $notes = $params['notes'] ?? null;
            $recordedBy = $params['recorded_by'] ?? auth()->id();
            $marqueeId = $booking->marquee_id ?? auth()->user()->getActiveMarqueeId();
            $branchId = $booking->branch_id;

            // 1. Resolve Receiving Cash/Bank Account
            $receivingAccountId = $params['account_id'] ?? null;
            if (!$receivingAccountId) {
                // Auto-resolve cash or bank account
                $targetCode = (strtolower($paymentMethod) === 'cash') ? '1001' : '1002';
                $defaultAcc = $this->resolveCoaAccount(
                    $marqueeId,
                    $targetCode,
                    strtolower($paymentMethod) === 'cash' ? 'Cash' : 'Bank',
                    'Asset',
                    'CURRENT_ASSETS'
                );
                $receivingAccountId = $defaultAcc->id;
            }

            $receivingAccount = Account::findOrFail($receivingAccountId);

            // 2. Determine Accounting Nature & Balancing Account
            // If revenue has not yet been recognized, payment is CUSTOMER ADVANCE LIABILITY
            // If revenue is already recognized, payment is ACCOUNTS RECEIVABLE SETTLEMENT
            if (!$booking->is_revenue_recognized) {
                $paymentType = $params['payment_type'] ?? 'advance';
                $balancingAccount = $this->resolveCoaAccount(
                    $marqueeId,
                    '2003',
                    'Customer Advances / Contract Liabilities',
                    'Liability',
                    'CURRENT_LIABILITIES'
                );
                $balancingNarration = "Customer Advance Liability for Booking #{$booking->booking_number}";
                $ledgerType = 'advance_payment';
                $ledgerDesc = "Advance payment received via {$paymentMethod} for Booking #{$booking->booking_number}";
            } else {
                $paymentType = 'receivable_payment';
                $balancingAccount = $this->resolveCoaAccount(
                    $marqueeId,
                    '1003',
                    'Accounts Receivable',
                    'Asset',
                    'CURRENT_ASSETS'
                );
                $balancingNarration = "Receivable settlement for Booking #{$booking->booking_number}";
                $ledgerType = 'receivable_payment';
                $ledgerDesc = "Receivable payment received via {$paymentMethod} for Booking #{$booking->booking_number}";
            }

            // 3. Generate Double-Entry Accounting Journal Voucher
            $header = [
                'marquee_id' => $marqueeId,
                'branch_id' => $branchId,
                'voucher_date' => $paymentDate,
                'reference' => $transactionReference ?: "PAY-BK-{$booking->id}-" . time(),
                'notes' => "Payment of Rs. " . number_format($amount, 2) . " received for Booking #{$booking->booking_number} ({$paymentType})",
                'status' => 'posted',
            ];

            $items = [
                [
                    'account_id' => $receivingAccount->id,
                    'debit' => $amount,
                    'credit' => 0.00,
                    'narration' => "Funds received via {$paymentMethod} into {$receivingAccount->name}",
                ],
                [
                    'account_id' => $balancingAccount->id,
                    'debit' => 0.00,
                    'credit' => $amount,
                    'narration' => $balancingNarration,
                ],
            ];

            $jv = $this->accountingService->createJournalVoucher($header, $items);

            // 4. Create BookingPayment record
            $payment = BookingPayment::create([
                'booking_id' => $booking->id,
                'vendor_sale_id' => $params['vendor_sale_id'] ?? null,
                'account_id' => $receivingAccount->id,
                'amount' => $amount,
                'payment_date' => $paymentDate,
                'payment_method' => $paymentMethod,
                'payment_type' => $paymentType,
                'transaction_reference' => $transactionReference,
                'journal_voucher_id' => $jv->id,
                'recorded_by' => $recordedBy,
                'notes' => $notes,
            ]);

            // 5. Update Customer Ledger Sub-ledger
            $lastCustomerBalance = (float) CustomerLedger::where('customer_id', $booking->customer_id)
                ->orderBy('transaction_date', 'desc')
                ->orderBy('id', 'desc')
                ->value('running_balance') ?? 0.00;

            // Payment credited to customer reduces their receivable balance / increases advance
            $newCustomerBalance = $lastCustomerBalance - $amount;

            CustomerLedger::create([
                'marquee_id' => $marqueeId,
                'branch_id' => $branchId,
                'customer_id' => $booking->customer_id,
                'booking_id' => $booking->id,
                'booking_payment_id' => $payment->id,
                'journal_voucher_id' => $jv->id,
                'transaction_date' => $paymentDate,
                'transaction_type' => $ledgerType,
                'reference_number' => $payment->transaction_reference ?: $jv->voucher_no,
                'description' => $notes ? "{$ledgerDesc} - {$notes}" : $ledgerDesc,
                'debit' => 0.00,
                'credit' => $amount,
                'running_balance' => $newCustomerBalance,
                'created_by' => $recordedBy,
            ]);

            // 6. Recalculate Booking Financial Metrics
            $this->recalculateBookingFinancials($booking);

            // 7. Add Booking History Audit Log
            BookingHistory::create([
                'booking_id' => $booking->id,
                'user_id' => $recordedBy,
                'status_from' => $booking->booking_status,
                'status_to' => $booking->booking_status,
                'payment_status_from' => $booking->getOriginal('payment_status'),
                'payment_status_to' => $booking->payment_status,
                'notes' => "Recorded {$paymentType} payment of Rs. " . number_format($amount, 2) . " via {$paymentMethod}. Voucher: {$jv->voucher_no}.",
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

            // Resolve Disbursing Cash/Bank Account
            $disbursingAccountId = $params['account_id'] ?? null;
            if (!$disbursingAccountId) {
                $targetCode = (strtolower($paymentMethod) === 'cash') ? '1001' : '1002';
                $defaultAcc = $this->resolveCoaAccount($marqueeId, $targetCode, strtolower($paymentMethod) === 'cash' ? 'Cash' : 'Bank', 'Asset', 'CURRENT_ASSETS');
                $disbursingAccountId = $defaultAcc->id;
            }
            $disbursingAccount = Account::findOrFail($disbursingAccountId);

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
                'booking_id' => $booking->id,
                'account_id' => $disbursingAccount->id,
                'amount' => $amount,
                'payment_date' => $refundDate,
                'payment_method' => $paymentMethod,
                'payment_type' => 'refund',
                'transaction_reference' => $transactionReference,
                'journal_voucher_id' => $jv->id,
                'recorded_by' => $recordedBy,
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
            $disbursingAccount = Account::findOrFail($disbursingAccountId);

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
                $refundPayment = BookingPayment::create([
                    'booking_id' => $booking->id,
                    'account_id' => $disbursingAccount->id,
                    'amount' => $refundAmount,
                    'payment_date' => $cancelDate,
                    'payment_method' => $paymentMethod,
                    'payment_type' => 'refund',
                    'transaction_reference' => "CNCL-REF-{$booking->id}",
                    'journal_voucher_id' => $jv?->id,
                    'recorded_by' => $recordedBy,
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
     */
    public function recalculateBookingFinancials(Booking $booking): void
    {
        $effectiveTotal = $booking->effective_invoice_amount;

        $totalAdvance = (float) $booking->payments()
            ->where('payment_type', 'advance')
            ->sum('amount');

        $totalReceivablePayments = (float) $booking->payments()
            ->where('payment_type', 'receivable_payment')
            ->sum('amount');

        $totalRefunds = (float) $booking->payments()
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
