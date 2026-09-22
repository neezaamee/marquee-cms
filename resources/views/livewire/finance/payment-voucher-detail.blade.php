<div class="row g-3">
    <!-- Left Column: Details Pane -->
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                <div>
                    <span class="badge {{ $voucher->voucher_type === 'CPV' ? 'bg-success-subtle text-success' : 'bg-primary-subtle text-primary' }} me-2 font-mono fs-12">
                        {{ $voucher->voucher_type }}
                    </span>
                    <h5 class="d-inline mb-0 text-primary">{{ $voucher->voucher_type_full }}: {{ $voucher->voucher_no }}</h5>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('finance.payment-vouchers.print', $voucher->id) }}" target="_blank" class="btn btn-falcon-default btn-sm">
                        <span class="fas fa-print me-1"></span>Print Voucher Slip
                    </a>
                    @if($voucher->status !== 'posted')
                        <a href="{{ route('finance.payment-vouchers.edit', $voucher->id) }}" class="btn btn-falcon-primary btn-sm">
                            <span class="fas fa-edit me-1"></span>Edit
                        </a>
                    @endif
                    <a href="{{ route('finance.payment-vouchers.index') }}" class="btn btn-falcon-default btn-sm">
                        <span class="fas fa-arrow-left me-1"></span>Back
                    </a>
                </div>
            </div>

            <div class="card-body">
                @if(session()->has('success'))
                    <div class="alert alert-success alert-dismissible fade show fs-12 py-2 mb-3" role="alert">
                        <span class="fas fa-check-circle me-1"></span>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if(session()->has('error'))
                    <div class="alert alert-danger alert-dismissible fade show fs-12 py-2 mb-3" role="alert">
                        <span class="fas fa-times-circle me-1"></span>{{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <!-- Meta Details Grid -->
                <div class="row g-3 border-bottom pb-3 mb-3">
                    <div class="col-md-4">
                        <span class="text-muted fs-11 d-block">Voucher Date</span>
                        <span class="fw-bold">{{ $voucher->voucher_date->format('d M, Y') }}</span>
                    </div>
                    <div class="col-md-4">
                        <span class="text-muted fs-11 d-block">Branch Location</span>
                        <span class="fw-bold">{{ $voucher->branch->name ?? 'Head Office' }}</span>
                    </div>
                    <div class="col-md-4">
                        <span class="text-muted fs-11 d-block">Payment Instrument</span>
                        <span class="fw-bold">{{ $voucher->payment_method }}</span>
                        @if($voucher->cheque_no)
                            <div class="fs-10 text-muted font-mono">Cheque #: {{ $voucher->cheque_no }}</div>
                        @endif
                    </div>

                    <div class="col-md-4 mt-3">
                        <span class="text-muted fs-11 d-block">Payee / Beneficiary</span>
                        <span class="fw-bold text-dark fs-12">{{ $voucher->payee_name }}</span>
                        <span class="badge badge-subtle-secondary ms-1" style="font-size: 9px;">{{ ucfirst($voucher->payee_type) }}</span>
                    </div>
                    <div class="col-md-4 mt-3">
                        <span class="text-muted fs-11 d-block">Payee CNIC</span>
                        <span class="fw-bold font-mono">{{ $voucher->payee_cnic ?: '—' }}</span>
                    </div>
                    <div class="col-md-4 mt-3">
                        <span class="text-muted fs-11 d-block">Contact Phone</span>
                        <span class="fw-bold font-mono">{{ $voucher->payee_phone ?: '—' }}</span>
                    </div>

                    @if($voucher->reference_no)
                        <div class="col-md-4 mt-3">
                            <span class="text-muted fs-11 d-block">External Document Ref #</span>
                            <span class="fw-bold font-mono text-primary">{{ $voucher->reference_no }}</span>
                        </div>
                    @endif

                    <div class="col-md-4 mt-3">
                        <span class="text-muted fs-11 d-block">Disbursing Account</span>
                        <span class="fw-bold">{{ $voucher->cashBankAccount->account->name ?? 'Cash/Bank' }}</span>
                    </div>

                    <div class="col-md-4 mt-3">
                        <span class="text-muted fs-11 d-block">Debit Account Head</span>
                        <span class="fw-bold font-mono text-secondary">[{{ $voucher->debitAccount->account_code }}]</span>
                        <span>{{ $voucher->debitAccount->name }}</span>
                    </div>
                </div>

                <!-- Financial Overview Card -->
                <div class="p-3 bg-light rounded border mb-4">
                    <div class="row align-items-center">
                        <div class="col-md-7">
                            <span class="text-muted fs-11 d-block text-uppercase fw-bold">Amount in Words</span>
                            <div class="fw-bold font-italic text-dark fs-12 mt-1">
                                {{ $voucher->amount_in_words }}
                            </div>
                        </div>
                        <div class="col-md-5 text-md-end mt-3 mt-md-0">
                            <span class="text-muted fs-11 d-block text-uppercase fw-bold">Total Voucher Amount</span>
                            <div class="fs-20 fw-bold font-monospace text-primary">
                                Rs. {{ number_format($voucher->amount, 2) }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Description / Particulars -->
                <div class="mb-4">
                    <h6 class="fs-12 text-uppercase text-secondary fw-bold border-bottom pb-2">
                        <span class="fas fa-align-left me-2 text-primary"></span>Particulars & Purpose
                    </h6>
                    <div class="p-3 bg-white border rounded text-dark fs-12">
                        {{ $voucher->description ?: 'No additional particulars specified.' }}
                    </div>
                </div>

                <!-- Linked General Ledger Journal Voucher (If Posted) -->
                @if($voucher->journalVoucher)
                    <div class="card bg-light border">
                        <div class="card-header bg-200 py-2 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fs-12 text-success">
                                <span class="fas fa-check-circle me-2"></span>Posted General Ledger Journal Voucher: {{ $voucher->journalVoucher->voucher_no }}
                            </h6>
                            <span class="badge bg-success-subtle text-success fs-10">Posted</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered fs-11 mb-0">
                                    <thead class="bg-300">
                                        <tr>
                                            <th>Account Head</th>
                                            <th>Narration</th>
                                            <th class="text-end" style="width: 120px;">Debit (PKR)</th>
                                            <th class="text-end" style="width: 120px;">Credit (PKR)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($voucher->journalVoucher->items as $item)
                                            <tr>
                                                <td>
                                                    <span class="font-mono text-muted me-1">[{{ $item->account->account_code }}]</span>
                                                    <strong>{{ $item->account->name }}</strong>
                                                </td>
                                                <td class="text-muted">{{ $item->narration }}</td>
                                                <td class="text-end font-mono {{ $item->debit > 0 ? 'fw-bold text-dark' : 'text-muted' }}">
                                                    {{ $item->debit > 0 ? number_format($item->debit, 2) : '—' }}
                                                </td>
                                                <td class="text-end font-mono {{ $item->credit > 0 ? 'fw-bold text-dark' : 'text-muted' }}">
                                                    {{ $item->credit > 0 ? number_format($item->credit, 2) : '—' }}
                                                </td>
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
    </div>

    <!-- Right Column: Status & Action Panel -->
    <div class="col-lg-4">
        <!-- Status Widget -->
        <div class="card mb-3">
            <div class="card-header bg-light py-2">
                <h6 class="mb-0 fs-12"><span class="fas fa-info-circle me-2 text-primary"></span>Workflow Status</h6>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="text-muted fs-12">Posting State</span>
                    <span class="badge {{ $voucher->status_badge_class }} rounded-pill px-3 py-1 fs-11">
                        {{ $voucher->status_label }}
                    </span>
                </div>

                @if($voucher->status === 'draft')
                    <div class="alert alert-subtle-secondary fs-11 py-2 mb-3">
                        This voucher is currently in <strong>Draft</strong> mode. Approve it so it can be printed for acknowledgment.
                    </div>
                    <button wire:click="approveVoucher" class="btn btn-sm btn-primary w-100 mb-2">
                        <span class="fas fa-check-circle me-1"></span>Approve for Disbursement
                    </button>
                @elseif($voucher->status === 'approved')
                    <div class="alert alert-subtle-warning fs-11 py-2 mb-3">
                        <span class="fas fa-clock me-1"></span><strong>Awaiting Physical Payment:</strong> The pre-payment voucher can be printed and signed by the payee. Once funds are disbursed, click below to post into the General Ledger.
                    </div>

                    <a href="{{ route('finance.payment-vouchers.print', $voucher->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary w-100 mb-2">
                        <span class="fas fa-print me-1"></span>Print Voucher Slip
                    </a>

                    <button wire:click="openDisburseModal" class="btn btn-sm btn-success w-100 py-2 fw-bold">
                        <span class="fas fa-hand-holding-usd me-1"></span>Confirm Payment & Post to GL
                    </button>
                @elseif($voucher->status === 'posted')
                    <div class="alert alert-subtle-success fs-11 py-2 mb-0">
                        <span class="fas fa-check-double me-1"></span><strong>Transaction Posted:</strong> Cash/bank account was credited and the expense/accounts payable was debited in GL.
                    </div>
                @elseif($voucher->status === 'cancelled')
                    <div class="alert alert-subtle-danger fs-11 py-2 mb-0">
                        <span class="fas fa-ban me-1"></span>This voucher was cancelled. No accounting records were modified.
                    </div>
                @endif
            </div>
        </div>

        <!-- Audit & Signatory Logs -->
        <div class="card">
            <div class="card-header bg-light py-2">
                <h6 class="mb-0 fs-12"><span class="fas fa-history me-2 text-primary"></span>Audit & Signatories</h6>
            </div>
            <div class="card-body p-3">
                <ul class="list-unstyled mb-0 fs-11">
                    <li class="mb-3 pb-2 border-bottom">
                        <span class="text-muted d-block fs-10">1. PREPARED BY</span>
                        <strong class="text-dark">{{ $voucher->preparedBy->name ?? 'System' }}</strong>
                        <span class="text-muted d-block fs-10">{{ $voucher->created_at->format('d M, Y h:i A') }}</span>
                    </li>

                    <li class="mb-3 pb-2 border-bottom">
                        <span class="text-muted d-block fs-10">2. APPROVED BY</span>
                        @if($voucher->approvedBy)
                            <strong class="text-success">{{ $voucher->approvedBy->name }}</strong>
                            <span class="text-muted d-block fs-10">Approved for disbursement</span>
                        @else
                            <span class="text-muted">Pending approval</span>
                        @endif
                    </li>

                    <li class="mb-3 pb-2 border-bottom">
                        <span class="text-muted d-block fs-10">3. DISBURSED & POSTED BY</span>
                        @if($voucher->disbursedBy)
                            <strong class="text-primary">{{ $voucher->disbursedBy->name }}</strong>
                            <span class="text-muted d-block fs-10">{{ $voucher->disbursed_at ? $voucher->disbursed_at->format('d M, Y h:i A') : '' }}</span>
                        @else
                            <span class="text-muted">Awaiting fund disbursement</span>
                        @endif
                    </li>

                    @if($voucher->receiver_signature_notes)
                        <li>
                            <span class="text-muted d-block fs-10">RECEIVER ACKNOWLEDGMENT</span>
                            <div class="p-2 bg-light rounded text-dark fs-11 mt-1">
                                "{{ $voucher->receiver_signature_notes }}"
                            </div>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>

    <!-- Disburse & Post Modal -->
    @if($showDisburseModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0, 0, 0, 0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <form wire:submit.prevent="disburseAndPost">
                        <div class="modal-header bg-success text-white">
                            <h6 class="modal-title text-white">
                                <span class="fas fa-hand-holding-usd me-2"></span>Confirm Payment Disbursement & GL Posting
                            </h6>
                            <button type="button" wire:click="$set('showDisburseModal', false)" class="btn-close btn-close-white" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="alert alert-subtle-success mb-3 fs-11">
                                Confirming this disbursement will credit <strong>{{ $voucher->cashBankAccount->account->name }}</strong> for <strong>Rs. {{ number_format($voucher->amount, 2) }}</strong> and record this transaction permanently into the General Ledger.
                            </div>

                            <div class="mb-3">
                                <label class="form-label fs-11 fw-bold">Actual Payment / Disbursement Date</label>
                                <input type="date" wire:model="disbursedDate" class="form-control form-control-sm" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fs-11 fw-bold">Acknowledgment Notes / Receiver Stamp Ref</label>
                                <textarea wire:model="disbursementNotes" class="form-control form-control-sm" rows="2" placeholder="e.g. Paid in cash at front counter. Payee signed the physical voucher copy."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer bg-light py-2">
                            <button type="button" wire:click="$set('showDisburseModal', false)" class="btn btn-falcon-default btn-sm">Cancel</button>
                            <button type="submit" class="btn btn-success btn-sm">
                                <span class="fas fa-check-circle me-1"></span>Post to General Ledger
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
