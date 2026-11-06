<?php

namespace App\Livewire\Finance;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseBudget;
use App\Models\PaymentVoucher;
use App\Models\PettyCashAccount;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ExpenseDashboard extends Component
{
    public $period = 'this_month';
    public $branch_id = '';

    public function render()
    {
        $user = auth()->user();
        $marqueeId = $user ? ($user->getActiveMarqueeId() ?: $user->marquee_id) : null;

        $today = now()->format('Y-m-d');
        
        // Resolve date range based on selected period
        switch ($this->period) {
            case 'today':
                $startDate = $today;
                $endDate = $today;
                $periodLabel = "Today";
                break;
            case 'last_month':
                $startDate = now()->subMonth()->startOfMonth()->format('Y-m-d');
                $endDate = now()->subMonth()->endOfMonth()->format('Y-m-d');
                $periodLabel = now()->subMonth()->format('F Y');
                break;
            case 'this_quarter':
                $startDate = now()->firstOfQuarter()->format('Y-m-d');
                $endDate = now()->lastOfQuarter()->format('Y-m-d');
                $periodLabel = "This Quarter";
                break;
            case 'this_year':
                $startDate = now()->startOfYear()->format('Y-m-d');
                $endDate = now()->endOfYear()->format('Y-m-d');
                $periodLabel = "Year " . date('Y');
                break;
            case 'this_month':
            default:
                $startDate = now()->startOfMonth()->format('Y-m-d');
                $endDate = now()->endOfMonth()->format('Y-m-d');
                $periodLabel = now()->format('F Y');
                break;
        }

        // Base query for expenses
        $baseExpenseQuery = Expense::where('marquee_id', $marqueeId)
            ->whereNotIn('status', [Expense::STATUS_DRAFT, Expense::STATUS_REJECTED, Expense::STATUS_CANCELLED]);

        if ($this->branch_id) {
            $baseExpenseQuery->where('branch_id', $this->branch_id);
        }

        // Base query for Operating Expense Payment Vouchers
        $basePvQuery = PaymentVoucher::where('marquee_id', $marqueeId)
            ->whereNull('expense_id')
            ->whereNotIn('status', [PaymentVoucher::STATUS_DRAFT, PaymentVoucher::STATUS_CANCELLED])
            ->where(function ($q) {
                $q->where('payee_type', PaymentVoucher::PAYEE_EXPENSE)
                  ->orWhereHas('debitAccount', function ($aq) {
                      $aq->where('nature', 'Expense')->orWhere('account_code', 'like', '5%');
                  });
            });
        if ($this->branch_id) {
            $basePvQuery->where('branch_id', $this->branch_id);
        }

        // 1. Today's total expenses (Combined)
        $todayExpenses = (float)(clone $baseExpenseQuery)
            ->where('expense_date', $today)
            ->sum('total_amount_base');
        $todayPv = (float)(clone $basePvQuery)->whereDate('voucher_date', $today)->sum('amount');
        $todayExpenses += $todayPv;

        // 2. Selected Period's total expenses (Combined)
        $periodExpenses = (float)(clone $baseExpenseQuery)
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->sum('total_amount_base');
        $periodPv = (float)(clone $basePvQuery)->whereBetween('voucher_date', [$startDate, $endDate])->sum('amount');
        $periodExpenses += $periodPv;

        // 3. Petty Cash Available in Drawers
        $pettyCashQuery = PettyCashAccount::where('marquee_id', $marqueeId)->where('is_active', true);
        if ($this->branch_id) {
            $pettyCashQuery->where('branch_id', $this->branch_id);
        }
        $pettyCashBalance = (float)$pettyCashQuery->sum('current_balance');

        // 4. Vendor outstanding AP
        $vendorOutstanding = (float)(clone $baseExpenseQuery)
            ->where('payment_method', Expense::METHOD_CREDIT)
            ->where('payment_status', 'Unpaid')
            ->sum('total_amount_base');

        // 5. Pending approvals count
        $pendingApprovals = Expense::where('marquee_id', $marqueeId)
            ->where('status', Expense::STATUS_PENDING)
            ->when($this->branch_id, fn($q) => $q->where('branch_id', $this->branch_id))
            ->count();

        // 6. Category-wise distribution for selected period (Combined)
        $allCategories = DB::table('expense_categories')->where('marquee_id', $marqueeId)->whereNull('deleted_at')->get();
        $categoriesById = $allCategories->keyBy('id');
        $categoriesByAccountId = $allCategories->whereNotNull('default_account_id')->keyBy('default_account_id');

        $expCatBreakdown = (clone $baseExpenseQuery)
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->select('expense_category_id', DB::raw('SUM(total_amount_base) as total'), DB::raw('COUNT(id) as count'))
            ->groupBy('expense_category_id')
            ->with('category')
            ->get();

        $pvCatBreakdown = (clone $basePvQuery)
            ->whereBetween('voucher_date', [$startDate, $endDate])
            ->with(['debitAccount'])
            ->get();

        $combinedCategories = [];

        foreach ($expCatBreakdown as $row) {
            $catName = $row->category->name ?? 'Unclassified / Split';
            $combinedCategories[$catName] = [
                'name' => $catName,
                'total' => (float)$row->total,
                'count' => (int)$row->count,
            ];
        }

        foreach ($pvCatBreakdown as $pv) {
            $catName = 'Operating Expenses (PV)';
            if ($pv->payee_id && isset($categoriesById[$pv->payee_id])) {
                $catName = $categoriesById[$pv->payee_id]->name;
            } elseif ($pv->debit_account_id && isset($categoriesByAccountId[$pv->debit_account_id])) {
                $catName = $categoriesByAccountId[$pv->debit_account_id]->name;
            } elseif ($pv->debitAccount) {
                $catName = $pv->debitAccount->name;
            } elseif (!empty($pv->payee_name)) {
                $catName = $pv->payee_name;
            }

            if (!isset($combinedCategories[$catName])) {
                $combinedCategories[$catName] = [
                    'name' => $catName,
                    'total' => 0.0,
                    'count' => 0,
                ];
            }
            $combinedCategories[$catName]['total'] += (float)$pv->amount;
            $combinedCategories[$catName]['count'] += 1;
        }

        usort($combinedCategories, fn($a, $b) => $b['total'] <=> $a['total']);
        $categoryBreakdown = collect(array_slice($combinedCategories, 0, 6))->map(fn($item) => (object)[
            'total' => $item['total'],
            'count' => $item['count'],
            'category' => (object)['name' => $item['name']],
        ]);

        // 7. Branch-wise distribution
        $branchBreakdown = (clone $baseExpenseQuery)
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->select('branch_id', DB::raw('SUM(total_amount_base) as total'))
            ->groupBy('branch_id')
            ->with('branch')
            ->orderByDesc('total')
            ->get();

        // 8. Budget consumption
        $year = (int)date('Y');
        $month = (int)date('m');
        $budgetQuery = ExpenseBudget::where('marquee_id', $marqueeId)->where('year', $year);
        if ($this->branch_id) {
            $budgetQuery->where('branch_id', $this->branch_id);
        }
        $allocatedBudget = (float)(clone $budgetQuery)->where(fn($q) => $q->whereNull('month')->orWhere('month', $month))->sum('allocated_amount');
        $consumedBudget = (float)(clone $budgetQuery)->where(fn($q) => $q->whereNull('month')->orWhere('month', $month))->sum('consumed_amount');

        // 9. Recent expenses list
        $recentExpenses = Expense::where('marquee_id', $marqueeId)
            ->when($this->branch_id, fn($q) => $q->where('branch_id', $this->branch_id))
            ->with(['category', 'branch', 'supplier'])
            ->orderBy('expense_date', 'desc')
            ->orderBy('id', 'desc')
            ->take(6)
            ->get();

        $branches = Branch::where('marquee_id', $marqueeId)->where('status', 'active')->get();

        return view('livewire.finance.expense-dashboard', [
            'periodLabel' => $periodLabel,
            'todayExpenses' => $todayExpenses,
            'periodExpenses' => $periodExpenses,
            'pettyCashBalance' => $pettyCashBalance,
            'vendorOutstanding' => $vendorOutstanding,
            'pendingApprovals' => $pendingApprovals,
            'categoryBreakdown' => $categoryBreakdown,
            'branchBreakdown' => $branchBreakdown,
            'allocatedBudget' => $allocatedBudget,
            'consumedBudget' => $consumedBudget,
            'recentExpenses' => $recentExpenses,
            'branches' => $branches,
        ]);
    }
}
