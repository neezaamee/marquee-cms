<?php

namespace App\Livewire\Finance;

use App\Models\Expense;
use App\Services\ExpenseService;
use Livewire\Component;

class ExpenseDetail extends Component
{
    public $expenseId;
    public $comments;
    public $confirmingAction = null; // 'approve' or 'reject'

    public function mount($id)
    {
        $this->expenseId = $id;
    }

    public function initiateApprove()
    {
        $this->confirmingAction = 'approve';
        $this->comments = '';
    }

    public function initiateReject()
    {
        $this->confirmingAction = 'reject';
        $this->comments = '';
    }

    public function cancelAction()
    {
        $this->confirmingAction = null;
    }

    public function submitApproval(ExpenseService $expenseService)
    {
        $expense = Expense::findOrFail($this->expenseId);

        if ($this->confirmingAction === 'approve') {
            try {
                $expenseService->approveExpense($expense->id, auth()->id(), $this->comments);
                session()->flash('success', 'Expense approved successfully.');
            } catch (\Exception $e) {
                session()->flash('error', $e->getMessage());
            }
        } elseif ($this->confirmingAction === 'reject') {
            $this->validate(['comments' => 'required|string']);
            try {
                $expenseService->rejectExpense($expense->id, auth()->id(), $this->comments);
                session()->flash('success', 'Expense rejected.');
            } catch (\Exception $e) {
                session()->flash('error', $e->getMessage());
            }
        }

        $this->confirmingAction = null;
    }

    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;

        $expense = Expense::where('marquee_id', $marqueeId)
            ->with(['category', 'type', 'branch', 'supplier', 'employee', 'booking', 'purchaseOrder', 'purchaseInvoice', 'currency', 'cashBankAccount', 'pettyCashAccount', 'journalVoucher', 'items.category', 'utilityBill', 'maintenanceRecord', 'approvals.user', 'approvals.role', 'attachments.uploader'])
            ->findOrFail($this->expenseId);

        // Check if user is an authorized approver for the current state
        $canUserApprove = false;
        if ($expense->status === Expense::STATUS_PENDING) {
            // Find next rule in sequence
            $lastApprovalCount = $expense->approvals()->where('action', 'Approved')->count();
            
            // Get next matching rule
            $nextRule = ExpenseApprovalRule::where('marquee_id', $marqueeId)
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
                })
                ->orderBy('sequence', 'asc')
                ->skip($lastApprovalCount)
                ->first();

            if ($nextRule && auth()->user()->role_id == $nextRule->approver_role_id) {
                $canUserApprove = true;
            }
        }

        return view('livewire.finance.expense-detail', [
            'expense' => $expense,
            'canApprove' => $canUserApprove,
        ]);
    }
}
// Helper logic import inside model block if needed
use App\Models\ExpenseApprovalRule;
