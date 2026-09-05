<div>
    <!-- Title Card -->
    <div class="card mb-3">
        <div class="card-body py-3">
            <div class="d-flex flex-between-center row gy-2">
                <div class="col-auto">
                    <h5 class="mb-1 text-primary fw-bold">
                        <span class="fas fa-wallet me-2"></span>Payment Ledger & Posting Control
                    </h5>
                    <p class="fs-11 text-600 mb-0">Centralized control screen for recording, verifying, and posting event booking payments into Cash & Bank accounts.</p>
                </div>
                <div class="col-auto">
                    <span class="badge badge-subtle-primary p-2 fs-11">
                        <span class="fas fa-shield-alt me-1"></span>Two-Stage Payment Verification Active
                    </span>
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
    @if(session()->has('warning'))
        <div class="alert alert-warning alert-dismissible fade show fs-12 py-2" role="alert">
            <span class="fas fa-exclamation-triangle me-1"></span>{{ session('warning') }}
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
        <!-- Total Received -->
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 border-start border-primary border-4 shadow-none">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-uppercase fs-11 fw-bold text-500">Total Received</div>
                            <div class="fs-17 fw-bolder font-monospace text-primary">Rs. {{ number_format($metrics['total_received'], 0) }}</div>
                            <div class="fs-11 text-500 mt-1">{{ $metrics['total_received_count'] }} payment entries recorded</div>
                        </div>
                        <div class="avatar avatar-l bg-subtle-primary rounded-circle d-flex align-items-center justify-content-center">
                            <span class="fas fa-hand-holding-usd text-primary fs-8"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Accountant Posting -->
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 border-start border-warning border-4 shadow-none bg-warning-subtle">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-uppercase fs-11 fw-bold text-warning-emphasis">
                                Pending Posting <span class="badge bg-warning text-dark rounded-pill ms-1">{{ $metrics['pending_posting_count'] }}</span>
                            </div>
                            <div class="fs-17 fw-bolder font-monospace text-warning-emphasis">Rs. {{ number_format($metrics['pending_posting_amount'], 0) }}</div>
                            <div class="fs-11 text-600 mt-1">
                                Cash: <span class="fw-bold text-dark">Rs. {{ number_format($metrics['pending_cash_amount'], 0) }}</span> | Bank: <span class="fw-bold text-dark">Rs. {{ number_format($metrics['pending_bank_amount'], 0) }}</span>
                            </div>
                        </div>
                        <div class="avatar avatar-l bg-warning rounded-circle d-flex align-items-center justify-content-center">
                            <span class="fas fa-clock text-white fs-8"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Posted to Financial Accounts -->
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 border-start border-success border-4 shadow-none">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-uppercase fs-11 fw-bold text-500">Posted (Cash & Bank)</div>
                            <div class="fs-17 fw-bolder font-monospace text-success">Rs. {{ number_format($metrics['posted_amount'], 0) }}</div>
                            <div class="fs-11 text-500 mt-1">
                                Cash: <span class="fw-bold text-success">Rs. {{ number_format($metrics['posted_cash_amount'], 0) }}</span> | Bank: <span class="fw-bold text-info">Rs. {{ number_format($metrics['posted_bank_amount'], 0) }}</span>
                            </div>
                        </div>
                        <div class="avatar avatar-l bg-subtle-success rounded-circle d-flex align-items-center justify-content-center">
                            <span class="fas fa-check-double text-success fs-8"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rejected / Reversed -->
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 border-start border-danger border-4 shadow-none">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-uppercase fs-11 fw-bold text-500">Rejected & Reversed</div>
                            <div class="fs-17 fw-bolder font-monospace text-danger">Rs. {{ number_format($metrics['rejected_amount'] + $metrics['reversed_amount'], 0) }}</div>
                            <div class="fs-11 text-500 mt-1">
                                Rejected: {{ $metrics['rejected_count'] }} | Reversed: {{ $metrics['reversed_count'] }}
                            </div>
                        </div>
                        <div class="avatar avatar-l bg-subtle-danger rounded-circle d-flex align-items-center justify-content-center">
                            <span class="fas fa-undo-alt text-danger fs-8"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-center">
                <!-- Search -->
                <div class="col-md-3">
                    <label class="form-label fs-11 fw-bold text-600 mb-1">Search Payments</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><span class="fas fa-search text-400"></span></span>
                        <input wire:model.live.debounce.300ms="search" type="text" class="form-control" placeholder="Search Payment #, Booking #, Customer, Ref..." />
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="col-md-2">
                    <label class="form-label fs-11 fw-bold text-600 mb-1">Posting Status</label>
                    <select wire:model.live="statusFilter" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="pending_posting">Pending Posting</option>
                        <option value="posted">Posted</option>
                        <option value="rejected">Rejected</option>
                        <option value="reversed">Reversed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <!-- Branch Filter -->
                @if($branches->count() > 1)
                <div class="col-md-2">
                    <label class="form-label fs-11 fw-bold text-600 mb-1">Branch</label>
                    <select wire:model.live="branchFilter" class="form-select form-select-sm">
                        <option value="">All Branches</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <!-- Payment Method -->
                <div class="col-md-2">
                    <label class="form-label fs-11 fw-bold text-600 mb-1">Method</label>
                    <select wire:model.live="paymentMethod" class="form-select form-select-sm">
                        <option value="">All Methods</option>
                        <option value="Cash">Cash</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Cheque">Cheque</option>
                        <option value="Card">Card</option>
                        <option value="Online Transfer">Online Transfer</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <!-- Start Date -->
                <div class="col-md-1.5 col-sm-6">
                    <label class="form-label fs-11 fw-bold text-600 mb-1">From</label>
                    <input wire:model.live="startDate" type="date" class="form-control form-control-sm" />
                </div>

                <!-- End Date -->
                <div class="col-md-1.5 col-sm-6">
                    <label class="form-label fs-11 fw-bold text-600 mb-1">To</label>
                    <input wire:model.live="endDate" type="date" class="form-control form-control-sm" />
                </div>
            </div>
        </div>
    </div>

    <!-- Ledger Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-striped border-0 fs-11 mb-0 align-middle">
                    <thead class="bg-light text-800">
                        <tr>
                            <th class="ps-3">Payment #</th>
                            <th>Booking # & Customer</th>
                            <th>Branch</th>
                            <th>Date</th>
                            <th>Method & Ref</th>
                            <th class="text-end">Amount</th>
                            <th class="text-center">Posting Status</th>
                            <th>Recorded By</th>
                            <th>Target Account / Posted By</th>
                            <th class="text-center" style="width: 140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                            @php
                                $statusBadge = match($payment->status) {
                                    'pending_posting', 'received' => 'warning',
                                    'posted' => 'success',
                                    'rejected' => 'danger',
                                    'reversed' => 'dark',
                                    'cancelled' => 'secondary',
                                    default => 'info',
                                };
                                $statusLabel = match($payment->status) {
                                    'pending_posting', 'received' => 'Pending Posting',
                                    'posted' => 'Posted',
                                    'rejected' => 'Rejected',
                                    'reversed' => 'Reversed',
                                    'cancelled' => 'Cancelled',
                                    default => ucfirst($payment->status),
                                };
                            @endphp
                            <tr class="{{ $payment->status === 'pending_posting' ? 'table-warning-subtle' : '' }}">
                                <td class="ps-3 font-monospace fw-bold text-primary">
                                    <span role="button" wire:click="openDetailModal({{ $payment->id }})" class="text-decoration-underline" title="View Full Payment & Accounting Impact">
                                        {{ $payment->payment_number ?: ('PAY-' . str_pad($payment->id, 5, '0', STR_PAD_LEFT)) }}
                                    </span>
                                </td>
                                <td>
                                    @if($payment->booking)
                                        <a href="{{ route('bookings.show', $payment->booking_id) }}" class="fw-bold font-monospace text-800">
                                            #{{ $payment->booking->booking_number }}
                                        </a>
                                        <div class="text-600 fs-11">{{ $payment->booking->customer->full_name ?? '—' }}</div>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>{{ $payment->booking->branch->name ?? 'Main' }}</td>
                                <td class="fw-semi-bold">{{ $payment->payment_date ? $payment->payment_date->format('M d, Y') : '—' }}</td>
                                <td>
                                    <span class="badge badge-subtle-primary">{{ $payment->payment_method }}</span>
                                    @if($payment->transaction_reference || $payment->cheque_number || $payment->bank_reference)
                                        <div class="font-monospace text-700 fs-10 mt-1">
                                            {{ $payment->transaction_reference ?: ($payment->cheque_number ? 'Chq: '.$payment->cheque_number : 'Ref: '.$payment->bank_reference) }}
                                        </div>
                                    @endif
                                </td>
                                <td class="text-end font-monospace fw-black text-{{ $payment->status === 'posted' ? 'success' : ($payment->status === 'pending_posting' ? 'warning-emphasis' : 'secondary') }} fs-11">
                                    Rs. {{ number_format($payment->amount, 2) }}
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-subtle-{{ $statusBadge }} fs-11">
                                        @if($payment->status === 'pending_posting')
                                            <span class="fas fa-clock me-1"></span>
                                        @elseif($payment->status === 'posted')
                                            <span class="fas fa-check-double me-1"></span>
                                        @elseif($payment->status === 'rejected')
                                            <span class="fas fa-times-circle me-1"></span>
                                        @elseif($payment->status === 'reversed')
                                            <span class="fas fa-undo me-1"></span>
                                        @endif
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semi-bold text-700">{{ $payment->recorder->name ?? 'System' }}</div>
                                    <div class="fs-10 text-500">{{ $payment->created_at ? $payment->created_at->format('M d, H:i') : '' }}</div>
                                </td>
                                <td>
                                    @if($payment->status === 'posted')
                                        <div class="fw-bold text-success fs-10">
                                            <span class="fas fa-university me-1"></span>{{ $payment->account->name ?? 'Cash in Hand' }}
                                        </div>
                                        <div class="fs-10 text-500">By: {{ $payment->poster->name ?? 'Accountant' }}</div>
                                    @elseif($payment->status === 'rejected')
                                        <div class="text-danger fs-10 fw-semi-bold">Rejected by {{ $payment->rejector->name ?? 'Accountant' }}</div>
                                        @if($payment->rejection_reason)
                                            <div class="text-muted fs-10 text-truncate" style="max-width: 150px;" title="{{ $payment->rejection_reason }}">
                                                "{{ $payment->rejection_reason }}"
                                            </div>
                                        @endif
                                    @elseif($payment->status === 'reversed')
                                        <div class="text-dark fs-10 fw-semi-bold">Reversed by {{ $payment->reverser->name ?? 'Accountant' }}</div>
                                    @else
                                        <span class="badge badge-subtle-secondary fs-10">Awaiting Post</span>
                                    @endif
                                </td>
                                <td class="text-center px-2">
                                    <div class="d-inline-flex gap-1 align-items-center">
                                        @if($payment->isPendingPosting() && (auth()->user()->isSuperAdmin() || auth()->user()->isBusinessOwner() || auth()->user()->hasPermission('post_payments')))
                                            <button wire:click="openPostModal({{ $payment->id }})" class="btn btn-falcon-success btn-xs" type="button" title="Post to Cash/Bank Account">
                                                <span class="fas fa-check-double me-1"></span>Post
                                            </button>
                                            <button wire:click="openRejectModal({{ $payment->id }})" class="btn btn-falcon-danger btn-xs" type="button" title="Reject Payment">
                                                <span class="fas fa-times"></span>
                                            </button>
                                        @endif

                                        <button wire:click="openDetailModal({{ $payment->id }})" class="btn btn-falcon-default btn-xs" type="button" title="View Details">
                                            <span class="fas fa-eye text-primary"></span>
                                        </button>

                                        <a href="{{ route('bookings.payment-receipt', $payment->id) }}" target="_blank" class="btn btn-falcon-default btn-xs" title="Print Receipt">
                                            <span class="fas fa-print text-secondary"></span>
                                        </a>

                                        @if($payment->status === 'posted' && (auth()->user()->isSuperAdmin() || auth()->user()->isBusinessOwner() || auth()->user()->hasPermission('reverse_payments')))
                                            <div class="dropdown d-inline-block">
                                                <button class="btn btn-link text-600 dropdown-toggle dropdown-caret-none btn-xs p-0 px-1" type="button" data-bs-toggle="dropdown">
                                                    <span class="fas fa-ellipsis-v"></span>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-end border py-1 fs-11">
                                                    @if($payment->journal_voucher_id)
                                                        <a class="dropdown-item text-info" href="{{ route('finance.general-ledger') }}">
                                                            <span class="fas fa-file-invoice me-1"></span>View Ledger
                                                        </a>
                                                    @endif
                                                    <button wire:click="openReverseModal({{ $payment->id }})" class="dropdown-item text-danger" type="button">
                                                        <span class="fas fa-undo-alt me-1"></span>Reverse Payment
                                                    </button>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-5 fs-12">
                                    <span class="fas fa-info-circle me-1"></span>No payment records match your search filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($payments->hasPages())
            <div class="card-footer py-2 bg-light">
                {{ $payments->links() }}
            </div>
        @endif
    </div>

    <!-- POST PAYMENT CONFIRMATION MODAL -->
    @if($showPostModal && $postingPayment)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.55);" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-success text-white py-2">
                    <h6 class="modal-title text-white fw-bold">
                        <span class="fas fa-check-double me-2"></span>Accountant Verification: Post Payment to Financial Account
                    </h6>
                    <button wire:click="$set('showPostModal', false)" type="button" class="btn-close btn-close-white" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Warning Notice -->
                    <div class="alert alert-warning py-2 fs-12 mb-3">
                        <span class="fas fa-exclamation-triangle me-1"></span>
                        <strong>Notice:</strong> Posting this payment will create a double-entry Journal Voucher (Debit selected Asset, Credit Customer Advance Liability) and update the physical Cash/Bank balance.
                    </div>

                    <!-- Payment Summary Box -->
                    <div class="card bg-light border-0 mb-3">
                        <div class="card-body py-2">
                            <div class="row g-2 fs-11">
                                <div class="col-sm-4">
                                    <span class="text-500">Payment #:</span>
                                    <div class="fw-bold font-monospace text-primary">{{ $postingPayment->payment_number ?: ('PAY-'.$postingPayment->id) }}</div>
                                </div>
                                <div class="col-sm-4">
                                    <span class="text-500">Booking #:</span>
                                    <div class="fw-bold font-monospace">#{{ $postingPayment->booking->booking_number }}</div>
                                </div>
                                <div class="col-sm-4">
                                    <span class="text-500">Customer:</span>
                                    <div class="fw-bold">{{ $postingPayment->booking->customer->full_name ?? '—' }}</div>
                                </div>
                                <div class="col-sm-4">
                                    <span class="text-500">Amount to Post:</span>
                                    <div class="fw-bolder font-monospace text-success fs-13">Rs. {{ number_format($postingPayment->amount, 2) }}</div>
                                </div>
                                <div class="col-sm-4">
                                    <span class="text-500">Recorded Method:</span>
                                    <div class="fw-bold"><span class="badge badge-subtle-primary">{{ $postingPayment->payment_method }}</span></div>
                                </div>
                                <div class="col-sm-4">
                                    <span class="text-500">Recorded By:</span>
                                    <div class="fw-bold">{{ $postingPayment->recorder->name ?? 'Booking Staff' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Accounting Form -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fs-11 fw-bold text-800">Target Posting Account (Cash / Bank) <span class="text-danger">*</span></label>
                            <select wire:model="targetAccountId" class="form-select form-select-sm">
                                <option value="">-- Select Cash in Hand or Bank Account --</option>
                                @foreach($accounts as $acc)
                                    <option value="{{ $acc->id }}">
                                        {{ $acc->account_code }} - {{ $acc->name }} ({{ $acc->nature }})
                                    </option>
                                @endforeach
                            </select>
                            @error('targetAccountId') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fs-11 fw-bold text-800">Posting Date <span class="text-danger">*</span></label>
                            <input wire:model="postingDate" type="date" class="form-control form-control-sm" />
                            @error('postingDate') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fs-11 fw-bold text-800">Accountant Verification Notes</label>
                            <textarea wire:model="accountantNotes" rows="2" class="form-control form-control-sm" placeholder="e.g. Cash counted and placed in locker; Bank deposit slip verified..."></textarea>
                            @error('accountantNotes') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light">
                    <button wire:click="$set('showPostModal', false)" type="button" class="btn btn-secondary btn-sm">Cancel</button>
                    <button wire:click="confirmPostPayment" type="button" class="btn btn-success btn-sm">
                        <span class="fas fa-check-circle me-1"></span>Post to Financial Accounts
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- REJECT PAYMENT MODAL -->
    @if($showRejectModal && $rejectingPayment)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.55);" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-danger text-white py-2">
                    <h6 class="modal-title text-white fw-bold">
                        <span class="fas fa-times-circle me-2"></span>Reject Payment Entry
                    </h6>
                    <button wire:click="$set('showRejectModal', false)" type="button" class="btn-close btn-close-white" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <p class="fs-12 text-700 mb-2">
                        Are you sure you want to reject payment <strong>{{ $rejectingPayment->payment_number ?: ('PAY-'.$rejectingPayment->id) }}</strong> of <strong>Rs. {{ number_format($rejectingPayment->amount, 2) }}</strong>?
                    </p>
                    <div class="mb-3">
                        <label class="form-label fs-11 fw-bold text-800">Reason for Rejection <span class="text-danger">*</span></label>
                        <textarea wire:model="rejectionReason" class="form-control form-control-sm" rows="3" placeholder="e.g. Cheque bounced; Fake bank transfer receipt; Cash not physically received..."></textarea>
                        @error('rejectionReason') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light">
                    <button wire:click="$set('showRejectModal', false)" type="button" class="btn btn-secondary btn-sm">Cancel</button>
                    <button wire:click="confirmRejectPayment" type="button" class="btn btn-danger btn-sm">
                        <span class="fas fa-times-circle me-1"></span>Confirm Rejection
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- REVERSE PAYMENT MODAL -->
    @if($showReverseModal && $reversingPayment)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.55);" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white py-2">
                    <h6 class="modal-title text-white fw-bold">
                        <span class="fas fa-undo-alt me-2"></span>Reverse Posted Payment
                    </h6>
                    <button wire:click="$set('showReverseModal', false)" type="button" class="btn-close btn-close-white" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="alert alert-danger py-2 fs-12 mb-3">
                        <span class="fas fa-exclamation-triangle me-1"></span>
                        <strong>Warning:</strong> Reversing this payment will generate an offsetting Journal Voucher and debit the customer ledger / advance liability.
                    </div>
                    <p class="fs-12 text-700 mb-2">
                        Reversing Payment <strong>{{ $reversingPayment->payment_number ?: ('PAY-'.$reversingPayment->id) }}</strong> (Rs. {{ number_format($reversingPayment->amount, 2) }}).
                    </p>
                    <div class="mb-3">
                        <label class="form-label fs-11 fw-bold text-800">Reversal Reason <span class="text-danger">*</span></label>
                        <textarea wire:model="reversalReason" class="form-control form-control-sm" rows="3" placeholder="State reasons for audit compliance..."></textarea>
                        @error('reversalReason') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light">
                    <button wire:click="$set('showReverseModal', false)" type="button" class="btn btn-secondary btn-sm">Cancel</button>
                    <button wire:click="confirmReversePayment" type="button" class="btn btn-dark btn-sm">
                        <span class="fas fa-undo-alt me-1"></span>Confirm Reversal
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- PAYMENT DETAIL & AUDIT MODAL -->
    @if($showDetailModal && $viewingPayment)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.55);" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-light py-2">
                    <h6 class="modal-title fw-bold text-primary">
                        <span class="fas fa-info-circle me-2"></span>Payment Details & Accounting Audit: {{ $viewingPayment->payment_number ?: ('PAY-'.$viewingPayment->id) }}
                    </h6>
                    <button wire:click="$set('showDetailModal', false)" type="button" class="btn-close" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 fs-11">
                    <div class="row g-3">
                        <!-- Left: Payment Info -->
                        <div class="col-md-6">
                            <div class="card h-100 border">
                                <div class="card-header bg-light py-2 fw-bold text-800">Payment Information</div>
                                <div class="card-body py-2">
                                    <table class="table table-sm table-borderless mb-0">
                                        <tr>
                                            <td class="text-500" style="width: 40%;">Payment #:</td>
                                            <td class="fw-bold font-monospace text-primary">{{ $viewingPayment->payment_number ?: ('PAY-'.$viewingPayment->id) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-500">Booking #:</td>
                                            <td class="fw-bold font-monospace">#{{ $viewingPayment->booking->booking_number ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-500">Customer:</td>
                                            <td class="fw-bold">{{ $viewingPayment->booking->customer->full_name ?? '—' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-500">Amount Paid:</td>
                                            <td class="fw-black font-monospace text-success fs-12">Rs. {{ number_format($viewingPayment->amount, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-500">Method:</td>
                                            <td><span class="badge badge-subtle-primary">{{ $viewingPayment->payment_method }}</span></td>
                                        </tr>
                                        <tr>
                                            <td class="text-500">Payment Date:</td>
                                            <td class="fw-bold">{{ $viewingPayment->payment_date ? $viewingPayment->payment_date->format('M d, Y') : '—' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-500">Reference / Cheque:</td>
                                            <td class="font-monospace text-700">{{ $viewingPayment->transaction_reference ?: ($viewingPayment->cheque_number ?: ($viewingPayment->bank_reference ?: '—')) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-500">Received By:</td>
                                            <td class="fw-bold">{{ $viewingPayment->recorder->name ?? 'Staff' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-500">Notes:</td>
                                            <td class="text-muted">{{ $viewingPayment->notes ?: '—' }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Posting & Accounting Info -->
                        <div class="col-md-6">
                            <div class="card h-100 border">
                                <div class="card-header bg-light py-2 fw-bold text-800">Posting & Accounting Status</div>
                                <div class="card-body py-2">
                                    <table class="table table-sm table-borderless mb-0">
                                        <tr>
                                            <td class="text-500" style="width: 40%;">Status:</td>
                                            <td>
                                                <span class="badge badge-subtle-{{ $viewingPayment->status === 'posted' ? 'success' : ($viewingPayment->status === 'pending_posting' ? 'warning' : 'danger') }} fs-11">
                                                    {{ strtoupper(str_replace('_', ' ', $viewingPayment->status)) }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-500">Posted By:</td>
                                            <td class="fw-bold">{{ $viewingPayment->poster->name ?? '—' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-500">Posted At:</td>
                                            <td>{{ $viewingPayment->posted_at ? $viewingPayment->posted_at->format('M d, Y H:i') : '—' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-500">Target Account:</td>
                                            <td class="fw-bold text-primary">{{ $viewingPayment->account->name ?? '—' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-500">Journal Voucher #:</td>
                                            <td class="font-monospace fw-bold text-info">{{ $viewingPayment->journalVoucher->voucher_no ?? '—' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-500">Accountant Notes:</td>
                                            <td class="text-muted">{{ $viewingPayment->accountantNotes ?: '—' }}</td>
                                        </tr>
                                        @if($viewingPayment->rejection_reason)
                                            <tr>
                                                <td class="text-danger">Rejection Reason:</td>
                                                <td class="text-danger fw-bold">{{ $viewingPayment->rejection_reason }}</td>
                                            </tr>
                                        @endif
                                        @if($viewingPayment->reversal_reason)
                                            <tr>
                                                <td class="text-dark">Reversal Reason:</td>
                                                <td class="text-dark fw-bold">{{ $viewingPayment->reversal_reason }}</td>
                                            </tr>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Journal Voucher Lines (Double-Entry Impact) -->
                        @if($viewingPayment->journalVoucher && $viewingPayment->journalVoucher->items->isNotEmpty())
                        <div class="col-12">
                            <div class="card border">
                                <div class="card-header bg-light py-2 fw-bold text-800 d-flex justify-content-between">
                                    <span>Double-Entry Accounting Impact (Voucher: {{ $viewingPayment->journalVoucher->voucher_no }})</span>
                                    <span class="badge badge-subtle-success">Balanced Entry</span>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-sm table-striped mb-0 fs-11">
                                        <thead>
                                            <tr>
                                                <th class="ps-3">Account Code & Name</th>
                                                <th>Narration</th>
                                                <th class="text-end">Debit (Rs.)</th>
                                                <th class="text-end pe-3">Credit (Rs.)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($viewingPayment->journalVoucher->items as $item)
                                                <tr>
                                                    <td class="ps-3 fw-bold">{{ $item->account->account_code ?? '' }} - {{ $item->account->name ?? '' }}</td>
                                                    <td class="text-600">{{ $item->narration }}</td>
                                                    <td class="text-end font-monospace text-success fw-bold">{{ $item->debit > 0 ? number_format($item->debit, 2) : '—' }}</td>
                                                    <td class="text-end pe-3 font-monospace text-primary fw-bold">{{ $item->credit > 0 ? number_format($item->credit, 2) : '—' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light">
                    <button wire:click="$set('showDetailModal', false)" type="button" class="btn btn-secondary btn-sm">Close</button>
                    @if($viewingPayment->isPendingPosting() && (auth()->user()->isSuperAdmin() || auth()->user()->isBusinessOwner() || auth()->user()->hasPermission('post_payments')))
                        <button wire:click="openPostModal({{ $viewingPayment->id }})" type="button" class="btn btn-success btn-sm">
                            <span class="fas fa-check-double me-1"></span>Post This Payment
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
