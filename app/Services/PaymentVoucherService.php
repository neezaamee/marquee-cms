<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Branch;
use App\Models\CashBankAccount;
use App\Models\Expense;
use App\Models\FinancialYear;
use App\Models\PaymentVoucher;
use App\Models\Supplier;
use App\Models\Vendor;
use App\Services\AccountingService;
use App\Services\InventoryService;
use App\Services\VendorCommissionService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentVoucherService
{
    protected AccountingService $accountingService;
    protected InventoryService $inventoryService;

    public function __construct(AccountingService $accountingService, InventoryService $inventoryService)
    {
        $this->accountingService = $accountingService;
        $this->inventoryService = $inventoryService;
    }

    /**
     * Generate sequential voucher number (e.g. CPV-2026-HO-0001 or BPV-2026-HO-0001).
     */
    public function generateVoucherNo(string $type, int $marqueeId, ?int $financialYearId = null, ?int $branchId = null): string
    {
        $prefix = strtoupper($type) === 'BPV' ? 'BPV' : 'CPV';

        $yearSuffix = date('Y');
        if ($financialYearId) {
            $fy = FinancialYear::find($financialYearId);
            if ($fy && $fy->start_date) {
                $yearSuffix = date('Y', strtotime($fy->start_date));
            }
        }

        if ($branchId) {
            $branch = Branch::find($branchId);
            $branchCode = $branch ? strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $branch->name), 0, 3)) : 'BR';
        } else {
            $branchCode = 'HO';
        }

        $query = PaymentVoucher::where('marquee_id', $marqueeId)
            ->where('voucher_type', $prefix);

        if ($financialYearId) {
            $query->where('financial_year_id', $financialYearId);
        }

        $latest = $query->orderBy('id', 'desc')->first();
        $nextSeq = 1;

        if ($latest && $latest->voucher_no) {
            $parts = explode('-', $latest->voucher_no);
            $lastPart = end($parts);
            if (is_numeric($lastPart)) {
                $nextSeq = (int) $lastPart + 1;
            }
        }

        $sequenceStr = str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
        return "{$prefix}-{$yearSuffix}-{$branchCode}-{$sequenceStr}";
    }

    /**
     * Convert currency amount to words (Rupees in Words).
     */
    public function numberToWords(float $amount): string
    {
        $amount = round($amount, 2);
        if ($amount == 0) {
            return 'Zero Rupees Only';
        }

        $rupees = floor($amount);
        $paisas = round(($amount - $rupees) * 100);

        $words = $this->convertIntegerToWords((int) $rupees);

        if ($paisas > 0) {
            $words .= ' and ' . $this->convertIntegerToWords((int) $paisas) . ' Paisas';
        }

        return 'Rupees ' . trim($words) . ' Only';
    }

    protected function convertIntegerToWords(int $num): string
    {
        if ($num == 0) {
            return 'Zero';
        }

        $ones = [
            0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
            6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
            11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen',
            15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen',
            19 => 'Nineteen'
        ];

        $tens = [
            2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty',
            6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety'
        ];

        if ($num < 20) {
            return $ones[$num];
        }

        if ($num < 100) {
            return $tens[intval($num / 10)] . ($num % 10 !== 0 ? '-' . $ones[$num % 10] : '');
        }

        if ($num < 1000) {
            return $ones[intval($num / 100)] . ' Hundred' . ($num % 100 !== 0 ? ' and ' . $this->convertIntegerToWords($num % 100) : '');
        }

        // South Asian / Pakistani numbering standard: Thousand, Lakh, Crore
        if ($num < 100000) {
            return $this->convertIntegerToWords(intval($num / 1000)) . ' Thousand' . ($num % 1000 !== 0 ? ' ' . $this->convertIntegerToWords($num % 1000) : '');
        }

        if ($num < 10000000) {
            return $this->convertIntegerToWords(intval($num / 100000)) . ' Lakh' . ($num % 100000 !== 0 ? ' ' . $this->convertIntegerToWords($num % 100000) : '');
        }

        return $this->convertIntegerToWords(intval($num / 10000000)) . ' Crore' . ($num % 10000000 !== 0 ? ' ' . $this->convertIntegerToWords($num % 10000000) : '');
    }

    /**
     * Create a new payment voucher.
     */
    public function createPaymentVoucher(array $data, int $userId): PaymentVoucher
    {
        return DB::transaction(function () use ($data, $userId) {
            $marqueeId = $data['marquee_id'];
            $type = strtoupper($data['voucher_type'] ?? 'CPV');

            $fy = $this->accountingService->getActiveFinancialYear($marqueeId);
            $financialYearId = $data['financial_year_id'] ?? ($fy ? $fy->id : null);
            $branchId = $data['branch_id'] ?? null;

            $voucherNo = $data['voucher_no'] ?? $this->generateVoucherNo($type, $marqueeId, $financialYearId, $branchId);
            $amount = (float) ($data['amount'] ?? 0);
            $amountInWords = $data['amount_in_words'] ?? $this->numberToWords($amount);

            // Determine specific payee relations
            $payeeType = $data['payee_type'] ?? 'general';
            $payeeId = $data['payee_id'] ?? null;
            $supplierId = null;
            $vendorId = null;
            $expenseId = null;

            if ($payeeType === 'supplier' && $payeeId) {
                $supplierId = Supplier::where('id', $payeeId)->where('marquee_id', $marqueeId)->value('id');
            } elseif ($payeeType === 'vendor' && $payeeId) {
                $vendorId = Vendor::withoutGlobalScope('tenant')->where('id', $payeeId)->where('marquee_id', $marqueeId)->value('id');
            } elseif ($payeeType === 'expense' && $payeeId) {
                $expenseId = Expense::where('id', $payeeId)->where('marquee_id', $marqueeId)->value('id');
            }

            $voucher = PaymentVoucher::create([
                'marquee_id' => $marqueeId,
                'branch_id' => $branchId,
                'financial_year_id' => $financialYearId,
                'voucher_no' => $voucherNo,
                'voucher_type' => $type,
                'voucher_date' => $data['voucher_date'] ?? date('Y-m-d'),
                'payee_type' => $payeeType,
                'payee_id' => $payeeId,
                'payee_name' => $data['payee_name'] ?? '—',
                'payee_cnic' => $data['payee_cnic'] ?? null,
                'payee_phone' => $data['payee_phone'] ?? null,
                'cash_bank_account_id' => $data['cash_bank_account_id'],
                'debit_account_id' => $data['debit_account_id'],
                'amount' => $amount,
                'amount_in_words' => $amountInWords,
                'payment_method' => $data['payment_method'] ?? ($type === 'CPV' ? 'Cash' : 'Bank Transfer'),
                'cheque_no' => $data['cheque_no'] ?? null,
                'cheque_date' => $data['cheque_date'] ?? null,
                'reference_no' => $data['reference_no'] ?? null,
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? PaymentVoucher::STATUS_APPROVED,
                'supplier_id' => $supplierId,
                'vendor_id' => $vendorId,
                'expense_id' => $expenseId,
                'prepared_by' => $userId,
                'approved_by' => in_array($data['status'] ?? '', [PaymentVoucher::STATUS_APPROVED, PaymentVoucher::STATUS_POSTED]) ? $userId : null,
                'created_by' => $userId,
            ]);

            return $voucher;
        });
    }

    /**
     * Update an existing unposted payment voucher.
     */
    public function updatePaymentVoucher(PaymentVoucher $voucher, array $data, int $userId): PaymentVoucher
    {
        if ($voucher->status === PaymentVoucher::STATUS_POSTED) {
            throw new InvalidArgumentException("Cannot modify a posted payment voucher.");
        }

        $amount = isset($data['amount']) ? (float)$data['amount'] : (float)$voucher->amount;
        $amountInWords = $data['amount_in_words'] ?? $this->numberToWords($amount);

        $payeeType = $data['payee_type'] ?? $voucher->payee_type;
        $payeeId = $data['payee_id'] ?? $voucher->payee_id;

        $voucher->update([
            'branch_id' => $data['branch_id'] ?? $voucher->branch_id,
            'voucher_date' => $data['voucher_date'] ?? $voucher->voucher_date,
            'payee_type' => $payeeType,
            'payee_id' => $payeeId,
            'payee_name' => $data['payee_name'] ?? $voucher->payee_name,
            'payee_cnic' => $data['payee_cnic'] ?? $voucher->payee_cnic,
            'payee_phone' => $data['payee_phone'] ?? $voucher->payee_phone,
            'cash_bank_account_id' => $data['cash_bank_account_id'] ?? $voucher->cash_bank_account_id,
            'debit_account_id' => $data['debit_account_id'] ?? $voucher->debit_account_id,
            'amount' => $amount,
            'amount_in_words' => $amountInWords,
            'payment_method' => $data['payment_method'] ?? $voucher->payment_method,
            'cheque_no' => $data['cheque_no'] ?? $voucher->cheque_no,
            'cheque_date' => $data['cheque_date'] ?? $voucher->cheque_date,
            'reference_no' => $data['reference_no'] ?? $voucher->reference_no,
            'description' => $data['description'] ?? $voucher->description,
            'supplier_id' => $payeeType === 'supplier' ? $payeeId : null,
            'vendor_id' => $payeeType === 'vendor' ? $payeeId : null,
            'expense_id' => $payeeType === 'expense' ? $payeeId : null,
            'updated_by' => $userId,
        ]);

        return $voucher;
    }

    /**
     * Approve voucher for disbursement and printing.
     */
    public function approvePaymentVoucher(PaymentVoucher $voucher, int $userId): PaymentVoucher
    {
        if ($voucher->status === PaymentVoucher::STATUS_POSTED) {
            throw new InvalidArgumentException("Voucher is already posted.");
        }

        $voucher->update([
            'status' => PaymentVoucher::STATUS_APPROVED,
            'approved_by' => $userId,
            'updated_by' => $userId,
        ]);

        return $voucher;
    }

    /**
     * Disburse funds and post to General Ledger & Subledgers (Stage 2).
     */
    public function disburseAndPost(PaymentVoucher $voucher, int $disbursedById, array $disbursementData = []): PaymentVoucher
    {
        if ($voucher->status === PaymentVoucher::STATUS_POSTED) {
            throw new InvalidArgumentException("This voucher has already been disbursed and posted.");
        }

        return DB::transaction(function () use ($voucher, $disbursedById, $disbursementData) {
            $disbursedDate = $disbursementData['disbursed_date'] ?? date('Y-m-d');
            $marqueeId = $voucher->marquee_id;

            // 1. Determine Credit Account from CashBankAccount
            $cashBankAcc = CashBankAccount::findOrFail($voucher->cash_bank_account_id);
            $creditAccountId = $cashBankAcc->account_id;

            if (!$creditAccountId) {
                throw new InvalidArgumentException("Source Cash/Bank account is missing a mapped Chart of Accounts head.");
            }

            // 2. Determine Debit Account
            $debitAccountId = $voucher->debit_account_id;
            if (!$debitAccountId) {
                throw new InvalidArgumentException("Debit account is not specified on this voucher.");
            }

            // 3. Post General Ledger double-entry Journal Voucher
            $header = [
                'marquee_id' => $marqueeId,
                'branch_id' => $voucher->branch_id,
                'voucher_date' => $disbursedDate,
                'reference' => $voucher->voucher_no,
                'notes' => "Auto-posted from {$voucher->voucher_type_full} #{$voucher->voucher_no}. Payee: {$voucher->payee_name}. " . ($voucher->description ?? ''),
                'status' => 'posted',
            ];

            $items = [
                [
                    'account_id' => $debitAccountId,
                    'debit' => $voucher->amount,
                    'credit' => 0.00,
                    'narration' => "Debit - {$voucher->voucher_type} #{$voucher->voucher_no} to {$voucher->payee_name}",
                ],
                [
                    'account_id' => $creditAccountId,
                    'debit' => 0.00,
                    'credit' => $voucher->amount,
                    'narration' => "Credit - Paid from {$cashBankAcc->account->name}",
                ]
            ];

            $jv = $this->accountingService->createJournalVoucher($header, $items);

            // 4. Update Subledgers based on payee type
            // A. Supplier Ledger
            if ($voucher->payee_type === PaymentVoucher::PAYEE_SUPPLIER && $voucher->supplier_id) {
                $this->inventoryService->recordSupplierTransaction(
                    $marqueeId,
                    $voucher->supplier_id,
                    $disbursedDate,
                    $voucher->amount, // Debit decreases payable
                    0.00,
                    'VendorPayment',
                    $cashBankAcc->id,
                    $voucher->voucher_no,
                    "Payment via {$voucher->voucher_type} #{$voucher->voucher_no}. " . ($voucher->description ?? '')
                );
            }

            // B. Vendor / Service Provider
            if ($voucher->payee_type === PaymentVoucher::PAYEE_VENDOR && $voucher->vendor_id) {
                $vendor = Vendor::withoutGlobalScope('tenant')->find($voucher->vendor_id);
                if ($vendor) {
                    $vendorCommissionService = app(VendorCommissionService::class);
                    $vendorCommissionService->processSettlement($vendor, (float)$voucher->amount, [
                        'settlement_date' => $disbursedDate,
                        'payment_method' => $voucher->payment_method,
                        'reference_number' => $voucher->voucher_no,
                        'account_id' => $cashBankAcc->id,
                        'remarks' => "Settlement via {$voucher->voucher_type} #{$voucher->voucher_no}. " . ($voucher->description ?? ''),
                    ]);
                }
            }

            // C. Expense Module
            if ($voucher->payee_type === PaymentVoucher::PAYEE_EXPENSE && $voucher->expense_id) {
                $expense = Expense::find($voucher->expense_id);
                if ($expense) {
                    $expense->update([
                        'payment_status' => 'Paid',
                        'status' => Expense::STATUS_PAID,
                        'journal_voucher_id' => $jv->id,
                    ]);
                }
            }

            // 5. Update Payment Voucher status
            $voucher->update([
                'status' => PaymentVoucher::STATUS_POSTED,
                'journal_voucher_id' => $jv->id,
                'disbursed_by' => $disbursedById,
                'disbursed_at' => now(),
                'receiver_signature_notes' => $disbursementData['notes'] ?? $voucher->receiver_signature_notes,
                'updated_by' => $disbursedById,
            ]);

            return $voucher;
        });
    }

    /**
     * Cancel an unposted payment voucher.
     */
    public function cancelPaymentVoucher(PaymentVoucher $voucher, int $userId, string $reason = ''): PaymentVoucher
    {
        if ($voucher->status === PaymentVoucher::STATUS_POSTED) {
            throw new InvalidArgumentException("Posted vouchers cannot be cancelled. Use an accounting adjustment or reversal instead.");
        }

        $voucher->update([
            'status' => PaymentVoucher::STATUS_CANCELLED,
            'description' => trim($voucher->description . "\n[Cancelled on " . date('Y-m-d') . ": {$reason}]"),
            'updated_by' => $userId,
        ]);

        return $voucher;
    }
}
