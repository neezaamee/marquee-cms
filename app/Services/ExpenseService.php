<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Expense;
use App\Models\ExpenseApproval;
use App\Models\ExpenseApprovalRule;
use App\Models\ExpenseBudget;
use App\Models\PettyCashAccount;
use App\Models\PettyCashReconciliation;
use App\Models\RecurringExpense;
use App\Models\SupplierLedger;
use App\Notifications\ExpenseNotification;
use App\Services\AccountingService;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;

class ExpenseService
{
    protected $accountingService;
    protected $inventoryService;

    public function __construct(AccountingService $accountingService, InventoryService $inventoryService)
    {
        $this->accountingService = $accountingService;
        $this->inventoryService = $inventoryService;
    }

    /**
     * Submit an expense for approval.
     */
    public function submitExpense(int $expenseId): Expense
    {
        return DB::transaction(function () use ($expenseId) {
            $expense = Expense::findOrFail($expenseId);

            if ($expense->status !== Expense::STATUS_DRAFT) {
                throw new InvalidArgumentException("Only Draft expenses can be submitted.");
            }

            // Check budget consumption warnings before submitting
            $this->checkBudgetWarning($expense);

            // Find matching approval rules
            $rule = $this->getNextApprovalRule($expense, 0);

            if ($rule) {
                $expense->update([
                    'status' => Expense::STATUS_PENDING,
                ]);
                $this->notifyApprover($expense, $rule);
            } else {
                // If no rules exist, auto-approve it
                $expense->update([
                    'status' => Expense::STATUS_APPROVED,
                ]);
                $this->processApprovedExpense($expense);
            }

            return $expense;
        });
    }

    /**
     * Approve an expense.
     */
    public function approveExpense(int $expenseId, int $approverId, ?string $comments = null): Expense
    {
        return DB::transaction(function () use ($expenseId, $approverId, $comments) {
            $expense = Expense::findOrFail($expenseId);

            if ($expense->status !== Expense::STATUS_PENDING) {
                throw new InvalidArgumentException("This expense is not pending approval.");
            }

            $user = DB::table('users')->where('id', $approverId)->first();
            $roleId = $user->role_id;

            // Log this approval step
            ExpenseApproval::create([
                'expense_id' => $expense->id,
                'user_id' => $approverId,
                'role_id' => $roleId,
                'action' => 'Approved',
                'comments' => $comments,
            ]);

            // Determine next approval rule sequence
            $lastApproval = ExpenseApproval::where('expense_id', $expense->id)
                ->where('action', 'Approved')
                ->count();

            $nextRule = $this->getNextApprovalRule($expense, $lastApproval);

            if ($nextRule) {
                // Shift to the next approver role
                $this->notifyApprover($expense, $nextRule);
            } else {
                // Final approval reached
                $expense->update([
                    'status' => Expense::STATUS_APPROVED,
                ]);
                $this->processApprovedExpense($expense);
            }

            return $expense;
        });
    }

    /**
     * Reject an expense.
     */
    public function rejectExpense(int $expenseId, int $approverId, ?string $comments = null): Expense
    {
        return DB::transaction(function () use ($expenseId, $approverId, $comments) {
            $expense = Expense::findOrFail($expenseId);

            if ($expense->status !== Expense::STATUS_PENDING) {
                throw new InvalidArgumentException("This expense is not pending approval.");
            }

            $user = DB::table('users')->where('id', $approverId)->first();
            $roleId = $user->role_id;

            ExpenseApproval::create([
                'expense_id' => $expense->id,
                'user_id' => $approverId,
                'role_id' => $roleId,
                'action' => 'Rejected',
                'comments' => $comments,
            ]);

            $expense->update([
                'status' => Expense::STATUS_REJECTED,
            ]);

            // Notify creator
            $creator = $expense->creator;
            if ($creator) {
                $creator->notify(new ExpenseNotification(
                    'Expense Rejected',
                    "Your expense {$expense->expense_number} was rejected: {$comments}",
                    'danger'
                ));
            }

            return $expense;
        });
    }

    /**
     * Get the next approval rule for an expense.
     */
    protected function getNextApprovalRule(Expense $expense, int $currentSequenceOffset): ?ExpenseApprovalRule
    {
        // Fetch rules matching threshold, branch, department, and category
        $query = ExpenseApprovalRule::where('marquee_id', $expense->marquee_id)
            ->where('min_amount', '<=', $expense->total_amount_base)
            ->where(function ($q) use ($expense) {
                $q->whereNull('branch_id')
                  ->orWhere('branch_id', $expense->branch_id);
            })
            ->where(function ($q) use ($expense) {
                $q->whereNull('department')
                  ->orWhere('department', $expense->department);
            })
            ->where(function ($q) use ($expense) {
                $q->whereNull('category_id')
                  ->orWhere('category_id', $expense->expense_category_id);
            });

        return $query->orderBy('sequence', 'asc')
            ->skip($currentSequenceOffset)
            ->first();
    }

    /**
     * Process an approved expense (Deduct Petty cash / Record Accounts Payable / Post journal entries).
     */
    protected function processApprovedExpense(Expense $expense): void
    {
        // 1. Deduct Petty Cash drawer balance if method is Petty Cash
        if ($expense->payment_method === Expense::METHOD_PETTY_CASH && $expense->petty_cash_account_id) {
            $drawer = PettyCashAccount::findOrFail($expense->petty_cash_account_id);
            if ($drawer->current_balance < $expense->total_amount) {
                throw new InvalidArgumentException("Insufficient balance in petty cash account: {$drawer->account_name}");
            }
            $drawer->decrement('current_balance', $expense->total_amount);
            $expense->update(['payment_status' => 'Paid', 'status' => Expense::STATUS_PAID]);
        } 
        // For cash/bank payments, mark paid automatically upon approval
        elseif (in_array($expense->payment_method, [Expense::METHOD_CASH, Expense::METHOD_BANK])) {
            $expense->update(['payment_status' => 'Paid', 'status' => Expense::STATUS_PAID]);
        }
        // For accounts payable, mark unpaid
        elseif ($expense->payment_method === Expense::METHOD_CREDIT) {
            $expense->update(['payment_status' => 'Unpaid']);
        }

        // 2. Consume budget
        $this->consumeBudget($expense);

        // 3. Post double-entry accounting
        $this->postExpenseJournalEntry($expense);
    }

    /**
     * Generate double-entry accounting voucher and post it.
     */
    public function postExpenseJournalEntry(Expense $expense): void
    {
        if ($expense->journal_voucher_id) {
            return; // Already posted
        }

        $marqueeId = $expense->marquee_id;
        $category = $expense->category;
        
        // Find Debit Account (default_account_id on category or generic root Expense 5000)
        $debitAccountId = $category->default_account_id ?? Account::where('marquee_id', $marqueeId)->where('account_code', '5000')->value('id');

        if (!$debitAccountId) {
            throw new InvalidArgumentException("No valid expense debit account mapped for category: {$category->name}");
        }

        // Find Credit Account
        $creditAccountId = null;
        if ($expense->payment_method === Expense::METHOD_CASH) {
            $creditAccountId = Account::where('marquee_id', $marqueeId)->where('account_code', '1001')->value('id');
        } elseif ($expense->payment_method === Expense::METHOD_BANK && $expense->cash_bank_account_id) {
            $creditAccountId = DB::table('cash_bank_accounts')->where('id', $expense->cash_bank_account_id)->value('gl_account_id');
        } elseif ($expense->payment_method === Expense::METHOD_PETTY_CASH && $expense->petty_cash_account_id) {
            $creditAccountId = PettyCashAccount::where('id', $expense->petty_cash_account_id)->value('gl_account_id');
        } elseif ($expense->payment_method === Expense::METHOD_CREDIT) {
            $creditAccountId = Account::where('marquee_id', $marqueeId)->where('account_code', '2001')->value('id');
        }

        if (!$creditAccountId) {
            throw new InvalidArgumentException("No valid credit account mapped for payment method: {$expense->payment_method}");
        }

        $header = [
            'marquee_id' => $marqueeId,
            'branch_id' => $expense->branch_id,
            'voucher_date' => $expense->expense_date->format('Y-m-d'),
            'reference' => $expense->expense_number,
            'notes' => "Auto-posted Expense Voucher: {$expense->expense_number}. " . $expense->description,
            'status' => 'posted',
        ];

        // Prepare line items
        $items = [
            [
                'account_id' => $debitAccountId,
                'debit' => $expense->total_amount,
                'credit' => 0.00,
                'narration' => "Debit Expense - Voucher #{$expense->expense_number}",
            ],
            [
                'account_id' => $creditAccountId,
                'debit' => 0.00,
                'credit' => $expense->total_amount,
                'narration' => "Credit Payment - Voucher #{$expense->expense_number}",
            ]
        ];

        // Generate journal voucher
        $voucher = $this->accountingService->createJournalVoucher($header, $items);

        // Record in Supplier Ledger if it is accounts payable (Credit)
        if ($expense->payment_method === Expense::METHOD_CREDIT && $expense->supplier_id) {
            $this->inventoryService->recordSupplierTransaction(
                $marqueeId,
                $expense->supplier_id,
                $expense->expense_date->format('Y-m-d'),
                0.00, // Debit
                $expense->total_amount, // Credit (Increases accounts payable balance)
                'Expense',
                $expense->id,
                $voucher->voucher_no,
                "Outstanding expense payable: Voucher #{$expense->expense_number}"
            );
        }

        $expense->update([
            'journal_voucher_id' => $voucher->id,
            'status' => Expense::STATUS_POSTED,
        ]);
    }

    /**
     * Process cash/bank payment of an unpaid Accounts Payable credit expense.
     */
    public function payCreditExpense(int $expenseId, string $paymentMethod, ?int $bankAccountId = null, ?int $pettyCashAccountId = null, ?string $refNumber = null): Expense
    {
        return DB::transaction(function () use ($expenseId, $paymentMethod, $bankAccountId, $pettyCashAccountId, $refNumber) {
            $expense = Expense::findOrFail($expenseId);

            if ($expense->payment_method !== Expense::METHOD_CREDIT || $expense->payment_status === 'Paid') {
                throw new InvalidArgumentException("Expense is either not a credit expense or is already paid.");
            }

            $marqueeId = $expense->marquee_id;

            // Find Debit Account: Accounts Payable (2001)
            $debitAccountId = Account::where('marquee_id', $marqueeId)->where('account_code', '2001')->value('id');

            // Find Credit Account
            $creditAccountId = null;
            if ($paymentMethod === Expense::METHOD_CASH) {
                $creditAccountId = Account::where('marquee_id', $marqueeId)->where('account_code', '1001')->value('id');
            } elseif ($paymentMethod === Expense::METHOD_BANK && $bankAccountId) {
                $creditAccountId = DB::table('cash_bank_accounts')->where('id', $bankAccountId)->value('gl_account_id');
            } elseif ($paymentMethod === Expense::METHOD_PETTY_CASH && $pettyCashAccountId) {
                $creditAccountId = PettyCashAccount::where('id', $pettyCashAccountId)->value('gl_account_id');
                
                // Deduct Petty Cash drawer
                $drawer = PettyCashAccount::findOrFail($pettyCashAccountId);
                if ($drawer->current_balance < $expense->total_amount) {
                    throw new InvalidArgumentException("Insufficient Petty Cash balance.");
                }
                $drawer->decrement('current_balance', $expense->total_amount);
            }

            if (!$debitAccountId || !$creditAccountId) {
                throw new InvalidArgumentException("Account mapping failed for payment transaction.");
            }

            $header = [
                'marquee_id' => $marqueeId,
                'branch_id' => $expense->branch_id,
                'voucher_date' => now()->format('Y-m-d'),
                'reference' => "PAY-{$expense->expense_number}",
                'notes' => "Vendor Payment for AP Expense: {$expense->expense_number}",
                'status' => 'posted',
            ];

            $items = [
                [
                    'account_id' => $debitAccountId,
                    'debit' => $expense->total_amount, // Debit decreases payables
                    'credit' => 0.00,
                    'narration' => "Debit Accounts Payable - Invoice #{$expense->expense_number}",
                ],
                [
                    'account_id' => $creditAccountId,
                    'debit' => 0.00,
                    'credit' => $expense->total_amount, // Credit decreases assets
                    'narration' => "Credit Asset - Payment for #{$expense->expense_number}",
                ]
            ];

            $voucher = $this->accountingService->createJournalVoucher($header, $items);

            // Record debit in supplier ledger to decrease vendor balance
            if ($expense->supplier_id) {
                $this->inventoryService->recordSupplierTransaction(
                    $marqueeId,
                    $expense->supplier_id,
                    now()->format('Y-m-d'),
                    $expense->total_amount, // Debit (decreases outstanding balance)
                    0.00, // Credit
                    'SupplierPayment',
                    $expense->id,
                    $voucher->voucher_no,
                    "Payment cleared for credit expense voucher #{$expense->expense_number}"
                );
            }

            $expense->update([
                'payment_status' => 'Paid',
                'status' => Expense::STATUS_CLOSED,
                'reference_number' => $refNumber ?? $expense->reference_number,
            ]);

            return $expense;
        });
    }

    /**
     * Check budget limits and notify if exceeded.
     */
    protected function checkBudgetWarning(Expense $expense): void
    {
        $budget = $this->findMatchingBudget($expense);

        if ($budget) {
            $remaining = $budget->allocated_amount - $budget->consumed_amount;
            if ($expense->total_amount_base > $remaining) {
                // Notify manager / budget owner
                $users = DB::table('users')
                    ->where('marquee_id', $expense->marquee_id)
                    ->whereIn('role_id', function ($query) {
                        $query->select('id')->from('roles')->whereIn('name', ['super_admin', 'owner', 'accountant']);
                    })->get();

                foreach ($users as $userData) {
                    $user = \App\Models\User::find($userData->id);
                    if ($user) {
                        $user->notify(new ExpenseNotification(
                            'Budget Warning Limit',
                            "Submitting expense ({$expense->total_amount} PKR) exceeds the budget allocated for category: " . $expense->category->name,
                            'warning'
                        ));
                    }
                }
            }
        }
    }

    /**
     * Update consumed budget amounts.
     */
    protected function consumeBudget(Expense $expense): void
    {
        $budget = $this->findMatchingBudget($expense);

        if ($budget) {
            $budget->increment('consumed_amount', $expense->total_amount_base);
        }
    }

    /**
     * Find matching budget registry by category/branch/year.
     */
    protected function findMatchingBudget(Expense $expense): ?ExpenseBudget
    {
        $year = $expense->expense_date->year;
        $month = $expense->expense_date->month;

        // Try Month Specific Budget first
        $budget = ExpenseBudget::where('marquee_id', $expense->marquee_id)
            ->where('year', $year)
            ->where('month', $month)
            ->where('category_id', $expense->expense_category_id)
            ->where(function ($q) use ($expense) {
                $q->whereNull('branch_id')
                  ->orWhere('branch_id', $expense->branch_id);
            })
            ->where(function ($q) use ($expense) {
                $q->whereNull('department')
                  ->orWhere('department', $expense->department);
            })
            ->first();

        // Fallback to Annual Category Budget
        if (!$budget) {
            $budget = ExpenseBudget::where('marquee_id', $expense->marquee_id)
                ->where('year', $year)
                ->whereNull('month')
                ->where('category_id', $expense->expense_category_id)
                ->where(function ($q) use ($expense) {
                    $q->whereNull('branch_id')
                      ->orWhere('branch_id', $expense->branch_id);
                })
                ->where(function ($q) use ($expense) {
                    $q->whereNull('department')
                      ->orWhere('department', $expense->department);
                })
                ->first();
        }

        return $budget;
    }

    /**
     * Send notifications to role group.
     */
    protected function notifyApprover(Expense $expense, ExpenseApprovalRule $rule): void
    {
        $users = \App\Models\User::where('marquee_id', $expense->marquee_id)
            ->where('role_id', $rule->approver_role_id)
            ->get();

        foreach ($users as $user) {
            $user->notify(new ExpenseNotification(
                'Pending Expense Approval Request',
                "Expense voucher {$expense->expense_number} ({$expense->total_amount} {$expense->currency->code}) is pending your approval.",
                'info'
            ));
        }
    }

    /**
     * Petty cash drawer replenishment.
     */
    public function replenishPettyCash(int $accountId, float $amount, string $sourceMethod, ?int $bankAccountId = null): void
    {
        DB::transaction(function () use ($accountId, $amount, $sourceMethod, $bankAccountId) {
            $drawer = PettyCashAccount::findOrFail($accountId);
            $marqueeId = $drawer->marquee_id;

            // Debit Petty Cash (Asset increase)
            $debitAccountId = $drawer->gl_account_id;

            // Credit Cash (1001) or Bank Account gl mapping
            $creditAccountId = null;
            if ($sourceMethod === Expense::METHOD_CASH) {
                $creditAccountId = Account::where('marquee_id', $marqueeId)->where('account_code', '1001')->value('id');
            } elseif ($sourceMethod === Expense::METHOD_BANK && $bankAccountId) {
                $creditAccountId = DB::table('cash_bank_accounts')->where('id', $bankAccountId)->value('gl_account_id');
            }

            if (!$debitAccountId || !$creditAccountId) {
                throw new InvalidArgumentException("Debit and Credit mappings must be defined for replenishment.");
            }

            // Build double entry voucher
            $header = [
                'marquee_id' => $marqueeId,
                'branch_id' => $drawer->branch_id,
                'voucher_date' => now()->format('Y-m-d'),
                'reference' => "REP-{$drawer->id}-" . date('Ymd'),
                'notes' => "Replenish petty cash account: {$drawer->account_name}",
                'status' => 'posted',
            ];

            $items = [
                [
                    'account_id' => $debitAccountId,
                    'debit' => $amount,
                    'credit' => 0.00,
                    'narration' => "Petty Cash Drawer Replenishment Debit",
                ],
                [
                    'account_id' => $creditAccountId,
                    'debit' => 0.00,
                    'credit' => $amount,
                    'narration' => "Replenishment credit from " . ($sourceMethod === Expense::METHOD_CASH ? 'Cash' : 'Bank'),
                ]
            ];

            $this->accountingService->createJournalVoucher($header, $items);
            $drawer->increment('current_balance', $amount);
        });
    }

    /**
     * Reconciliation Audit drawer log.
     */
    public function reconcilePettyCash(int $accountId, float $physicalBalance, ?string $notes = null): PettyCashReconciliation
    {
        return DB::transaction(function () use ($accountId, $physicalBalance, $notes) {
            $drawer = PettyCashAccount::findOrFail($accountId);
            $systemBalance = $drawer->current_balance;
            $diff = $physicalBalance - $systemBalance;
            $status = abs($diff) < 0.01 ? 'Balanced' : 'Discrepancy';

            $reconciliation = PettyCashReconciliation::create([
                'petty_cash_account_id' => $drawer->id,
                'reconciliation_date' => now()->format('Y-m-d'),
                'system_balance' => $systemBalance,
                'physical_balance' => $physicalBalance,
                'difference' => $diff,
                'status' => $status,
                'notes' => $notes,
                'created_by' => auth()->id(),
            ]);

            // Adjust system balance to match physical
            $drawer->update(['current_balance' => $physicalBalance]);

            // If there's a discrepancy, we can post a Journal Voucher adjusting the balance (omitted here for simplicity, but easily added)

            return $reconciliation;
        });
    }

    /**
     * Process console job for active recurring expense generation.
     */
    public function generateScheduledRecurringExpenses(): int
    {
        $today = now()->format('Y-m-d');
        $templates = RecurringExpense::active()
            ->where('next_generation_date', '<=', $today)
            ->get();

        $count = 0;

        foreach ($templates as $tmpl) {
            DB::transaction(function () use ($tmpl, &$count) {
                // Generate base expense
                $expenseNumber = 'EXP-REC-' . date('YmdHis') . rand(10, 99);
                $currency = Currency::where('marquee_id', $tmpl->marquee_id)->where('is_base', true)->first()
                    ?? Currency::where('marquee_id', $tmpl->marquee_id)->first();

                $expense = Expense::create([
                    'marquee_id' => $tmpl->marquee_id,
                    'branch_id' => $tmpl->branch_id,
                    'expense_number' => $expenseNumber,
                    'expense_date' => now(),
                    'department' => $tmpl->department,
                    'cost_center' => $tmpl->cost_center,
                    'expense_category_id' => $tmpl->expense_category_id,
                    'expense_type_id' => $tmpl->expense_type_id,
                    'supplier_id' => $tmpl->supplier_id,
                    'employee_id' => $tmpl->employee_id,
                    'currency_id' => $currency->id,
                    'exchange_rate' => 1.000000,
                    'description' => "Scheduled auto-generated: " . $tmpl->description,
                    'amount' => $tmpl->amount,
                    'tax_amount' => $tmpl->tax_amount,
                    'discount_amount' => $tmpl->discount_amount,
                    'total_amount' => $tmpl->total_amount,
                    'total_amount_base' => $tmpl->total_amount,
                    'payment_method' => Expense::METHOD_CREDIT, // Default to payable/credit
                    'payment_status' => 'Unpaid',
                    'status' => Expense::STATUS_DRAFT, // Created as draft so user can review/approve
                ]);

                // Calculate next generation date
                $next = now();
                switch ($tmpl->frequency) {
                    case 'Daily':
                        $next->addDay();
                        break;
                    case 'Weekly':
                        $next->addWeek();
                        break;
                    case 'Monthly':
                        $next->addMonth();
                        break;
                    case 'Quarterly':
                        $next->addMonths(3);
                        break;
                    case 'Yearly':
                        $next->addYear();
                        break;
                }

                $tmpl->update([
                    'last_generated_date' => now(),
                    'next_generation_date' => $next->format('Y-m-d'),
                ]);

                $count++;
            });
        }

        return $count;
    }
}
