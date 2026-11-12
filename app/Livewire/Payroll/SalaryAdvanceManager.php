<?php

namespace App\Livewire\Payroll;

use App\Models\CashBankAccount;
use App\Models\Employee;
use App\Models\SalaryAdvance;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class SalaryAdvanceManager extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Filters
    public string $filterMonth = '';
    public string $filterStatus = 'all'; // 'all', 'pending', 'deducted', 'waived'
    public string $search = '';

    // Form modal state
    public bool $isFormOpen = false;
    public ?int $editId = null;

    // Form inputs
    public ?int $employee_id = null;
    public string $advance_date = '';
    public string $amount = '';
    public string $deduction_month = '';
    public string $payment_method = 'cash';
    public ?int $cash_bank_account_id = null;
    public string $reason = '';

    protected function rules(): array
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'advance_date' => 'required|date',
            'amount' => 'required|numeric|min:1',
            'deduction_month' => 'required|string|regex:/^\d{4}-\d{2}$/',
            'payment_method' => 'required|in:cash,bank',
            'cash_bank_account_id' => 'nullable|exists:cash_bank_accounts,id',
            'reason' => 'nullable|string|max:500',
        ];
    }

    public function mount()
    {
        $this->filterMonth = Carbon::today()->format('Y-m');
        $this->advance_date = Carbon::today()->format('Y-m-d');
        $this->deduction_month = Carbon::today()->format('Y-m');
    }

    public function updatedFilterMonth()
    {
        $this->resetPage();
    }

    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function getMarqueeId(): ?int
    {
        $user = auth()->user();
        return $user ? ($user->getActiveMarqueeId() ?: $user->marquee_id) : null;
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->advance_date = Carbon::today()->format('Y-m-d');
        $this->deduction_month = Carbon::today()->format('Y-m');
        $this->isFormOpen = true;
    }

    public function closeModal(): void
    {
        $this->resetForm();
        $this->isFormOpen = false;
    }

    public function resetForm(): void
    {
        $this->editId = null;
        $this->employee_id = null;
        $this->advance_date = Carbon::today()->format('Y-m-d');
        $this->amount = '';
        $this->deduction_month = Carbon::today()->format('Y-m');
        $this->payment_method = 'cash';
        $this->cash_bank_account_id = null;
        $this->reason = '';
        $this->resetValidation();
    }

    public function save(): void
    {
        $this->validate();
        $marqueeId = $this->getMarqueeId();
        $userId = auth()->id();

        $emp = Employee::findOrFail($this->employee_id);

        if ($this->editId) {
            $advance = SalaryAdvance::where('marquee_id', $marqueeId)->findOrFail($this->editId);
            $advance->update([
                'employee_id' => $this->employee_id,
                'branch_id' => $emp->branch_id,
                'advance_date' => $this->advance_date,
                'amount' => (float) $this->amount,
                'deduction_month' => $this->deduction_month,
                'payment_method' => $this->payment_method,
                'cash_bank_account_id' => $this->cash_bank_account_id,
                'reason' => $this->reason ?: null,
                'updated_by' => $userId,
            ]);

            session()->flash('success', 'Salary advance updated successfully.');
        } else {
            SalaryAdvance::create([
                'marquee_id' => $marqueeId,
                'branch_id' => $emp->branch_id,
                'employee_id' => $this->employee_id,
                'advance_date' => $this->advance_date,
                'amount' => (float) $this->amount,
                'deduction_month' => $this->deduction_month,
                'payment_method' => $this->payment_method,
                'cash_bank_account_id' => $this->cash_bank_account_id,
                'status' => 'pending',
                'deducted_amount' => 0.00,
                'reason' => $this->reason ?: null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            session()->flash('success', 'Salary advance recorded and disbursed successfully.');
        }

        $this->closeModal();
    }

    public function waive(int $id): void
    {
        $marqueeId = $this->getMarqueeId();
        $advance = SalaryAdvance::where('marquee_id', $marqueeId)->findOrFail($id);

        if ($advance->status === 'deducted') {
            session()->flash('error', 'Cannot waive an advance that has already been deducted in payroll.');
            return;
        }

        $advance->update(['status' => 'waived']);
        session()->flash('success', 'Advance marked as waived.');
    }

    public function delete(int $id): void
    {
        $marqueeId = $this->getMarqueeId();
        $advance = SalaryAdvance::where('marquee_id', $marqueeId)->findOrFail($id);

        if ($advance->status === 'deducted') {
            session()->flash('error', 'Cannot delete an advance that has already been deducted in payroll.');
            return;
        }

        $advance->delete();
        session()->flash('success', 'Salary advance deleted successfully.');
    }

    public function render()
    {
        $marqueeId = $this->getMarqueeId();

        $query = SalaryAdvance::where('marquee_id', $marqueeId)
            ->with(['employee', 'cashBankAccount']);

        if ($this->filterMonth) {
            $query->where('deduction_month', $this->filterMonth);
        }

        if ($this->filterStatus !== 'all') {
            $query->where('status', $this->filterStatus);
        }

        if ($this->search) {
            $query->whereHas('employee', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('employee_id', 'like', '%' . $this->search . '%');
            });
        }

        $advances = $query->orderBy('advance_date', 'desc')->paginate(15);

        // KPI stats for this marquee
        $totalGivenThisMonth = (float) SalaryAdvance::where('marquee_id', $marqueeId)
            ->where('deduction_month', $this->filterMonth ?: Carbon::today()->format('Y-m'))
            ->sum('amount');

        $totalPendingAll = (float) SalaryAdvance::where('marquee_id', $marqueeId)
            ->where('status', 'pending')
            ->selectRaw('COALESCE(SUM(amount - deducted_amount), 0) as pending_total')
            ->value('pending_total');

        $totalDeducted = (float) SalaryAdvance::where('marquee_id', $marqueeId)
            ->where('status', 'deducted')
            ->sum('deducted_amount');

        $employees = Employee::where('marquee_id', $marqueeId)
            ->where('status', 'Active')
            ->orderBy('name')
            ->get();

        $cashBankAccounts = CashBankAccount::where('marquee_id', $marqueeId)
            ->where('status', 'active')
            ->with('account')
            ->get();

        return view('livewire.payroll.salary-advance-manager', [
            'advances' => $advances,
            'employees' => $employees,
            'cashBankAccounts' => $cashBankAccounts,
            'totalGivenThisMonth' => $totalGivenThisMonth,
            'totalPendingAll' => $totalPendingAll,
            'totalDeducted' => $totalDeducted,
        ])->layout('layouts.admin');
    }
}
