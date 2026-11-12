<div>
    <!-- Top Action & Navigation Bar -->
    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <a href="{{ route('payroll.index') }}" class="btn btn-falcon-default btn-sm shadow-sm" title="Back to Batches">
                        <span class="fas fa-arrow-left"></span>
                    </a>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h4 class="mb-0 fw-bold text-900">{{ $payroll->batch_number }}</h4>
                            @if($payroll->status === 'paid')
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    <span class="fas fa-check-circle me-1"></span> Fully Paid
                                </span>
                            @elseif($payroll->status === 'partially_paid')
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                    <span class="fas fa-adjust me-1"></span> Partially Paid
                                </span>
                            @elseif($payroll->status === 'approved')
                                <span class="badge bg-info-subtle text-info border border-info-subtle">
                                    <span class="fas fa-thumbs-up me-1"></span> Approved
                                </span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                    <span class="fas fa-edit me-1"></span> Draft
                                </span>
                            @endif
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                {{ \Carbon\Carbon::parse($payroll->payroll_month . '-01')->format('F Y') }}
                            </span>
                        </div>
                        <p class="text-600 fs-11 mb-0 mt-1">
                            Branch: <strong>{{ $payroll->branch ? $payroll->branch->name : 'All / Main' }}</strong>
                            &bull; Created By: <strong>{{ $payroll->creator ? $payroll->creator->name : 'System' }}</strong>
                            @if($payroll->approver)
                                &bull; Approved By: <strong>{{ $payroll->approver->name }}</strong>
                            @endif
                        </p>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2 flex-wrap">
                    @if($payroll->status === 'draft')
                        <button type="button" 
                                wire:click="approveBatch" 
                                wire:confirm="Are you sure you want to approve this payroll batch? Once approved, it is ready for cash/bank disbursement."
                                class="btn btn-info btn-sm fw-bold shadow-sm text-white">
                            <span class="fas fa-thumbs-up me-1"></span> Approve Batch
                        </button>
                    @endif

                    @if(in_array($payroll->status, ['draft', 'approved', 'partially_paid']) && $payroll->total_net_payable > $payroll->total_paid)
                        <button type="button" wire:click="openDisburseModal" class="btn btn-success btn-sm fw-bold shadow-sm">
                            <span class="fas fa-cash-register me-1"></span> Disburse / Pay Batch
                        </button>
                    @endif

                    <button type="button" onclick="window.print()" class="btn btn-falcon-default btn-sm fw-bold">
                        <span class="fas fa-print me-1"></span> Print Register
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Notifications -->
    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm py-2 px-3 mb-3" role="alert">
            <div class="d-flex align-items-center">
                <span class="fas fa-check-circle fa-lg me-2"></span>
                <span class="fs-10">{{ session('success') }}</span>
            </div>
            <button type="button" class="btn-close fs-11" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm py-2 px-3 mb-3" role="alert">
            <div class="d-flex align-items-center">
                <span class="fas fa-exclamation-triangle fa-lg me-2"></span>
                <span class="fs-10">{{ session('error') }}</span>
            </div>
            <button type="button" class="btn-close fs-11" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Payment Voucher Notice if paid -->
    @if($payroll->paymentVoucher)
        <div class="alert alert-subtle-success border-0 shadow-sm py-2 px-3 mb-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="fas fa-receipt fa-lg text-success"></span>
                    <span class="fs-11">
                        Disbursement Journal linked to Payment Voucher <strong>#{{ $payroll->paymentVoucher->voucher_no }}</strong>
                        ({{ $payroll->payment_method ? ucfirst($payroll->payment_method) : 'Cash' }})
                        on <strong>{{ $payroll->payment_date ? $payroll->payment_date->format('d M Y') : 'N/A' }}</strong>.
                        Source: <strong>{{ $payroll->cashBankAccount ? $payroll->cashBankAccount->name : 'Default Cash' }}</strong>.
                    </span>
                </div>
                <div>
                    <span class="badge bg-success text-white">Voucher Status: {{ $payroll->paymentVoucher->status }}</span>
                </div>
            </div>
        </div>
    @endif

    <!-- Financial KPI Summary Cards -->
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card h-100 border-0 shadow-sm bg-body-tertiary">
                <div class="card-body p-2 p-md-3">
                    <span class="fs-11 text-muted text-uppercase fw-bold">Employees</span>
                    <h4 class="mb-0 fw-bolder text-900 mt-1">{{ $payroll->total_employees }}</h4>
                    <span class="fs-11 text-muted">Staff Members</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card h-100 border-0 shadow-sm bg-body-tertiary">
                <div class="card-body p-2 p-md-3">
                    <span class="fs-11 text-muted text-uppercase fw-bold">Basic Salaries</span>
                    <h4 class="mb-0 fw-bolder text-700 mt-1">Rs. {{ number_format($payroll->total_basic_salary, 0) }}</h4>
                    <span class="fs-11 text-muted">Base sum</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card h-100 border-0 shadow-sm bg-body-tertiary">
                <div class="card-body p-2 p-md-3">
                    <span class="fs-11 text-muted text-uppercase fw-bold">OT + Allowances</span>
                    <h4 class="mb-0 fw-bolder text-info mt-1">Rs. {{ number_format($payroll->total_overtime_amount + $payroll->total_allowances, 0) }}</h4>
                    <span class="fs-11 text-muted">OT: {{ number_format($payroll->total_overtime_amount, 0) }}</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card h-100 border-0 shadow-sm bg-body-tertiary">
                <div class="card-body p-2 p-md-3">
                    <span class="fs-11 text-muted text-uppercase fw-bold">Total Deductions</span>
                    <h4 class="mb-0 fw-bolder text-danger mt-1">Rs. {{ number_format($payroll->total_deductions, 0) }}</h4>
                    <span class="fs-11 text-muted">Abs: {{ number_format($payroll->total_absence_deductions, 0) }} | Adv: {{ number_format($payroll->total_advance_deductions, 0) }}</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card h-100 border-0 shadow-sm bg-body-tertiary">
                <div class="card-body p-2 p-md-3">
                    <span class="fs-11 text-muted text-uppercase fw-bold">Net Payable</span>
                    <h4 class="mb-0 fw-bolder text-primary mt-1">Rs. {{ number_format($payroll->total_net_payable, 0) }}</h4>
                    <span class="fs-11 text-muted">Total Net Salaries</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card h-100 border-0 shadow-sm bg-body-tertiary">
                <div class="card-body p-2 p-md-3">
                    <span class="fs-11 text-muted text-uppercase fw-bold">Total Disbursed</span>
                    <h4 class="mb-0 fw-bolder {{ $payroll->total_paid >= $payroll->total_net_payable ? 'text-success' : 'text-warning' }} mt-1">
                        Rs. {{ number_format($payroll->total_paid, 0) }}
                    </h4>
                    <span class="fs-11 text-muted">Pending: Rs. {{ number_format(max(0, $payroll->total_net_payable - $payroll->total_paid), 0) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Itemized Staff Table Card -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-body-tertiary py-2 px-3 border-bottom">
            <div class="row g-2 align-items-center justify-content-between">
                <div class="col-12 col-md-auto d-flex align-items-center gap-2">
                    <span class="fas fa-users-cog text-primary"></span>
                    <h6 class="mb-0 fw-bold">Itemized Staff Payslips ({{ $items->total() }})</h6>
                </div>
                <div class="col-12 col-md-auto">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div class="input-group input-group-sm" style="max-width: 240px;">
                            <span class="input-group-text bg-white border-end-0"><span class="fas fa-search text-400"></span></span>
                            <input type="text" wire:model.live.debounce.300ms="search" class="form-control border-start-0" placeholder="Search staff name / code...">
                        </div>
                        <select wire:model.live="filterStatus" class="form-select form-select-sm" style="width: 130px;">
                            <option value="">All Statuses</option>
                            <option value="unpaid">Unpaid</option>
                            <option value="paid">Paid</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0 fs-10">
                    <thead class="bg-body-secondary text-700 text-uppercase">
                        <tr>
                            <th class="ps-3 py-2">Slip #</th>
                            <th class="py-2">Employee</th>
                            <th class="py-2 text-center">Attendance (P/A/L/H)</th>
                            <th class="py-2 text-end">Basic Salary</th>
                            <th class="py-2 text-end">Overtime</th>
                            <th class="py-2 text-end">Allowances</th>
                            <th class="py-2 text-end">Gross</th>
                            <th class="py-2 text-end">Absence Ded.</th>
                            <th class="py-2 text-end">Advance Ded.</th>
                            <th class="py-2 text-end">Other Ded.</th>
                            <th class="py-2 text-end">Net Salary</th>
                            <th class="py-2 text-center">Status</th>
                            <th class="pe-3 py-2 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr>
                                <td class="ps-3 fw-bold text-nowrap">
                                    <span class="text-primary">{{ $item->salary_slip_number }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div>
                                            <a href="{{ route('staff.show', $item->employee_id) }}" class="fw-bold text-900 text-decoration-none">
                                                {{ $item->employee ? $item->employee->name : 'N/A' }}
                                            </a>
                                            <div class="fs-11 text-muted">
                                                {{ $item->employee ? $item->employee->employee_id : '' }}
                                                &bull; {{ $item->employee ? $item->employee->designation : 'Staff' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center text-nowrap">
                                    <span class="badge bg-success-subtle text-success" title="Present Days">{{ $item->present_days }}P</span>
                                    <span class="badge bg-danger-subtle text-danger" title="Absent Days">{{ $item->absent_days }}A</span>
                                    @if($item->late_days > 0)
                                        <span class="badge bg-warning-subtle text-warning" title="Late Days">{{ $item->late_days }}L</span>
                                    @endif
                                    @if($item->half_days > 0)
                                        <span class="badge bg-info-subtle text-info" title="Half Days">{{ $item->half_days }}H</span>
                                    @endif
                                </td>
                                <td class="text-end fw-semibold">
                                    Rs. {{ number_format($item->basic_salary, 2) }}
                                </td>
                                <td class="text-end">
                                    @if($item->overtime_hours > 0)
                                        <span class="fw-semibold text-info">Rs. {{ number_format($item->overtime_amount, 2) }}</span>
                                        <div class="fs-11 text-muted">{{ $item->overtime_hours }} hrs</div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($item->total_allowances > 0)
                                        <span class="fw-semibold text-success">+Rs. {{ number_format($item->total_allowances, 2) }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end fw-bold text-700">
                                    Rs. {{ number_format($item->gross_salary, 2) }}
                                </td>
                                <td class="text-end">
                                    @if($item->absence_deduction > 0)
                                        <span class="text-danger fw-semibold">-Rs. {{ number_format($item->absence_deduction, 2) }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($item->advance_deduction > 0)
                                        <span class="text-warning fw-semibold">-Rs. {{ number_format($item->advance_deduction, 2) }}</span>
                                        <div class="fs-11 text-muted">Advance</div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($item->total_other_deductions > 0)
                                        <span class="text-danger fw-semibold">-Rs. {{ number_format($item->total_other_deductions, 2) }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end fw-bolder text-primary fs-10">
                                    Rs. {{ number_format($item->net_salary, 2) }}
                                </td>
                                <td class="text-center text-nowrap">
                                    @if($item->payment_status === 'paid')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            <span class="fas fa-check-circle me-1"></span> Paid
                                        </span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                            <span class="fas fa-clock me-1"></span> Unpaid
                                        </span>
                                    @endif
                                </td>
                                <td class="pe-3 text-end text-nowrap">
                                    <div class="btn-group btn-group-sm">
                                        <!-- View / Print Payslip -->
                                        <a href="{{ route('payroll.slip', $item->id) }}" target="_blank" class="btn btn-outline-secondary btn-sm py-1 px-2" title="Print Payslip">
                                            <span class="fas fa-print"></span> Slip
                                        </a>

                                        <!-- Edit Item if not paid or batch in draft/approved -->
                                        @if($item->payment_status !== 'paid' && in_array($payroll->status, ['draft', 'approved']))
                                            <button type="button" wire:click="openEditModal({{ $item->id }})" class="btn btn-outline-primary btn-sm py-1 px-2" title="Adjust Salary / Allowances / Deductions">
                                                <span class="fas fa-edit"></span>
                                            </button>
                                        @endif

                                        <!-- Single Pay Button if unpaid -->
                                        @if($item->payment_status !== 'paid')
                                            <button type="button" wire:click="openSinglePayModal({{ $item->id }})" class="btn btn-outline-success btn-sm py-1 px-2" title="Pay this Employee">
                                                <span class="fas fa-money-bill-wave"></span> Pay
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="text-center py-4 text-muted">
                                    No staff records found matching your filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($items->hasPages())
            <div class="card-footer bg-body-tertiary py-2 px-3">
                {{ $items->links() }}
            </div>
        @endif
    </div>

    <!-- Edit Employee Payslip Modal -->
    @if($isEditModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content shadow-lg border-0">
                    <div class="modal-header bg-primary text-white py-2 px-3">
                        <h5 class="modal-title fs-10 fw-bold d-flex align-items-center gap-2">
                            <span class="fas fa-edit"></span> Adjust Payslip &mdash; {{ $editEmployeeName }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeEditModal" aria-label="Close"></button>
                    </div>
                    <form wire:submit.prevent="saveItem">
                        <div class="modal-body p-3">
                            <div class="row g-3">
                                <!-- Base Salary & Overtime -->
                                <div class="col-12">
                                    <h6 class="fw-bold text-primary border-bottom pb-1 mb-2 fs-11">Basic Salary &amp; Overtime</h6>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fs-11 fw-bold">Basic Salary (Rs.)</label>
                                    <input type="number" step="0.01" wire:model="editBasicSalary" class="form-control form-control-sm" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fs-11 fw-bold">Overtime Hours</label>
                                    <input type="number" step="0.25" wire:model="editOvertimeHours" wire:change="recalculateItemModal" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fs-11 fw-bold">Overtime Rate / Hour (Rs.)</label>
                                    <input type="number" step="0.01" wire:model="editOvertimeRate" wire:change="recalculateItemModal" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-4 offset-md-8">
                                    <label class="form-label fs-11 fw-bold">Overtime Amount (Rs.)</label>
                                    <input type="number" step="0.01" wire:model="editOvertimeAmount" class="form-control form-control-sm fw-bold text-info">
                                </div>

                                <!-- Allowances -->
                                <div class="col-12">
                                    <h6 class="fw-bold text-success border-bottom pb-1 mb-2 fs-11">Allowances &amp; Bonuses</h6>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fs-11">Performance Bonus (Rs.)</label>
                                    <input type="number" step="0.01" wire:model="editBonus" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fs-11">Conveyance Allowance (Rs.)</label>
                                    <input type="number" step="0.01" wire:model="editConveyance" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fs-11">Medical Allowance (Rs.)</label>
                                    <input type="number" step="0.01" wire:model="editMedical" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fs-11">Other Allowance (Rs.)</label>
                                    <input type="number" step="0.01" wire:model="editOtherAllowance" class="form-control form-control-sm">
                                </div>

                                <!-- Deductions -->
                                <div class="col-12">
                                    <h6 class="fw-bold text-danger border-bottom pb-1 mb-2 fs-11">Deductions</h6>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fs-11">Absence Deduction (Rs.)</label>
                                    <input type="number" step="0.01" wire:model="editAbsenceDeduction" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fs-11">Salary Advance Deduction (Rs.)</label>
                                    <input type="number" step="0.01" wire:model="editAdvanceDeduction" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fs-11">Late Deduction (Rs.)</label>
                                    <input type="number" step="0.01" wire:model="editLateDeduction" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fs-11">Fine / Penalty (Rs.)</label>
                                    <input type="number" step="0.01" wire:model="editFineDeduction" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fs-11">Other Deduction (Rs.)</label>
                                    <input type="number" step="0.01" wire:model="editOtherDeduction" class="form-control form-control-sm">
                                </div>

                                <div class="col-md-9">
                                    <label class="form-label fs-11">Adjustment Notes / Justification</label>
                                    <input type="text" wire:model="editNotes" class="form-control form-control-sm" placeholder="e.g. Eid bonus included, 1 day excused leave">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-light py-2 px-3">
                            <button type="button" class="btn btn-secondary btn-sm" wire:click="closeEditModal">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm fw-bold">
                                <span class="fas fa-save me-1"></span> Save Adjustments
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Bulk Disburse Modal -->
    @if($isDisburseModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content shadow-lg border-0">
                    <div class="modal-header bg-success text-white py-2 px-3">
                        <h5 class="modal-title fs-10 fw-bold d-flex align-items-center gap-2">
                            <span class="fas fa-cash-register"></span> Disburse Monthly Payroll Batch
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeDisburseModal" aria-label="Close"></button>
                    </div>
                    <form wire:submit.prevent="executeDisburseBatch">
                        <div class="modal-body p-3">
                            <div class="alert alert-info py-2 px-3 mb-3 border-0 fs-11">
                                <span class="fas fa-info-circle me-1"></span>
                                Disbursing this batch will automatically create a <strong>Payment Voucher (CPV / BPV)</strong>, debit GL <strong>5302 (Salary Expense)</strong>, credit the selected Cash/Bank account, mark staff slips as paid, and settle mid-month salary advances.
                            </div>

                            <div class="mb-3">
                                <label class="form-label fs-11 fw-bold">Source Cash / Bank Account <span class="text-danger">*</span></label>
                                <select wire:model="disburseCashBankAccountId" class="form-select form-select-sm" required>
                                    <option value="">Select Account...</option>
                                    @foreach($cashBankAccounts as $cb)
                                        <option value="{{ $cb->id }}">{{ $cb->name }} ({{ ucfirst($cb->type) }})</option>
                                    @endforeach
                                </select>
                                @error('disburseCashBankAccountId') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fs-11 fw-bold">Payment Method <span class="text-danger">*</span></label>
                                    <select wire:model="disbursePaymentMethod" class="form-select form-select-sm" required>
                                        <option value="cash">Cash (CPV)</option>
                                        <option value="bank">Bank Transfer / Cheque (BPV)</option>
                                    </select>
                                    @error('disbursePaymentMethod') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fs-11 fw-bold">Disbursement Date <span class="text-danger">*</span></label>
                                    <input type="date" wire:model="disburseDate" class="form-control form-control-sm" required>
                                    @error('disburseDate') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fs-11 fw-bold">Cheque # / Transaction Reference</label>
                                <input type="text" wire:model="disburseReference" class="form-control form-control-sm" placeholder="e.g. CHQ-99210 or Online Ref">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fs-11 fw-bold">Notes</label>
                                <textarea wire:model="disburseNotes" class="form-control form-control-sm" rows="2" placeholder="Optional notes..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer bg-light py-2 px-3">
                            <button type="button" class="btn btn-secondary btn-sm" wire:click="closeDisburseModal">Cancel</button>
                            <button type="submit" class="btn btn-success btn-sm fw-bold">
                                <span class="fas fa-check-circle me-1"></span> Confirm &amp; Disburse Funds
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Single Employee Pay Modal -->
    @if($isSinglePayModalOpen && $singlePayItem)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content shadow-lg border-0">
                    <div class="modal-header bg-primary text-white py-2 px-3">
                        <h5 class="modal-title fs-10 fw-bold d-flex align-items-center gap-2">
                            <span class="fas fa-money-bill-wave"></span> Individual Payout &mdash; {{ $singlePayItem->employee ? $singlePayItem->employee->name : 'Staff' }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeSinglePayModal" aria-label="Close"></button>
                    </div>
                    <form wire:submit.prevent="executeSinglePay">
                        <div class="modal-body p-3">
                            <div class="card bg-light border-0 mb-3 p-3 text-center">
                                <span class="fs-11 text-muted text-uppercase fw-bold">Net Salary Payable</span>
                                <h3 class="mb-0 fw-bolder text-primary mt-1">Rs. {{ number_format($singlePayItem->net_salary - $singlePayItem->paid_amount, 2) }}</h3>
                                <span class="fs-11 text-muted">Slip: {{ $singlePayItem->salary_slip_number }}</span>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fs-11 fw-bold">Source Cash / Bank Account <span class="text-danger">*</span></label>
                                <select wire:model="singleCashBankAccountId" class="form-select form-select-sm" required>
                                    <option value="">Select Account...</option>
                                    @foreach($cashBankAccounts as $cb)
                                        <option value="{{ $cb->id }}">{{ $cb->name }} ({{ ucfirst($cb->type) }})</option>
                                    @endforeach
                                </select>
                                @error('singleCashBankAccountId') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fs-11 fw-bold">Payment Method <span class="text-danger">*</span></label>
                                    <select wire:model="singlePaymentMethod" class="form-select form-select-sm" required>
                                        <option value="cash">Cash (CPV)</option>
                                        <option value="bank">Bank Transfer (BPV)</option>
                                    </select>
                                    @error('singlePaymentMethod') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fs-11 fw-bold">Payment Date <span class="text-danger">*</span></label>
                                    <input type="date" wire:model="singlePaymentDate" class="form-control form-control-sm" required>
                                    @error('singlePaymentDate') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fs-11 fw-bold">Cheque / Ref Number</label>
                                <input type="text" wire:model="singleReference" class="form-control form-control-sm" placeholder="Optional reference">
                            </div>
                        </div>
                        <div class="modal-footer bg-light py-2 px-3">
                            <button type="button" class="btn btn-secondary btn-sm" wire:click="closeSinglePayModal">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm fw-bold">
                                <span class="fas fa-check me-1"></span> Disburse Payment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
