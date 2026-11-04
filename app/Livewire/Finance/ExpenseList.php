<?php

namespace App\Livewire\Finance;

use App\Models\Branch;
use App\Models\CashBankAccount;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PettyCashAccount;
use App\Models\Supplier;
use App\Repositories\ExpenseRepositoryInterface;
use App\Services\ExpenseService;
use Livewire\Component;
use Livewire\WithPagination;

class ExpenseList extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Pagination
    public $perPage = 15;

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

    public function updatingPerPage() { $this->resetPage(); }
    public function updatingSearch() { $this->resetPage(); }
    public function updatingStatus() { $this->resetPage(); }
    public function updatingBranchId() { $this->resetPage(); }
    public function updatingExpenseCategoryId() { $this->resetPage(); }
    public function updatingSupplierId() { $this->resetPage(); }
    public function updatingPaymentMethod() { $this->resetPage(); }
    public function updatingPaymentStatus() { $this->resetPage(); }
    public function updatingStartDate() { $this->resetPage(); }
    public function updatingEndDate() { $this->resetPage(); }

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
            $user = auth()->user();
            $marqueeId = $user ? ($user->getActiveMarqueeId() ?: $user->marquee_id) : null;
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

    public function postDirectly($id, ExpenseService $expenseService)
    {
        try {
            $expense = Expense::findOrFail($id);
            if (!in_array($expense->status, [Expense::STATUS_DRAFT, Expense::STATUS_SUBMITTED, Expense::STATUS_APPROVED])) {
                session()->flash('error', 'Expense cannot be posted directly in its current status.');
                return;
            }

            if ($expense->status === Expense::STATUS_DRAFT || $expense->status === Expense::STATUS_SUBMITTED) {
                $expenseService->submitExpense($expense->id);
            } elseif ($expense->status === Expense::STATUS_APPROVED) {
                $expenseService->postExpenseJournalEntry($expense);
            }

            $freshExpense = $expense->fresh();
            session()->flash('success', "Expense {$freshExpense->expense_number} posted to General Ledger successfully (Status: {$freshExpense->status}).");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("ExpenseList postDirectly error: " . $e->getMessage());
            session()->flash('error', 'Failed to post expense: ' . $e->getMessage());
        }
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

    public function getActiveFilterParams(): array
    {
        return array_filter([
            'status' => $this->status,
            'branch_id' => $this->branch_id,
            'expense_category_id' => $this->expense_category_id,
            'supplier_id' => $this->supplier_id,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'search' => $this->search,
        ], fn($val) => $val !== '' && $val !== null);
    }

    public function getPrintUrlProperty(): string
    {
        return route('expenses.print', $this->getActiveFilterParams());
    }

    public function getPdfUrlProperty(): string
    {
        return route('expenses.pdf', $this->getActiveFilterParams());
    }

    public function exportCSV(ExpenseRepositoryInterface $repository)
    {
        $user = auth()->user();
        $marqueeId = $user ? ($user->getActiveMarqueeId() ?: $user->marquee_id) : null;

        $filters = array_merge($this->getActiveFilterParams(), [
            'marquee_id' => $marqueeId,
        ]);

        $expenses = $repository->all($filters);

        $filename = 'Expense_Register_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($expenses) {
            $file = fopen('php://output', 'w');
            // Add UTF-8 BOM for Excel compatibility
            fputs($file, "\xEF\xBB\xBF");

            fputcsv($file, [
                'Voucher No',
                'Date',
                'Branch',
                'Department',
                'Category',
                'Payee / Vendor',
                'Description',
                'Reference No',
                'Subtotal (PKR)',
                'Tax Amount (PKR)',
                'Discount (PKR)',
                'Total Amount (PKR)',
                'Payment Method',
                'Payment Status',
                'Status',
            ]);

            foreach ($expenses as $exp) {
                $payee = '—';
                if ($exp->supplier) {
                    $payee = $exp->supplier->name;
                } elseif ($exp->employee) {
                    $payee = trim($exp->employee->first_name . ' ' . $exp->employee->last_name);
                }

                $categoryName = $exp->is_multiline ? 'Split Entries' : ($exp->category->name ?? '—');

                fputcsv($file, [
                    $exp->expense_number,
                    $exp->expense_date ? $exp->expense_date->format('Y-m-d') : '',
                    $exp->branch->name ?? '—',
                    $exp->department ?? '—',
                    $categoryName,
                    $payee,
                    $exp->description ?? '—',
                    $exp->reference_number ?? '—',
                    number_format((float)$exp->amount, 2, '.', ''),
                    number_format((float)$exp->tax_amount, 2, '.', ''),
                    number_format((float)$exp->discount_amount, 2, '.', ''),
                    number_format((float)$exp->total_amount, 2, '.', ''),
                    $exp->payment_method ?? 'Cash',
                    $exp->payment_status ?? 'Paid',
                    $exp->status,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportExcel(ExpenseRepositoryInterface $repository)
    {
        return $this->exportCSV($repository);
    }

    public function render(ExpenseRepositoryInterface $repository)
    {
        $user = auth()->user();
        $marqueeId = $user ? ($user->getActiveMarqueeId() ?: $user->marquee_id) : null;

        // Auto-reconcile credit expenses for suppliers whose balance has been cleared
        if ($marqueeId) {
            $unpaidCreditExpenses = Expense::where('marquee_id', $marqueeId)
                ->where('payment_method', Expense::METHOD_CREDIT)
                ->whereIn('payment_status', ['Unpaid', 'Partially Paid'])
                ->whereNotIn('status', [Expense::STATUS_DRAFT, Expense::STATUS_REJECTED, Expense::STATUS_CANCELLED])
                ->with('supplier')
                ->get();

            foreach ($unpaidCreditExpenses as $exp) {
                if ($exp->supplier && $exp->supplier->current_balance <= 0.01) {
                    $exp->update([
                        'paid_amount' => $exp->total_amount,
                        'payment_status' => 'Paid',
                        'status' => in_array($exp->status, [Expense::STATUS_APPROVED, Expense::STATUS_POSTED]) ? Expense::STATUS_CLOSED : $exp->status,
                    ]);
                }
            }
        }

        $filters = array_merge($this->getActiveFilterParams(), [
            'marquee_id' => $marqueeId,
        ]);

        // Fetch paginated expenses
        $perPageCount = is_numeric($this->perPage) ? (int)$this->perPage : 15;
        $expenses = $repository->paginate($perPageCount, $filters);

        $branches = Branch::where('marquee_id', $marqueeId)->where('status', 'active')->get();
        $categories = ExpenseCategory::where('marquee_id', $marqueeId)->where('is_active', true)->get();
        $suppliers = Supplier::where('marquee_id', $marqueeId)->get();

        $bankAccounts = CashBankAccount::where('marquee_id', $marqueeId)->get();
        $pettyDrawers = PettyCashAccount::where('marquee_id', $marqueeId)->where('is_active', true)->get();

        // Calculate past-due payables metrics
        $todayStr = \Carbon\Carbon::today()->format('Y-m-d');
        $pastDueBase = Expense::where('marquee_id', $marqueeId)
            ->whereNotIn('status', [Expense::STATUS_DRAFT, Expense::STATUS_REJECTED, Expense::STATUS_CANCELLED])
            ->where('payment_status', '!=', 'Paid')
            ->where(function ($q) use ($todayStr) {
                $q->where(function ($sub) use ($todayStr) {
                    $sub->whereNotNull('due_date')->whereDate('due_date', '<', $todayStr);
                })->orWhere(function ($sub) use ($todayStr) {
                    $sub->whereNull('due_date')->whereDate('expense_date', '<', $todayStr);
                });
            });

        $pastDuePayablesCount = (clone $pastDueBase)->count();
        $pastDuePayablesTotal = (float) (clone $pastDueBase)
            ->selectRaw('COALESCE(SUM(total_amount - COALESCE(paid_amount, 0)), 0) as balance')
            ->value('balance');

        return view('livewire.finance.expense-list', [
            'expenses' => $expenses,
            'branches' => $branches,
            'categories' => $categories,
            'suppliers' => $suppliers,
            'bankAccounts' => $bankAccounts,
            'pettyDrawers' => $pettyDrawers,
            'pastDuePayablesCount' => $pastDuePayablesCount,
            'pastDuePayablesTotal' => $pastDuePayablesTotal,
        ]);
    }
}
