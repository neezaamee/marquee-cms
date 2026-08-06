<?php

namespace App\Livewire\Finance;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Supplier;
use App\Repositories\ExpenseRepositoryInterface;
use App\Services\ExpenseService;
use Livewire\Component;
use Livewire\WithPagination;

class ExpenseList extends Component
{
    use WithPagination;

    // Filters
    public $search = '';
    public $status = '';
    public $branch_id = '';
    public $expense_category_id = '';
    public $supplier_id = '';
    public $payment_method = '';
    public $payment_status = '';
    public $start_date = '';
    public $end_date = '';

    // Bulk actions
    public $selectedExpenses = [];
    public $selectAll = false;

    // Accounts Payable clearing details
    public $payingExpenseId;
    public $payMethod = 'Cash';
    public $payBankAccountId;
    public $payPettyCashAccountId;
    public $payReference;
    public $isPayModalOpen = false;

    protected $listeners = ['refreshList' => '$refresh'];

    public function updatedSelectAll($value)
    {
        if ($value) {
            $marqueeId = auth()->user()->marquee_id;
            $this->selectedExpenses = Expense::where('marquee_id', $marqueeId)->pluck('id')->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->selectedExpenses = [];
        }
    }

    public function submitBulk(ExpenseService $expenseService)
    {
        $count = 0;
        foreach ($this->selectedExpenses as $id) {
            $expense = Expense::find($id);
            if ($expense && $expense->status === Expense::STATUS_DRAFT) {
                $expenseService->submitExpense($id);
                $count++;
            }
        }
        $this->selectedExpenses = [];
        $this->selectAll = false;
        session()->flash('success', "Submitted {$count} expenses for approval.");
    }

    public function postBulk(ExpenseService $expenseService)
    {
        $count = 0;
        foreach ($this->selectedExpenses as $id) {
            $expense = Expense::find($id);
            if ($expense && $expense->status === Expense::STATUS_APPROVED) {
                $expenseService->postExpenseJournalEntry($expense);
                $count++;
            }
        }
        $this->selectedExpenses = [];
        $this->selectAll = false;
        session()->flash('success', "Posted {$count} expenses to general ledger.");
    }

    public function delete($id, ExpenseRepositoryInterface $repository)
    {
        $expense = Expense::findOrFail($id);
        if (!in_array($expense->status, [Expense::STATUS_DRAFT, Expense::STATUS_REJECTED])) {
            session()->flash('error', 'Only Draft or Rejected expenses can be deleted.');
            return;
        }

        // Delete attachments
        foreach ($expense->attachments as $attach) {
            if (\Storage::disk('public')->exists($attach->file_path)) {
                \Storage::disk('public')->delete($attach->file_path);
            }
            $attach->delete();
        }

        $repository->delete($id);
        session()->flash('success', 'Expense deleted successfully.');
    }

    public function openPayModal($id)
    {
        $this->payingExpenseId = $id;
        $this->payMethod = 'Cash';
        $this->payBankAccountId = '';
        $this->payPettyCashAccountId = '';
        $this->payReference = '';
        $this->isPayModalOpen = true;
    }

    public function clearPayModal()
    {
        $this->isPayModalOpen = false;
    }

    public function processCreditPayment(ExpenseService $expenseService)
    {
        $this->validate([
            'payMethod' => 'required|in:Cash,Bank,Petty Cash',
            'payBankAccountId' => 'required_if:payMethod,Bank|nullable|exists:cash_bank_accounts,id',
            'payPettyCashAccountId' => 'required_if:payMethod,Petty Cash|nullable|exists:petty_cash_accounts,id',
            'payReference' => 'nullable|string|max:100',
        ]);

        try {
            $expenseService->payCreditExpense(
                $this->payingExpenseId,
                $this->payMethod,
                $this->payBankAccountId ?: null,
                $this->payPettyCashAccountId ?: null,
                $this->payReference
            );

            session()->flash('success', 'Accounts payable invoice cleared successfully.');
            $this->isPayModalOpen = false;
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render(ExpenseRepositoryInterface $repository)
    {
        $marqueeId = auth()->user()->marquee_id;

        $filters = [
            'status' => $this->status,
            'branch_id' => $this->branch_id,
            'expense_category_id' => $this->expense_category_id,
            'supplier_id' => $this->supplier_id,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'search' => $this->search,
        ];

        // Fetch paginated expenses
        $expenses = $repository->all($filters);
        
        // Paginate manually since repo returns collections for reports or we can paginate in repo.
        // Let's implement manual pagination for repository collection
        $currentPage = \Livewire\Features\SupportPagination\SupportPagination::getPage();
        $perPage = 10;
        $paginatedExpenses = new \Illuminate\Pagination\LengthAwarePaginator(
            $expenses->forPage($currentPage, $perPage),
            $expenses->count(),
            $perPage,
            $currentPage,
            ['path' => url()->current()]
        );

        $branches = Branch::where('marquee_id', $marqueeId)->where('status', 'active')->get();
        $categories = ExpenseCategory::where('marquee_id', $marqueeId)->where('is_active', true)->get();
        $suppliers = Supplier::where('marquee_id', $marqueeId)->get();

        $bankAccounts = \App\Models\CashBankAccount::where('marquee_id', $marqueeId)->get();
        $pettyDrawers = PettyCashAccount::where('marquee_id', $marqueeId)->where('is_active', true)->get();

        return view('livewire.finance.expense-list', [
            'expenses' => $paginatedExpenses,
            'branches' => $branches,
            'categories' => $categories,
            'suppliers' => $suppliers,
            'bankAccounts' => $bankAccounts,
            'pettyDrawers' => $pettyDrawers,
        ]);
    }
}
