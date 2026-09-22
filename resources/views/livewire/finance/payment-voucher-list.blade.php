<div>
    <!-- Title Header Card -->
    <div class="card mb-3">
        <div class="card-body py-3">
            <div class="d-flex flex-between-center row gy-2">
                <div class="col-auto">
                    <h5 class="mb-1 text-primary fw-bold">
                        <span class="fas fa-file-invoice-dollar me-2"></span>Payment Vouchers (CPV / BPV)
                    </h5>
                    <p class="fs-11 text-600 mb-0">Generate pre-payment formal acknowledgment vouchers for Suppliers, Third-Party Vendors & Expenses, and post to General Ledger only upon actual disbursement.</p>
                </div>
                <div class="col-auto d-flex gap-2">
                    <a href="{{ route('finance.payment-vouchers.create') }}?type=CPV" class="btn btn-sm btn-outline-success">
                        <span class="fas fa-money-bill-wave me-1"></span>New CPV (Cash)
                    </a>
                    <a href="{{ route('finance.payment-vouchers.create') }}?type=BPV" class="btn btn-sm btn-outline-primary">
                        <span class="fas fa-university me-1"></span>New BPV (Bank)
                    </a>
                    <a href="{{ route('finance.payment-vouchers.create') }}" class="btn btn-sm btn-primary">
                        <span class="fas fa-plus me-1"></span>New Voucher
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    @if(session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show fs-12 py-2" role="alert">
            <span class="fas fa-check-circle me-1"></span>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show fs-12 py-2" role="alert">
            <span class="fas fa-times-circle me-1"></span>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Metric Summary Cards -->
    <div class="row g-3 mb-3">
        <!-- Total Disbursed & Posted -->
        <div class="col-sm-6 col-lg-4">
            <div class="card h-100 border-start border-success border-4 shadow-none">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-uppercase fs-11 fw-bold text-500">Paid & Posted to GL</div>
                            <div class="fs-17 fw-bolder font-monospace text-success">Rs. {{ number_format($totalPostedAmount, 2) }}</div>
                            <div class="fs-11 text-500 mt-1">{{ $totalPostedCount }} vouchers disbursed</div>
                        </div>
                        <div class="avatar avatar-l bg-subtle-success rounded-circle d-flex align-items-center justify-content-center">
                            <span class="fas fa-check-double text-success fs-8"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Disbursement -->
        <div class="col-sm-6 col-lg-4">
            <div class="card h-100 border-start border-warning border-4 shadow-none">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-uppercase fs-11 fw-bold text-500">Pending Disbursement (Unposted)</div>
                            <div class="fs-17 fw-bolder font-monospace text-warning">Rs. {{ number_format($totalPendingAmount, 2) }}</div>
                            <div class="fs-11 text-500 mt-1">{{ $totalPendingCount }} vouchers awaiting payment/signing</div>
                        </div>
                        <div class="avatar avatar-l bg-subtle-warning rounded-circle d-flex align-items-center justify-content-center">
                            <span class="fas fa-hourglass-half text-warning fs-8"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Vouchers Recorded -->
        <div class="col-sm-6 col-lg-4">
            <div class="card h-100 border-start border-primary border-4 shadow-none">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-uppercase fs-11 fw-bold text-500">Total Vouchers Managed</div>
                            <div class="fs-17 fw-bolder font-monospace text-primary">{{ $vouchers->total() }}</div>
                            <div class="fs-11 text-500 mt-1">CPV (Cash) & BPV (Bank) records</div>
                        </div>
                        <div class="avatar avatar-l bg-subtle-primary rounded-circle d-flex align-items-center justify-content-center">
                            <span class="fas fa-receipt text-primary fs-8"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="card mb-3 shadow-none border">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="fas fa-search text-secondary"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Search Voucher #, Payee, Ref...">
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <select wire:model.live="filterType" class="form-select form-select-sm">
                        <option value="">All Types (CPV & BPV)</option>
                        <option value="CPV">CPV (Cash Payment)</option>
                        <option value="BPV">BPV (Bank Payment)</option>
                    </select>
                </div>
                <div class="col-md-2 col-6">
                    <select wire:model.live="filterStatus" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="approved">Approved (Pending Disbursement)</option>
                        <option value="posted">Paid & Posted</option>
                        <option value="draft">Draft (Unapproved)</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                @if(count($branches) > 1)
                <div class="col-md-2 col-6">
                    <select wire:model.live="filterBranch" class="form-select form-select-sm">
                        <option value="">All Branches</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-3 col-12 d-flex gap-1">
                    <input type="date" wire:model.live="startDate" class="form-control form-control-sm" placeholder="From Date">
                    <input type="date" wire:model.live="endDate" class="form-control form-control-sm" placeholder="To Date">
                </div>
            </div>
        </div>
    </div>

    <!-- Vouchers Table -->
    <div class="card shadow-none border">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-striped table-hover fs-11 mb-0 align-middle">
                    <thead class="bg-200 text-800">
                        <tr>
                            <th class="ps-3" style="width: 130px;">Voucher #</th>
                            <th style="width: 100px;">Date</th>
                            <th>Payee / Beneficiary</th>
                            <th>Debit Account</th>
                            <th>Disbursing Source</th>
                            <th class="text-end" style="width: 120px;">Amount (PKR)</th>
                            <th class="text-center" style="width: 140px;">Status</th>
                            <th class="text-end pe-3" style="width: 160px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vouchers as $v)
                            <tr>
                                <td class="ps-3 font-monospace fw-bold">
                                    <span class="badge {{ $v->voucher_type === 'CPV' ? 'bg-success-subtle text-success' : 'bg-primary-subtle text-primary' }} me-1">
                                        {{ $v->voucher_type }}
                                    </span>
                                    <a href="{{ route('finance.payment-vouchers.show', $v->id) }}" class="text-decoration-none">
                                        {{ $v->voucher_no }}
                                    </a>
                                </td>
                                <td>{{ $v->voucher_date->format('d M, Y') }}</td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $v->payee_name }}</div>
                                    <div class="fs-10 text-muted">
                                        <span class="badge badge-subtle-secondary" style="font-size: 9px;">
                                            {{ ucfirst($v->payee_type) }}
                                        </span>
                                        @if($v->reference_no)
                                            Ref: <span class="font-mono">{{ $v->reference_no }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 180px;" title="{{ $v->debitAccount->name ?? '—' }}">
                                        <span class="font-mono text-secondary">{{ $v->debitAccount->account_code ?? '' }}</span>
                                        {{ $v->debitAccount->name ?? '—' }}
                                    </div>
                                </td>
                                <td>
                                    <div class="text-dark">{{ $v->cashBankAccount->account->name ?? 'Cash/Bank' }}</div>
                                    @if($v->cheque_no)
                                        <div class="fs-10 text-muted font-mono">Chq: {{ $v->cheque_no }}</div>
                                    @endif
                                </td>
                                <td class="text-end font-monospace fw-bold fs-12 text-dark">
                                    {{ number_format($v->amount, 2) }}
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $v->status_badge_class }} rounded-pill px-2 py-1">
                                        {{ $v->status_label }}
                                    </span>
                                    @if($v->journalVoucher)
                                        <div class="fs-10 font-mono text-success mt-1" title="Posted GL Journal Voucher">
                                            <i class="fas fa-link me-1"></i>{{ $v->journalVoucher->voucher_no }}
                                        </div>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <!-- View -->
                                        <a href="{{ route('finance.payment-vouchers.show', $v->id) }}" class="btn btn-falcon-default btn-xs" title="View Details">
                                            <i class="fas fa-eye text-primary"></i>
                                        </a>

                                        <!-- Print Voucher Slip -->
                                        <a href="{{ route('finance.payment-vouchers.print', $v->id) }}" target="_blank" class="btn btn-falcon-default btn-xs" title="Print Pre-Payment Voucher Slip">
                                            <i class="fas fa-print text-secondary"></i>
                                        </a>

                                        @if($v->status === 'approved' || $v->status === 'draft')
                                            <!-- Disburse & Post button -->
                                            <button wire:click="openDisburseModal({{ $v->id }})" class="btn btn-falcon-success btn-xs" title="Confirm Payment & Post to Ledger">
                                                <i class="fas fa-check-double text-success"></i>
                                            </button>

                                            <!-- Edit -->
                                            <a href="{{ route('finance.payment-vouchers.edit', $v->id) }}" class="btn btn-falcon-default btn-xs" title="Edit Voucher">
                                                <i class="fas fa-edit text-info"></i>
                                            </a>

                                            <!-- Cancel -->
                                            <button wire:click="openCancelModal({{ $v->id }})" class="btn btn-falcon-default btn-xs" title="Cancel Voucher">
                                                <i class="fas fa-times text-danger"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fas fa-receipt fs-2 mb-2 d-block text-300"></i>
                                    No payment vouchers found matching your criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($vouchers->hasPages())
                <div class="p-3 border-top d-flex justify-content-end">
                    {{ $vouchers->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Disburse & Post Confirmation Modal -->
    @if($showDisburseModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0, 0, 0, 0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <form wire:submit.prevent="confirmDisburse">
                        <div class="modal-header bg-success text-white">
                            <h6 class="modal-title text-white">
                                <span class="fas fa-hand-holding-usd me-2"></span>Confirm Payment Disbursement & GL Posting
                            </h6>
                            <button type="button" wire:click="$set('showDisburseModal', false)" class="btn-close btn-close-white" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="alert alert-subtle-success mb-3 fs-11">
                                <strong>Physical Payment Confirmation:</strong> By confirming this disbursement, the transaction will be permanently posted to the General Ledger (crediting the cash/bank account) and the corresponding supplier/vendor ledger will be updated.
                            </div>

                            <div class="row g-2 mb-3 bg-light p-2 rounded border">
                                <div class="col-6">
                                    <span class="text-muted fs-11 d-block">Voucher Number</span>
                                    <strong class="font-mono text-primary">{{ $disbursingVoucherNo }}</strong>
                                </div>
                                <div class="col-6 text-end">
                                    <span class="text-muted fs-11 d-block">Payment Amount</span>
                                    <strong class="font-mono fs-13 text-success">Rs. {{ number_format($disbursingAmount, 2) }}</strong>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fs-11 fw-bold">Disbursement / Payment Date</label>
                                <input type="date" wire:model="disbursementDate" class="form-control form-control-sm" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fs-11 fw-bold">Disbursement Notes / Receiver Acknowledgment</label>
                                <textarea wire:model="disbursementNotes" class="form-control form-control-sm" rows="2" placeholder="e.g. Cash handed over to supplier rep / Signed voucher copy received in office"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer bg-light py-2">
                            <button type="button" wire:click="$set('showDisburseModal', false)" class="btn btn-falcon-default btn-sm">Cancel</button>
                            <button type="submit" class="btn btn-success btn-sm">
                                <span class="fas fa-check-circle me-1"></span>Disburse & Post to GL
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Cancel Confirmation Modal -->
    @if($showCancelModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0, 0, 0, 0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <form wire:submit.prevent="confirmCancel">
                        <div class="modal-header bg-danger text-white">
                            <h6 class="modal-title text-white">
                                <span class="fas fa-exclamation-triangle me-2"></span>Cancel Payment Voucher
                            </h6>
                            <button type="button" wire:click="$set('showCancelModal', false)" class="btn-close btn-close-white" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4">
                            <p class="fs-12 mb-3">Are you sure you want to cancel this payment voucher? This voucher will be marked as cancelled and cannot be disbursed.</p>
                            <div class="mb-3">
                                <label class="form-label fs-11 fw-bold">Cancellation Reason</label>
                                <textarea wire:model="cancelReason" class="form-control form-control-sm" rows="2" placeholder="Reason for cancelling this voucher..." required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer bg-light py-2">
                            <button type="button" wire:click="$set('showCancelModal', false)" class="btn btn-falcon-default btn-sm">Close</button>
                            <button type="submit" class="btn btn-danger btn-sm">Cancel Voucher</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
