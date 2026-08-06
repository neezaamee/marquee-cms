<?php

namespace App\Livewire\Finance;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseBudget;
use App\Models\ExpenseUtilityBill;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ExpenseDashboard extends Component
{
    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;
        $today = now()->format('Y-m-d');
        $startOfMonth = now()->startOfMonth()->format('Y-m-d');
        $endOfMonth = now()->endOfMonth()->format('Y-m-d');

        // 1. Today's total expenses
        $todayExpenses = (float)Expense::where('marquee_id', $marqueeId)
            ->where('expense_date', $today)
            ->whereNotIn('status', [Expense::STATUS_DRAFT, Expense::STATUS_REJECTED, Expense::STATUS_CANCELLED])
            ->sum('total_amount_base');

        // 2. Month's total expenses
        $monthExpenses = (float)Expense::where('marquee_id', $marqueeId)
            ->whereBetween('expense_date', [$startOfMonth, $endOfMonth])
            ->whereNotIn('status', [Expense::STATUS_DRAFT, Expense::STATUS_REJECTED, Expense::STATUS_CANCELLED])
            ->sum('total_amount_base');

        // 3. Pending approvals count
        $pendingApprovals = Expense::where('marquee_id', $marqueeId)
            ->where('status', Expense::STATUS_PENDING)
            ->count();

        // 4. Vendor outstanding AP
        $vendorOutstanding = (float)Expense::where('marquee_id', $marqueeId)
            ->where('payment_method', Expense::METHOD_CREDIT)
            ->where('payment_status', 'Unpaid')
            ->whereNotIn('status', [Expense::STATUS_DRAFT, Expense::STATUS_REJECTED, Expense::STATUS_CANCELLED])
            ->sum('total_amount_base');

        // 5. Utility Bills Due Count
        $utilityBillsDue = Expense::where('marquee_id', $marqueeId)
            ->where('status', Expense::STATUS_POSTED)
            ->where('payment_method', Expense::METHOD_CREDIT)
            ->where('payment_status', 'Unpaid')
            ->whereHas('utilityBill')
            ->count();

        // 6. Category-wise distribution
        $categoryBreakdown = Expense::where('marquee_id', $marqueeId)
            ->whereBetween('expense_date', [$startOfMonth, $endOfMonth])
            ->whereNotIn('status', [Expense::STATUS_DRAFT, Expense::STATUS_REJECTED, Expense::STATUS_CANCELLED])
            ->select('expense_category_id', DB::raw('SUM(total_amount_base) as total'))
            ->groupBy('expense_category_id')
            ->with('category')
            ->get();

        // 7. Branch-wise distribution
        $branchBreakdown = Expense::where('marquee_id', $marqueeId)
            ->whereBetween('expense_date', [$startOfMonth, $endOfMonth])
            ->whereNotIn('status', [Expense::STATUS_DRAFT, Expense::STATUS_REJECTED, Expense::STATUS_CANCELLED])
            ->select('branch_id', DB::raw('SUM(total_amount_base) as total'))
            ->groupBy('branch_id')
            ->with('branch')
            ->get();

        // 8. Budget consumption
        $year = (int)date('Y');
        $month = (int)date('m');
        $allocatedBudget = (float)ExpenseBudget::where('marquee_id', $marqueeId)->where('year', $year)->where(fn($q) => $q->whereNull('month')->orWhere('month', $month))->sum('allocated_amount');
        $consumedBudget = (float)ExpenseBudget::where('marquee_id', $marqueeId)->where('year', $year)->where(fn($q) => $q->whereNull('month')->orWhere('month', $month))->sum('consumed_amount');

        // 9. Recent expenses list
        $recentExpenses = Expense::where('marquee_id', $marqueeId)
            ->with(['category', 'branch'])
            ->orderBy('expense_date', 'desc')
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();

        return view('livewire.finance.expense-dashboard', [
            'todayExpenses' => $todayExpenses,
            'monthExpenses' => $monthExpenses,
            'pendingApprovals' => $pendingApprovals,
            'vendorOutstanding' => $vendorOutstanding,
            'utilityBillsDue' => $utilityBillsDue,
            'categoryBreakdown' => $categoryBreakdown,
            'branchBreakdown' => $branchBreakdown,
            'allocatedBudget' => $allocatedBudget,
            'consumedBudget' => $consumedBudget,
            'recentExpenses' => $recentExpenses,
        ]);
    }
}
