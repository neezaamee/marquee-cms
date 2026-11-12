<div>
    <!-- Top Action & Navigation Bar -->
    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-xl bg-warning-subtle text-warning rounded-3 d-flex align-items-center justify-content-center shadow-sm">
                        <span class="fas fa-hand-holding-usd fa-lg"></span>
                    </div>
                    <div>
                        <h4 class="mb-0 fw-bold text-900 d-flex align-items-center gap-2">
                            Staff Salary Advances &amp; Loans
                            <span class="badge bg-warning-subtle text-warning rounded-pill fs-11">Receivables</span>
                        </h4>
                        <p class="text-600 fs-11 mb-0">Record mid-month advance cash disbursements and auto-track deductions against monthly payroll.</p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" wire:click="openCreateModal" class="btn btn-primary btn-sm fw-bold shadow-sm">
                        <span class="fas fa-plus me-1"></span> Disburse New Advance
                    </button>
                    <a href="{{ route('payroll.index') }}" class="btn btn-falcon-default btn-sm fw-bold">
                        <span class="fas fa-money-check-alt me-1 text-success"></span> Monthly Payroll
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm bg-body-tertiary">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="fs-11 text-muted text-uppercase fw-bold">Advances Disbursed</span>
                            <h3 class="mb-0 fw-bolder text-warning mt-1">Rs. {{ number_format($totalGivenThisMonth, 2) }}</h3>
                            <span class="fs-11 text-muted">Target Month: {{ $filterMonth ?: 'All' }}</span>
                        </div>
                        <div class="avatar avatar-m bg-warning-subtle text-warning rounded-circle d-flex align-items-center justify-content-center">
                            <span class="fas fa-coins fa-lg"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm bg-body-tertiary">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="fs-11 text-muted text-uppercase fw-bold">Total Pending / Outstanding</span>
                            <h3 class="mb-0 fw-bolder text-danger mt-1">Rs. {{ number_format($totalPendingAll, 2) }}</h3>
                            <span class="fs-11 text-muted">Awaiting payroll deduction</span>
                        </div>
                        <div class="avatar avatar-m bg-danger-subtle text-danger rounded-circle d-flex align-items-center justify-content-center">
                            <span class="fas fa-hourglass-half fa-lg"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm bg-body-tertiary">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="fs-11 text-muted text-uppercase fw-bold">Total Recovered / Deducted</span>
                            <h3 class="mb-0 fw-bolder text-success mt-1">Rs. {{ number_format($totalDeducted, 2) }}</h3>
                            <span class="fs-11 text-muted">Successfully settled via payroll</span>
                        </div>
                        <div class="avatar avatar-m bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center">
                            <span class="fas fa-check-circle fa-lg"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main List Card -->
    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-header bg-body-tertiary py-3">
            <div class="row g-2 align-items-center justify-content-between">
                <div class="col-12 col-md-auto d-flex align-items-center gap-2 flex-wrap">
                    <div>
                        <label class="form-label fs-11 text-muted mb-0 fw-bold">Deduction Month</label>
                        <input type="month" wire:model.live="filterMonth" class="form-control form-control-sm" />
                    </div>

                    <div>
                        <label class="form-label fs-11 text-muted mb-0 fw-bold">Status</label>
                        <select wire:model.live="filterStatus" class="form-select form-select-sm">
                            <option value="all">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="deducted">Deducted (Settled)</option>
                            <option value="waived">Waived</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label fs-11 text-muted mb-0 fw-bold">Search Staff</label>
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Filter employee..." class="form-control form-control-sm" style="min-width: 160px;" />
                    </div>
                </div>
                <div class="col-12 col-md-auto text-end">
                    <span class="fs-11 text-muted">Showing {{ $advances->total() }} records</span>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            @if(session()->has('success'))
                <div class="alert alert-success border-2 d-flex align-items-center m-3" role="alert">
                    <div class="bg-success me-3 icon-item"><span class="fas fa-check-circle text-white fs-8"></span></div>
                    <p class="mb-0 flex-grow-1 text-success-800">{{ session('success') }}</p>
                    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session()->has('error'))
                <div class="alert alert-danger border-2 d-flex align-items-center m-3" role="alert">
                    <span class="fas fa-exclamation-triangle me-2"></span>
                    <p class="mb-0 flex-grow-1">{{ session('error') }}</p>
                    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="table-responsive scrollbar">
                <table class="table table-sm table-striped align-middle mb-0 fs-10 table-hover">
                    <thead class="bg-200 text-900">
                        <tr>
                            <th class="ps-3 py-2" style="width: 110px;">Date</th>
                            <th class="py-2" style="width: 240px;">Employee</th>
                            <th class="text-end py-2" style="width: 130px;">Amount</th>
                            <th class="text-center py-2" style="width: 130px;">Deduction Month</th>
                            <th class="py-2" style="width: 140px;">Payment Channel</th>
                            <th class="text-center py-2" style="width: 100px;">Status</th>
                            <th class="py-2">Reason / Notes</th>
                            <th class="text-end pe-3 py-2" style="width: 90px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($advances as $adv)
                            <tr>
                                <td class="ps-3 font-monospace">{{ $adv->advance_date->format('d M, Y') }}</td>
                                <td>
                                    <div class="fw-bold text-900">
                                        <a href="{{ route('staff.show', $adv->employee_id) }}" class="text-900 text-decoration-none">
                                            {{ $adv->employee->name ?? '—' }}
                                        </a>
                                    </div>
                                    <div class="text-muted fs-11">
                                        <span class="badge bg-secondary-subtle text-secondary me-1">{{ $adv->employee->employee_id ?? '' }}</span>
                                        {{ $adv->employee->designation ?? '' }}
                                    </div>
                                </td>
                                <td class="text-end font-monospace fw-bold text-warning">
                                    Rs. {{ number_format($adv->amount, 2) }}
                                </td>
                                <td class="text-center font-monospace">
                                    <span class="badge bg-primary-subtle text-primary">
                                        {{ \Carbon\Carbon::parse($adv->deduction_month . '-01')->format('M Y') }}
                                    </span>
                                </td>
                                <td>
                                    @if($adv->payment_method === 'cash')
                                        <span class="badge badge-subtle-success rounded-pill"><span class="fas fa-money-bill-wave me-1"></span>Cash</span>
                                    @else
                                        <span class="badge badge-subtle-primary rounded-pill"><span class="fas fa-university me-1"></span>Bank</span>
                                    @endif
                                    @if($adv->cashBankAccount)
                                        <div class="text-muted fs-11">{{ $adv->cashBankAccount->bank_name }}</div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($adv->status === 'pending')
                                        <span class="badge bg-warning-subtle text-warning rounded-pill">Pending</span>
                                    @elseif($adv->status === 'deducted')
                                        <span class="badge bg-success-subtle text-success rounded-pill">Deducted</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary rounded-pill">Waived</span>
                                    @endif
                                </td>
                                <td class="text-muted">{{ $adv->reason ?: '—' }}</td>
                                <td class="text-end pe-3">
                                    <div class="d-flex justify-content-end gap-2">
                                        @if($adv->status === 'pending')
                                            <button wire:click="waive({{ $adv->id }})" class="btn btn-link p-0 text-secondary" title="Waive Advance" onclick="confirm('Mark this advance as waived?') || event.stopImmediatePropagation()">
                                                <span class="fas fa-hand-holding"></span>
                                            </button>
                                            <button wire:click="delete({{ $adv->id }})" class="btn btn-link p-0 text-danger" title="Delete" onclick="confirm('Delete this advance record?') || event.stopImmediatePropagation()">
                                                <span class="fas fa-trash-alt"></span>
                                            </button>
                                        @else
                                            <span class="text-muted fs-11">&mdash;</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <span class="fas fa-hand-holding-usd fa-2x mb-2 d-block text-400"></span>
                                    No salary advances recorded for the selected filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($advances->hasPages())
            <div class="card-footer d-flex align-items-center justify-content-center bg-light py-2">
                {{ $advances->links() }}
            </div>
        @endif
    </div>

    <!-- Create / Edit Advance Modal -->
    @if($isFormOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-primary text-white py-3">
                        <h5 class="modal-title text-white mb-0">
                            <span class="fas fa-hand-holding-usd me-2"></span> Disburse Staff Salary Advance
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeModal" aria-label="Close"></button>
                    </div>
                    <form wire:submit.prevent="save">
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label fs-11 text-muted fw-bold">Select Employee <span class="text-danger">*</span></label>
                                <select wire:model.live="employee_id" class="form-select form-select-sm @error('employee_id') is-invalid @enderror">
                                    <option value="">Choose active employee...</option>
                                    @foreach($employees as $e)
                                        <option value="{{ $e->id }}">
                                            [{{ $e->employee_id }}] {{ $e->name }} &mdash; Salary: Rs. {{ number_format($e->salary, 0) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('employee_id') <div class="invalid-feedback">{{ $message }}</div> @enderror

                                @if($employee_id)
                                    @php $selEmp = $employees->firstWhere('id', $employee_id); @endphp
                                    @if($selEmp)
                                        <div class="alert alert-light border mt-2 p-2 fs-11 text-muted mb-0">
                                            <strong>Basic Salary:</strong> Rs. {{ number_format($selEmp->salary, 2) }} &bull;
                                            <strong>Pending Advance Balance:</strong> Rs. {{ number_format($selEmp->pending_advance_balance, 2) }}
                                        </div>
                                    @endif
                                @endif
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label fs-11 text-muted fw-bold">Disbursement Date <span class="text-danger">*</span></label>
                                    <input type="date" wire:model="advance_date" class="form-control form-control-sm @error('advance_date') is-invalid @enderror" />
                                    @error('advance_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-6">
                                    <label class="form-label fs-11 text-muted fw-bold">Advance Amount (Rs.) <span class="text-danger">*</span></label>
                                    <input type="number" step="100" min="1" wire:model="amount" placeholder="e.g. 5000" class="form-control form-control-sm font-monospace @error('amount') is-invalid @enderror" />
                                    @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label fs-11 text-muted fw-bold">Target Deduction Month <span class="text-danger">*</span></label>
                                    <input type="month" wire:model="deduction_month" class="form-control form-control-sm @error('deduction_month') is-invalid @enderror" />
                                    @error('deduction_month') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-6">
                                    <label class="form-label fs-11 text-muted fw-bold">Payment Method <span class="text-danger">*</span></label>
                                    <select wire:model.live="payment_method" class="form-select form-select-sm">
                                        <option value="cash">Cash in Hand</option>
                                        <option value="bank">Bank Transfer</option>
                                    </select>
                                </div>
                            </div>

                            @if($payment_method === 'bank' && $cashBankAccounts->count() > 0)
                                <div class="mb-3">
                                    <label class="form-label fs-11 text-muted fw-bold">Disbursing Bank Account</label>
                                    <select wire:model="cash_bank_account_id" class="form-select form-select-sm">
                                        <option value="">Select Bank Account...</option>
                                        @foreach($cashBankAccounts->where('type', 'bank') as $cb)
                                            <option value="{{ $cb->id }}">{{ $cb->bank_name }} - {{ $cb->account_number }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div class="mb-2">
                                <label class="form-label fs-11 text-muted fw-bold">Reason / Notes</label>
                                <textarea wire:model="reason" rows="2" placeholder="e.g. Medical emergency advance, family function..." class="form-control form-control-sm"></textarea>
                            </div>
                        </div>

                        <div class="modal-footer bg-light py-2">
                            <button type="button" class="btn btn-falcon-default btn-sm" wire:click="closeModal">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm fw-bold">
                                <span class="fas fa-save me-1"></span> Confirm &amp; Disburse
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
