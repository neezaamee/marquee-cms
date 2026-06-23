<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountOpeningBalance;
use App\Models\FinancialYear;
use App\Models\JournalVoucher;
use App\Models\JournalVoucherItem;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AccountingService
{
    /**
     * Get the active financial year for a tenant.
     */
    public function getActiveFinancialYear(int $marqueeId): ?FinancialYear
    {
        return FinancialYear::where('marquee_id', $marqueeId)
            ->where('status', 'active')
            ->where('is_default', true)
            ->first() ?? FinancialYear::where('marquee_id', $marqueeId)
            ->where('status', 'active')
            ->orderBy('start_date', 'desc')
            ->first();
    }

    /**
     * Check if posting is allowed for a given date.
     */
    public function isDateInActiveFinancialYear(string $date, int $marqueeId): bool
    {
        $parsedDate = date('Y-m-d', strtotime($date));
        $fy = FinancialYear::where('marquee_id', $marqueeId)
            ->where('status', 'active')
            ->where('start_date', '<=', $parsedDate)
            ->where('end_date', '>=', $parsedDate)
            ->first();
            
        return $fy !== null;
    }

    /**
     * Generate the next journal voucher number.
     */
    public function generateNextVoucherNo(int $marqueeId, int $financialYearId, ?int $branchId = null): string
    {
        $fy = FinancialYear::findOrFail($financialYearId);
        $yearSuffix = date('Y', strtotime($fy->start_date));

        $query = JournalVoucher::where('marquee_id', $marqueeId)
            ->where('financial_year_id', $financialYearId);

        if ($branchId) {
            $query->where('branch_id', $branchId);
            $branchCode = DB::table('branches')->where('id', $branchId)->value('name');
            $branchSuffix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $branchCode ?? ''), 0, 3));
        } else {
            $branchSuffix = 'HO'; // Head Office / Central
        }

        $latestVoucher = $query->orderBy('id', 'desc')->first();
        $nextSequence = 1;

        if ($latestVoucher) {
            $parts = explode('-', $latestVoucher->voucher_no);
            $lastSeq = end($parts);
            if (is_numeric($lastSeq)) {
                $nextSequence = (int)$lastSeq + 1;
            }
        }

        $sequenceStr = str_pad((string)$nextSequence, 4, '0', STR_PAD_LEFT);

        return "JV-{$yearSuffix}-{$branchSuffix}-{$sequenceStr}";
    }

    /**
     * Create a journal voucher along with its details.
     */
    public function createJournalVoucher(array $header, array $items): JournalVoucher
    {
        $this->validateJournalItems($items);

        $marqueeId = $header['marquee_id'];
        $voucherDate = $header['voucher_date'];

        // Enforce Financial Year check
        if (!$this->isDateInActiveFinancialYear($voucherDate, $marqueeId)) {
            throw new InvalidArgumentException("Voucher date does not fall within any active financial year.");
        }

        // Get matching financial year
        $fy = FinancialYear::where('marquee_id', $marqueeId)
            ->where('status', 'active')
            ->where('start_date', '<=', $voucherDate)
            ->where('end_date', '>=', $voucherDate)
            ->first();

        $header['financial_year_id'] = $fy->id;

        // Auto-generate voucher number if not provided
        if (empty($header['voucher_no'])) {
            $header['voucher_no'] = $this->generateNextVoucherNo($marqueeId, $fy->id, $header['branch_id'] ?? null);
        }

        return DB::transaction(function () use ($header, $items) {
            $voucher = JournalVoucher::create($header);

            foreach ($items as $item) {
                $voucher->items()->create([
                    'account_id' => $item['account_id'],
                    'debit' => $item['debit'] ?? 0,
                    'credit' => $item['credit'] ?? 0,
                    'narration' => $item['narration'] ?? null,
                ]);
            }

            return $voucher;
        });
    }

    /**
     * Update a journal voucher along with its details.
     */
    public function updateJournalVoucher(int $id, array $header, array $items): JournalVoucher
    {
        $this->validateJournalItems($items);

        $voucher = JournalVoucher::findOrFail($id);

        if ($voucher->status === 'posted') {
            throw new InvalidArgumentException("Cannot modify a posted journal voucher.");
        }

        $marqueeId = $header['marquee_id'] ?? $voucher->marquee_id;
        $voucherDate = $header['voucher_date'] ?? $voucher->voucher_date;

        if (!$this->isDateInActiveFinancialYear($voucherDate, $marqueeId)) {
            throw new InvalidArgumentException("Voucher date does not fall within any active financial year.");
        }

        return DB::transaction(function () use ($voucher, $header, $items) {
            $voucher->update($header);
            
            // Delete old items and recreate
            $voucher->items()->delete();

            foreach ($items as $item) {
                $voucher->items()->create([
                    'account_id' => $item['account_id'],
                    'debit' => $item['debit'] ?? 0,
                    'credit' => $item['credit'] ?? 0,
                    'narration' => $item['narration'] ?? null,
                ]);
            }

            return $voucher;
        });
    }

    /**
     * Validate voucher line items.
     */
    protected function validateJournalItems(array $items): void
    {
        if (count($items) < 2) {
            throw new InvalidArgumentException("A journal voucher must have at least two line items.");
        }

        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($items as $item) {
            $debit = (float)($item['debit'] ?? 0);
            $credit = (float)($item['credit'] ?? 0);

            if ($debit < 0 || $credit < 0) {
                throw new InvalidArgumentException("Debit and Credit values cannot be negative.");
            }

            if ($debit > 0 && $credit > 0) {
                throw new InvalidArgumentException("A single item cannot have both debit and credit values.");
            }

            if ($debit == 0 && $credit == 0) {
                throw new InvalidArgumentException("Each line item must have either a debit or credit value.");
            }

            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        if (abs($totalDebit - $totalCredit) > 0.001) {
            throw new InvalidArgumentException("Total Debits must equal Total Credits. Unbalanced entries are not allowed.");
        }
    }

    /**
     * Fetch General Ledger for an account.
     */
    public function getGeneralLedger(
        int $accountId,
        string $startDate,
        string $endDate,
        ?int $branchId = null,
        ?int $financialYearId = null
    ): array {
        $account = Account::findOrFail($accountId);
        $nature = $account->nature;

        // If financial year is not provided, locate it based on start date
        if (!$financialYearId) {
            $fy = FinancialYear::where('marquee_id', $account->marquee_id)
                ->where('start_date', '<=', $startDate)
                ->where('end_date', '>=', $startDate)
                ->first();
            $financialYearId = $fy?->id;
        } else {
            $fy = FinancialYear::find($financialYearId);
        }

        if (!$fy) {
            throw new InvalidArgumentException("Financial year not found for the given dates.");
        }

        // 1. Calculate Opening Balance for the period
        $openingBalanceQuery = AccountOpeningBalance::where('account_id', $accountId)
            ->where('financial_year_id', $financialYearId);

        if ($branchId) {
            $openingBalanceQuery->where('branch_id', $branchId);
        } else {
            $openingBalanceQuery->whereNull('branch_id');
        }

        $openingBalRecord = $openingBalanceQuery->first();
        $openingDebit = (float)($openingBalRecord?->debit ?? 0);
        $openingCredit = (float)($openingBalRecord?->credit ?? 0);

        // Sum transactions from the beginning of the financial year up to ($startDate - 1 day)
        $fyStart = $fy->start_date instanceof \DateTimeInterface 
            ? $fy->start_date->format('Y-m-d') 
            : date('Y-m-d', strtotime($fy->start_date));
        $priorStartDate = date('Y-m-d', strtotime($startDate));

        $priorDebit = 0;
        $priorCredit = 0;

        if ($priorStartDate > $fyStart) {
            $priorTransactions = DB::table('journal_voucher_items')
                ->join('journal_vouchers', 'journal_voucher_items.journal_voucher_id', '=', 'journal_vouchers.id')
                ->whereNull('journal_vouchers.deleted_at')
                ->where('journal_vouchers.status', 'posted')
                ->where('journal_voucher_items.account_id', $accountId)
                ->where('journal_vouchers.voucher_date', '>=', $fyStart)
                ->where('journal_vouchers.voucher_date', '<', $priorStartDate);

            if ($branchId) {
                $priorTransactions->where('journal_vouchers.branch_id', $branchId);
            }

            $priorDebit = (float)$priorTransactions->sum('journal_voucher_items.debit');
            $priorCredit = (float)$priorTransactions->sum('journal_voucher_items.credit');
        }

        $totalOpeningDebit = $openingDebit + $priorDebit;
        $totalOpeningCredit = $openingCredit + $priorCredit;

        // Balance depends on account nature
        if (in_array(strtolower($nature), ['asset', 'expense'])) {
            $openingBalanceValue = $totalOpeningDebit - $totalOpeningCredit;
        } else {
            // Liability, Equity, Income
            $openingBalanceValue = $totalOpeningCredit - $totalOpeningDebit;
        }

        // 2. Fetch Period Transactions
        $itemsQuery = JournalVoucherItem::join('journal_vouchers', 'journal_voucher_items.journal_voucher_id', '=', 'journal_vouchers.id')
            ->whereNull('journal_vouchers.deleted_at')
            ->where('journal_vouchers.status', 'posted')
            ->where('journal_voucher_items.account_id', $accountId)
            ->whereBetween('journal_vouchers.voucher_date', [$startDate, $endDate])
            ->select(
                'journal_voucher_items.*',
                'journal_vouchers.voucher_no',
                'journal_vouchers.voucher_date',
                'journal_vouchers.reference'
            )
            ->orderBy('journal_vouchers.voucher_date', 'asc')
            ->orderBy('journal_vouchers.voucher_no', 'asc')
            ->orderBy('journal_voucher_items.id', 'asc');

        if ($branchId) {
            $itemsQuery->where('journal_vouchers.branch_id', $branchId);
        }

        $items = $itemsQuery->get();

        // 3. Construct Ledger Entries with Running Balance
        $ledgerEntries = [];
        $runningBalance = $openingBalanceValue;

        foreach ($items as $item) {
            $debit = (float)$item->debit;
            $credit = (float)$item->credit;

            if (in_array(strtolower($nature), ['asset', 'expense'])) {
                $runningBalance += ($debit - $credit);
            } else {
                $runningBalance += ($credit - $debit);
            }

            $ledgerEntries[] = [
                'id' => $item->id,
                'voucher_no' => $item->voucher_no,
                'voucher_date' => $item->voucher_date instanceof \DateTimeInterface 
                    ? $item->voucher_date->format('Y-m-d') 
                    : date('Y-m-d', strtotime($item->voucher_date)),
                'reference' => $item->reference,
                'narration' => $item->narration,
                'debit' => $debit,
                'credit' => $credit,
                'running_balance' => $runningBalance,
            ];
        }

        $totalPeriodDebit = (float)$items->sum('debit');
        $totalPeriodCredit = (float)$items->sum('credit');

        return [
            'account' => $account,
            'opening_balance_debit' => $totalOpeningDebit,
            'opening_balance_credit' => $totalOpeningCredit,
            'opening_balance' => $openingBalanceValue,
            'entries' => $ledgerEntries,
            'total_debit' => $totalPeriodDebit,
            'total_credit' => $totalPeriodCredit,
            'closing_balance' => $runningBalance,
        ];
    }

    /**
     * Get Trial Balance report.
     */
    public function getTrialBalance(
        int $marqueeId,
        int $financialYearId,
        ?string $asOfDate = null,
        ?int $branchId = null
    ): array {
        $fy = FinancialYear::findOrFail($financialYearId);
        $startDate = $fy->start_date instanceof \DateTimeInterface 
            ? $fy->start_date->format('Y-m-d') 
            : date('Y-m-d', strtotime($fy->start_date));
        $endDate = $asOfDate ?: ($fy->end_date instanceof \DateTimeInterface 
            ? $fy->end_date->format('Y-m-d') 
            : date('Y-m-d', strtotime($fy->end_date)));

        // Fetch all active accounts for the tenant
        $accounts = Account::where('marquee_id', $marqueeId)
            ->where('is_active', true)
            ->with(['accountType'])
            ->get();

        $rows = [];
        $totalTrialDebit = 0;
        $totalTrialCredit = 0;

        foreach ($accounts as $account) {
            $gl = $this->getGeneralLedger($account->id, $startDate, $endDate, $branchId, $financialYearId);
            
            $netDebit = 0;
            $netCredit = 0;
            $netBalance = $gl['closing_balance'];
            $nature = strtolower($account->nature);

            if (in_array($nature, ['asset', 'expense'])) {
                if ($netBalance >= 0) {
                    $netDebit = $netBalance;
                } else {
                    $netCredit = abs($netBalance);
                }
            } else {
                if ($netBalance >= 0) {
                    $netCredit = $netBalance;
                } else {
                    $netDebit = abs($netBalance);
                }
            }

            if ($netDebit > 0 || $netCredit > 0) {
                $rows[] = [
                    'account_code' => $account->account_code,
                    'account_name' => $account->name,
                    'nature' => $account->nature,
                    'type_name' => $account->accountType->name,
                    'debit' => $netDebit,
                    'credit' => $netCredit,
                ];

                $totalTrialDebit += $netDebit;
                $totalTrialCredit += $netCredit;
            }
        }

        $isBalanced = abs($totalTrialDebit - $totalTrialCredit) < 0.01;

        return [
            'financial_year' => $fy,
            'as_of_date' => $endDate,
            'rows' => $rows,
            'total_debit' => $totalTrialDebit,
            'total_credit' => $totalTrialCredit,
            'is_balanced' => $isBalanced,
        ];
    }
}
