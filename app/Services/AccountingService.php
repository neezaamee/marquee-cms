<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountOpeningBalance;
use App\Models\FinancialYear;
use App\Models\JournalVoucher;
use App\Models\JournalVoucherItem;
use App\Models\SaasInvoice;
use App\Models\SaasPayment;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AccountingService
{
    /**
     * Get the active financial year for a tenant or platform.
     */
    public function getActiveFinancialYear(?int $marqueeId = null): ?FinancialYear
    {
        $query = FinancialYear::where('status', 'active');
        if ($marqueeId) {
            $query->where('marquee_id', $marqueeId);
        } else {
            $query->whereNull('marquee_id');
        }
        return $query->where('is_default', true)->first() 
            ?? $query->orderBy('start_date', 'desc')->first();
    }

    /**
     * Check if posting is allowed for a given date.
     */
    public function isDateInActiveFinancialYear(string $date, ?int $marqueeId = null): bool
    {
        $parsedDate = date('Y-m-d', strtotime($date));
        $query = FinancialYear::where('status', 'active')
            ->where('start_date', '<=', $parsedDate)
            ->where('end_date', '>=', $parsedDate);
            
        if ($marqueeId) {
            $query->where('marquee_id', $marqueeId);
        } else {
            $query->whereNull('marquee_id');
        }
            
        return $query->first() !== null;
    }

    /**
     * Generate the next journal voucher number.
     */
    public function generateNextVoucherNo(?int $marqueeId, int $financialYearId, ?int $branchId = null): string
    {
        $fy = FinancialYear::findOrFail($financialYearId);
        $yearSuffix = date('Y', strtotime($fy->start_date));

        $query = JournalVoucher::where('financial_year_id', $financialYearId);
        if ($marqueeId) {
            $query->where('marquee_id', $marqueeId);
        } else {
            $query->whereNull('marquee_id');
        }

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

        $marqueeId = $header['marquee_id'] ?? null;
        $voucherDate = $header['voucher_date'];

        // Enforce active Financial Year rule
        if (!$this->isDateInActiveFinancialYear($voucherDate, $marqueeId)) {
            $parsedDate = date('Y-m-d', strtotime($voucherDate));
            $closedYear = FinancialYear::withoutGlobalScope('tenant')
                ->where('start_date', '<=', $parsedDate)
                ->where('end_date', '>=', $parsedDate)
                ->where(function ($q) use ($marqueeId) {
                    if ($marqueeId) {
                        $q->where('marquee_id', $marqueeId);
                    } else {
                        $q->whereNull('marquee_id');
                    }
                })
                ->where('status', '!=', 'active')
                ->first();

            if ($closedYear) {
                throw new \InvalidArgumentException("Voucher date does not fall within any active financial year.");
            }

            // Auto-create only if no financial year existed for this date
            $year = date('Y', strtotime($voucherDate));
            FinancialYear::withoutGlobalScope('tenant')->create([
                'marquee_id' => $marqueeId,
                'name' => 'FY ' . $year,
                'start_date' => $year . '-01-01',
                'end_date' => $year . '-12-31',
                'status' => 'active',
                'is_default' => true,
            ]);
        }

        // Get matching financial year
        $fyQuery = FinancialYear::where('status', 'active')
            ->where('start_date', '<=', $voucherDate)
            ->where('end_date', '>=', $voucherDate);
            
        if ($marqueeId) {
            $fyQuery->where('marquee_id', $marqueeId);
        } else {
            $fyQuery->whereNull('marquee_id');
        }
        $fy = $fyQuery->first();

        if (!$fy) {
            $fy = FinancialYear::where('status', 'active')->first();
        }

        $header['financial_year_id'] = $fy?->id;

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

        // Get list of cancelled SaaS invoice numbers and associated payments
        $excludedReferences = [];
        try {
            $cancelledInvoiceNumbers = SaasInvoice::where('invoice_status', 'Cancelled')
                ->pluck('invoice_number')
                ->toArray();

            $cancelledPaymentReferences = SaasPayment::whereIn('invoice_id', function($q) {
                $q->select('id')->from('saas_invoices')->where('invoice_status', 'Cancelled');
            })->pluck('payment_reference')->toArray();

            $excludedReferences = array_merge($cancelledInvoiceNumbers, $cancelledPaymentReferences);
        } catch (\Exception $e) {
            // Fallback if tables don't exist in a migration/test context
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

            if (!empty($excludedReferences)) {
                $priorTransactions->where(function($q) use ($excludedReferences) {
                    $q->whereNotNull('journal_vouchers.marquee_id')
                      ->orWhereNotIn('journal_vouchers.reference', $excludedReferences);
                });
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

        if (!empty($excludedReferences)) {
            $itemsQuery->where(function($q) use ($excludedReferences) {
                $q->whereNotNull('journal_vouchers.marquee_id')
                  ->orWhereNotIn('journal_vouchers.reference', $excludedReferences);
            });
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
        ?int $marqueeId,
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

        // Fetch all active accounts for the tenant/platform
        $accountsQuery = Account::where('is_active', true)->with(['accountType']);
        if ($marqueeId) {
            $accountsQuery->where('marquee_id', $marqueeId);
        } else {
            $accountsQuery->whereNull('marquee_id');
        }
        $accounts = $accountsQuery->get();

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

    /**
     * Get Profit & Loss Report.
     */
    public function getProfitAndLoss(
        ?int $marqueeId,
        int $financialYearId,
        ?string $startDate = null,
        ?string $endDate = null,
        ?int $branchId = null
    ): array {
        $fy = FinancialYear::findOrFail($financialYearId);
        $defaultStart = $fy->start_date instanceof \DateTimeInterface 
            ? $fy->start_date->format('Y-m-d') 
            : date('Y-m-d', strtotime($fy->start_date));
        $defaultEnd = $fy->end_date instanceof \DateTimeInterface 
            ? $fy->end_date->format('Y-m-d') 
            : date('Y-m-d', strtotime($fy->end_date));

        $startDate = $startDate ?: $defaultStart;
        $endDate = $endDate ?: $defaultEnd;

        // Fetch all active accounts of Income and Expense natures
        $accountsQuery = Account::where('is_active', true)
            ->whereIn('nature', ['Income', 'Expense'])
            ->with(['accountType']);
            
        if ($marqueeId) {
            $accountsQuery->where('marquee_id', $marqueeId);
        } else {
            $accountsQuery->whereNull('marquee_id');
        }
        $accounts = $accountsQuery->get();

        $incomeRows = [];
        $expenseRows = [];
        $totalIncome = 0.0;
        $totalExpense = 0.0;

        foreach ($accounts as $account) {
            $gl = $this->getGeneralLedger($account->id, $startDate, $endDate, $branchId, $financialYearId);
            $balance = (float)$gl['closing_balance'];

            if ($balance == 0) {
                continue;
            }

            $row = [
                'account_code' => $account->account_code,
                'account_name' => $account->name,
                'type_name' => $account->accountType->name,
                'balance' => $balance,
            ];

            if ($account->nature === 'Income') {
                $incomeRows[] = $row;
                $totalIncome += $balance;
            } else {
                $expenseRows[] = $row;
                $totalExpense += $balance;
            }
        }

        $netProfitOrLoss = $totalIncome - $totalExpense;

        return [
            'financial_year' => $fy,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'income_rows' => $incomeRows,
            'expense_rows' => $expenseRows,
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'net_profit_loss' => $netProfitOrLoss,
        ];
    }

    /**
     * Get Balance Sheet Report.
     */
    public function getBalanceSheet(
        ?int $marqueeId,
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

        // Fetch all active accounts of Asset, Liability, and Equity natures
        $accountsQuery = Account::where('is_active', true)
            ->whereIn('nature', ['Asset', 'Liability', 'Equity'])
            ->with(['accountType']);
            
        if ($marqueeId) {
            $accountsQuery->where('marquee_id', $marqueeId);
        } else {
            $accountsQuery->whereNull('marquee_id');
        }
        $accounts = $accountsQuery->get();

        $assetRows = [];
        $liabilityRows = [];
        $equityRows = [];

        $totalAssets = 0.0;
        $totalLiabilities = 0.0;
        $totalEquity = 0.0;

        foreach ($accounts as $account) {
            $gl = $this->getGeneralLedger($account->id, $startDate, $endDate, $branchId, $financialYearId);
            $balance = (float)$gl['closing_balance'];

            if ($balance == 0) {
                continue;
            }

            $row = [
                'account_code' => $account->account_code,
                'account_name' => $account->name,
                'type_name' => $account->accountType->name,
                'balance' => $balance,
            ];

            if ($account->nature === 'Asset') {
                $assetRows[] = $row;
                $totalAssets += $balance;
            } elseif ($account->nature === 'Liability') {
                $liabilityRows[] = $row;
                $totalLiabilities += $balance;
            } else {
                $equityRows[] = $row;
                $totalEquity += $balance;
            }
        }

        // Calculate Net Profit/Loss for the current period (Income - Expenses) to keep Balance Sheet in balance
        $pl = $this->getProfitAndLoss($marqueeId, $financialYearId, $startDate, $endDate, $branchId);
        $currentPeriodProfitLoss = $pl['net_profit_loss'];

        // Add current period profit/loss as a dynamic equity row
        if ($currentPeriodProfitLoss != 0) {
            $equityRows[] = [
                'account_code' => '3999', // Dynamic placeholder code for Current Period Earnings
                'account_name' => 'Current Period Profit/Loss',
                'type_name' => 'Current Period Earnings',
                'balance' => $currentPeriodProfitLoss,
            ];
            $totalEquity += $currentPeriodProfitLoss;
        }

        $totalLiabilitiesAndEquity = $totalLiabilities + $totalEquity;
        $difference = $totalAssets - $totalLiabilitiesAndEquity;
        $isBalanced = abs($difference) < 0.01;

        return [
            'financial_year' => $fy,
            'as_of_date' => $endDate,
            'asset_rows' => $assetRows,
            'liability_rows' => $liabilityRows,
            'equity_rows' => $equityRows,
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_equity' => $totalEquity,
            'total_liabilities_and_equity' => $totalLiabilitiesAndEquity,
            'difference' => $difference,
            'is_balanced' => $isBalanced,
        ];
    }

    /**
     * Post a SaaS Invoice creation entry to the SaaS Chart of Accounts.
     */
    public function postSaasInvoiceJournal(SaasInvoice $invoice)
    {
        $accounts = Account::whereNull('marquee_id')->get()->keyBy('account_code');
        if ($accounts->isEmpty()) return;

        $arAccount = $accounts->get('1003');
        
        // Determine monthly vs annual
        $cycleName = strtolower($invoice->billingCycle->cycle_name ?? '');
        $revAccount = str_contains($cycleName, 'annual') ? $accounts->get('4002') : $accounts->get('4001');

        if (!$arAccount || !$revAccount) return;

        $amount = $invoice->total_amount;

        $header = [
            'marquee_id' => null,
            'branch_id' => null,
            'voucher_date' => $invoice->created_at ? $invoice->created_at->format('Y-m-d') : date('Y-m-d'),
            'reference' => $invoice->invoice_number,
            'notes' => 'Auto-generated for SaaS Invoice ' . $invoice->invoice_number,
            'status' => 'posted',
        ];

        $items = [
            [
                'account_id' => $arAccount->id,
                'debit' => $amount,
                'credit' => 0,
                'narration' => 'Subscription receivable generated for User #' . $invoice->user_id,
            ],
            [
                'account_id' => $revAccount->id,
                'debit' => 0,
                'credit' => $amount,
                'narration' => 'Subscription revenue recognized',
            ],
        ];

        $this->createJournalVoucher($header, $items);
    }

    /**
     * Post a SaaS Payment creation entry to the SaaS Chart of Accounts.
     */
    public function postSaasPaymentJournal(SaasPayment $payment)
    {
        $accounts = Account::whereNull('marquee_id')->get()->keyBy('account_code');
        if ($accounts->isEmpty()) return;

        // Debit SaaS Bank/Stripe and Credit SaaS Accounts Receivable
        $bankAccount = $accounts->get('1002');
        $arAccount = $accounts->get('1003');

        if (!$bankAccount || !$arAccount) return;

        $amount = $payment->amount;

        $header = [
            'marquee_id' => null,
            'branch_id' => null,
            'voucher_date' => $payment->payment_date ? $payment->payment_date->format('Y-m-d') : date('Y-m-d'),
            'reference' => $payment->payment_reference,
            'notes' => 'Auto-generated for SaaS Payment ' . $payment->payment_reference,
            'status' => 'posted',
        ];

        $items = [
            [
                'account_id' => $bankAccount->id,
                'debit' => $amount,
                'credit' => 0,
                'narration' => 'Cash received from subscription payment ref: ' . $payment->payment_reference,
            ],
            [
                'account_id' => $arAccount->id,
                'debit' => 0,
                'credit' => $amount,
                'narration' => 'Subscription receivable cleared for Invoice #' . $payment->invoice_id,
            ],
        ];

        $this->createJournalVoucher($header, $items);
    }
}
