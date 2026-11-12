<div>
    <!-- Top Action & Navigation Bar -->
    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-xl bg-success-subtle text-success rounded-3 d-flex align-items-center justify-content-center shadow-sm">
                        <span class="fas fa-file-invoice-dollar fa-lg"></span>
                    </div>
                    <div>
                        <h4 class="mb-0 fw-bold text-900 d-flex align-items-center gap-2">
                            Monthly Payroll Management
                            <span class="badge bg-success-subtle text-success rounded-pill fs-11">Salaries</span>
                        </h4>
                        <p class="text-600 fs-11 mb-0">Generate monthly employee salary batches with automatic attendance, overtime, and advance deductions.</p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" wire:click="openGenerateModal" class="btn btn-primary btn-sm fw-bold shadow-sm">
                        <span class="fas fa-magic me-1"></span> Generate Monthly Payroll
                    </button>
                    <a href="{{ route('payroll.advances') }}" class="btn btn-falcon-default btn-sm fw-bold">
                        <span class="fas fa-hand-holding-usd me-1 text-warning"></span> Staff Advances
                    </a>
                    <a href="{{ route('attendance.monthly') }}" class="btn btn-falcon-default btn-sm fw-bold">
                        <span class="fas fa-calendar-check me-1 text-info"></span> Attendance Sheet
                    </a>
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

    <!-- KPI Summary Cards -->
    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm bg-body-tertiary">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="fs-11 text-muted text-uppercase fw-bold">Total Payroll Disbursed</span>
                            <h3 class="mb-0 fw-bolder text-success mt-1">Rs. {{ number_format($totalPayrollDisbursed, 2) }}</h3>
                            <span class="fs-11 text-muted">Paid out through Cash / Bank</span>
                        </div>
                        <div class="avatar avatar-m bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center">
                            <span class="fas fa-check-double fa-lg"></span>
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
                            <span class="fs-11 text-muted text-uppercase fw-bold">Pending Review / Drafts</span>
                            <h3 class="mb-0 fw-bolder text-warning mt-1">{{ $pendingApprovalBatches }}</h3>
                            <span class="fs-11 text-muted">Batches awaiting owner approval</span>
                        </div>
                        <div class="avatar avatar-m bg-warning-subtle text-warning rounded-circle d-flex align-items-center justify-content-center">
                            <span class="fas fa-clock fa-lg"></span>
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
                            <span class="fs-11 text-muted text-uppercase fw-bold">Active Filter Year</span>
                            <h3 class="mb-0 fw-bolder text-primary mt-1">{{ $filterYear ?: 'All Years' }}</h3>
                            <span class="fs-11 text-muted">Total Batches: {{ $payrolls->total() }}</span>
                        </div>
                        <div class="avatar avatar-m bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center">
                            <span class="fas fa-calendar-alt fa-lg"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Table Card -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-body-tertiary py-2 px-3 border-bottom">
            <div class="row g-2 align-items-center justify-content-between">
                <div class="col-12 col-md-auto d-flex align-items-center gap-2">
                    <span class="fas fa-list-alt text-primary"></span>
                    <h6 class="mb-0 fw-bold">Generated Payroll Batches</h6>
                </div>
                <div class="col-12 col-md-auto">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <!-- Year filter -->
                        <div class="d-flex align-items-center gap-1">
                            <label class="fs-11 text-600 mb-0 fw-bold">Year:</label>
                            <select wire:model.live="filterYear" class="form-select form-select-sm" style="min-width: 100px;">
                                <option value="">All Years</option>
                                @for($y = (int)date('Y') + 1; $y >= 2024; $y--)
                                    <option value="{{ $y }}">{{ $y }}</option>
                                @endfor
                            </select>
                        </div>

                        <!-- Branch filter -->
                        @if($branches->count() > 1)
                        <div class="d-flex align-items-center gap-1">
                            <label class="fs-11 text-600 mb-0 fw-bold">Branch:</label>
                            <select wire:model.live="selectedBranchId" class="form-select form-select-sm" style="min-width: 140px;">
                                <option value="">All Branches</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0 fs-10">
                    <thead class="bg-body-secondary text-700 text-uppercase">
                        <tr>
                            <th class="ps-3 py-2">Batch #</th>
                            <th class="py-2">Month</th>
                            <th class="py-2">Branch</th>
                            <th class="py-2 text-center">Staff Count</th>
                            <th class="py-2 text-end">Gross Salary</th>
                            <th class="py-2 text-end">Deductions</th>
                            <th class="py-2 text-end">Net Payable</th>
                            <th class="py-2 text-end">Paid Amount</th>
                            <th class="py-2 text-center">Status</th>
                            <th class="py-2 text-center">Generated By</th>
                            <th class="pe-3 py-2 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payrolls as $batch)
                            <tr>
                                <td class="ps-3 fw-bold">
                                    <a href="{{ route('payroll.show', $batch->id) }}" class="text-primary text-decoration-none">
                                        <span class="fas fa-file-invoice text-muted me-1"></span>{{ $batch->batch_number }}
                                    </a>
                                </td>
                                <td>
                                    <span class="fw-semibold text-900">
                                        {{ \Carbon\Carbon::parse($batch->payroll_month . '-01')->format('F Y') }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ $batch->branch ? $batch->branch->name : 'All / Main' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary-subtle text-secondary rounded-pill">
                                        {{ $batch->total_employees }} Staff
                                    </span>
                                </td>
                                <td class="text-end fw-semibold text-700">
                                    Rs. {{ number_format($batch->total_gross, 2) }}
                                </td>
                                <td class="text-end text-danger fw-semibold">
                                    Rs. {{ number_format($batch->total_deductions, 2) }}
                                </td>
                                <td class="text-end fw-bolder text-primary">
                                    Rs. {{ number_format($batch->total_net, 2) }}
                                </td>
                                <td class="text-end fw-bold {{ $batch->total_paid >= $batch->total_net ? 'text-success' : 'text-warning' }}">
                                    Rs. {{ number_format($batch->total_paid, 2) }}
                                </td>
                                <td class="text-center">
                                    @if($batch->status === 'paid')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            <span class="fas fa-check-circle me-1"></span> Paid
                                        </span>
                                    @elseif($batch->status === 'partially_paid')
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                            <span class="fas fa-adjust me-1"></span> Partial
                                        </span>
                                    @elseif($batch->status === 'approved')
                                        <span class="badge bg-info-subtle text-info border border-info-subtle">
                                            <span class="fas fa-thumbs-up me-1"></span> Approved
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                            <span class="fas fa-edit me-1"></span> Draft
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center text-muted fs-11">
                                    {{ $batch->creator ? $batch->creator->name : 'System' }}
                                </td>
                                <td class="pe-3 text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('payroll.show', $batch->id) }}" class="btn btn-outline-primary btn-sm py-1 px-2" title="View & Process Batch">
                                            <span class="fas fa-eye me-1"></span> Open
                                        </a>
                                        @if(in_array($batch->status, ['draft', 'approved']) && $batch->total_paid == 0)
                                            <button type="button" 
                                                    wire:click="deleteBatch({{ $batch->id }})" 
                                                    wire:confirm="Are you sure you want to delete this payroll batch and its slips? This cannot be undone."
                                                    class="btn btn-outline-danger btn-sm py-1 px-2" 
                                                    title="Delete Batch">
                                                <span class="fas fa-trash-alt"></span>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-5 text-muted">
                                    <div class="py-4">
                                        <span class="fas fa-file-invoice-dollar fa-3x text-300 mb-2"></span>
                                        <p class="mb-2">No payroll batches generated yet for this period.</p>
                                        <button type="button" wire:click="openGenerateModal" class="btn btn-primary btn-sm">
                                            <span class="fas fa-magic me-1"></span> Generate First Payroll
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($payrolls->hasPages())
            <div class="card-footer bg-body-tertiary py-2 px-3">
                {{ $payrolls->links() }}
            </div>
        @endif
    </div>

    <!-- Generate Monthly Payroll Modal -->
    @if($isGenerateModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content shadow-lg border-0">
                    <div class="modal-header bg-primary text-white py-2 px-3">
                        <h5 class="modal-title fs-10 fw-bold d-flex align-items-center gap-2">
                            <span class="fas fa-magic"></span> Generate Monthly Payroll Batch
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeGenerateModal" aria-label="Close"></button>
                    </div>
                    <form wire:submit.prevent="generatePayroll">
                        <div class="modal-body p-3">
                            <div class="alert alert-info py-2 px-3 mb-3 border-0 fs-11">
                                <span class="fas fa-info-circle me-1"></span>
                                This will automatically pull all active staff, calculate daily attendance deductions, calculate overtime payments, and attach pending mid-month advances.
                            </div>

                            <div class="mb-3">
                                <label class="form-label fs-11 fw-bold">Payroll Month <span class="text-danger">*</span></label>
                                <input type="month" wire:model="genMonth" class="form-control form-control-sm" required>
                                @error('genMonth') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                            </div>

                            @if($branches->count() > 1)
                            <div class="mb-3">
                                <label class="form-label fs-11 fw-bold">Branch (Leave empty for All Branches)</label>
                                <select wire:model="genBranchId" class="form-select form-select-sm">
                                    <option value="">All Branches / Main</option>
                                    @foreach($branches as $b)
                                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                                    @endforeach
                                </select>
                                @error('genBranchId') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                            </div>
                            @endif

                            <div class="mb-3">
                                <label class="form-label fs-11 fw-bold">Standard Working Days in Month <span class="text-danger">*</span></label>
                                <input type="number" wire:model="genWorkingDays" class="form-control form-control-sm" min="1" max="31" required>
                                <div class="form-text fs-11 text-muted">Standard is 30 days. Daily wage rate = Basic Salary / Working Days.</div>
                                @error('genWorkingDays') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="modal-footer bg-light py-2 px-3">
                            <button type="button" class="btn btn-secondary btn-sm" wire:click="closeGenerateModal">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm fw-bold">
                                <span class="fas fa-cogs me-1"></span> Generate Payroll
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
