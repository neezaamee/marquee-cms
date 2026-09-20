<?php

namespace App\Livewire\Finance;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseBudget;
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

        // 1. Today's total expenses
        $todayExpenses = (float)(clone $baseExpenseQuery)
            ->where('expense_date', $today)
            ->sum('total_amount_base');

        // 2. Selected Period's total expenses
        $periodExpenses = (float)(clone $baseExpenseQuery)
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->sum('total_amount_base');

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

        // 6. Category-wise distribution for selected period
        $categoryBreakdown = (clone $baseExpenseQuery)
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->select('expense_category_id', DB::raw('SUM(total_amount_base) as total'), DB::raw('COUNT(id) as count'))
            ->groupBy('expense_category_id')
            ->with('category')
            ->orderByDesc('total')
            ->take(6)
            ->get();

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
