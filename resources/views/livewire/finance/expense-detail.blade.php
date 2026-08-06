<div class="row g-3">
    <!-- Left Column: Details Pane -->
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><span class="fas fa-file-invoice-dollar me-2 text-primary"></span>Voucher Sheets: {{ $expense->expense_number }}</h5>
                <div>
                    @if($expense->status === 'Draft' || $expense->status === 'Rejected')
                        <a href="{{ route('expenses.edit', $expense->id) }}" class="btn btn-falcon-primary btn-sm me-2">
                            <span class="fas fa-edit me-1"></span>Edit Voucher
                        </a>
                    @endif
                    <a href="{{ route('expenses.index') }}" class="btn btn-falcon-default btn-sm">
                        <span class="fas fa-arrow-left me-1"></span>Back
                    </a>
                </div>
            </div>
            
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success border-2 d-flex align-items-center mb-3" role="alert">
                        <div class="bg-success me-3 icon-item"><span class="fas fa-check-circle text-white fs-8"></span></div>
                        <p class="mb-0 flex-grow-1 text-success-800">{{ session('success') }}</p>
                        <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger border-2 d-flex align-items-center mb-3" role="alert">
                        <div class="bg-danger me-3 icon-item"><span class="fas fa-times-circle text-white fs-8"></span></div>
                        <p class="mb-0 flex-grow-1 text-danger-800">{{ session('error') }}</p>
                        <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <!-- Meta Details Grid -->
                <div class="row g-3 border-bottom pb-3">
                    <div class="col-md-4">
                        <span class="text-muted fs-11 d-block">Expense Date</span>
                        <span class="fw-bold">{{ $expense->expense_date->format('Y-m-d') }}</span>
                    </div>
                    <div class="col-md-4">
                        <span class="text-muted fs-11 d-block">Branch Location</span>
                        <span class="fw-bold">{{ $expense->branch->name ?? '—' }}</span>
                    </div>
                    <div class="col-md-4">
                        <span class="text-muted fs-11 d-block">Department & Cost Center</span>
                        <span class="fw-bold">{{ $expense->department ?? '—' }} @if($expense->cost_center) [{{ $expense->cost_center }}] @endif</span>
                    </div>

                    <div class="col-md-4 mt-3">
                        <span class="text-muted fs-11 d-block">Payment Method</span>
                        <span class="fw-bold">{{ $expense->payment_method }}</span>
                        @if($expense->payment_method === 'Bank' && $expense->cashBankAccount)
                            <div class="fs-11 text-muted">{{ $expense->cashBankAccount->account_name }}</div>
                        @elseif($expense->payment_method === 'Petty Cash' && $expense->pettyCashAccount)
                            <div class="fs-11 text-muted">{{ $expense->pettyCashAccount->account_name }}</div>
                        @endif
                    </div>
                    <div class="col-md-4 mt-3">
                        <span class="text-muted fs-11 d-block">Vendor / Supplier</span>
                        <span class="fw-bold">{{ $expense->supplier->name ?? '—' }}</span>
                    </div>
                    <div class="col-md-4 mt-3">
                        <span class="text-muted fs-11 d-block">Employee Scoping</span>
                        <span class="fw-bold">{{ $expense->employee->name ?? '—' }}</span>
                    </div>
                    
                    @if($expense->booking)
                        <div class="col-md-4 mt-3">
                            <span class="text-muted fs-11 d-block">Related Booking</span>
                            <span class="fw-bold text-primary">{{ $expense->booking->booking_number }}</span>
                        </div>
                    @endif
                    @if($expense->journalVoucher)
                        <div class="col-md-4 mt-3">
                            <span class="text-muted fs-11 d-block">GL Journal Voucher</span>
                            <span class="fw-bold text-success">{{ $expense->journalVoucher->voucher_no }}</span>
                        </div>
                    @endif
                    <div class="col-md-4 mt-3">
                        <span class="text-muted fs-11 d-block">Ref Number</span>
                        <span class="fw-bold">{{ $expense->reference_number ?? '—' }}</span>
                    </div>
                </div>

                <!-- Utilities Info Box -->
                @if($expense->utilityBill)
                    <div class="card bg-light border my-3">
                        <div class="card-header bg-200 py-2">
                            <h6 class="mb-0 text-800"><span class="fas fa-plug me-2"></span>Utility Bill Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <span class="text-muted fs-11 d-block">Service Type</span>
                                    <span class="fw-semi-bold">{{ $expense->utilityBill->utility_type }}</span>
                                </div>
                                <div class="col-md-4">
                                    <span class="text-muted fs-11 d-block">Consumer Number</span>
                                    <span class="fw-semi-bold font-monospace">{{ $expense->utilityBill->consumer_number }}</span>
                                </div>
                                <div class="col-md-4">
                                    <span class="text-muted fs-11 d-block">Billing Period</span>
                                    <span class="fw-semi-bold">{{ $expense->utilityBill->billing_period }}</span>
                                </div>
                                <div class="col-md-4">
                                    <span class="text-muted fs-11 d-block">Previous Reading</span>
                                    <span class="fw-semi-bold">{{ $expense->utilityBill->previous_reading ?? '—' }} Units</span>
                                </div>
                                <div class="col-md-4">
                                    <span class="text-muted fs-11 d-block">Current Reading</span>
                                    <span class="fw-semi-bold">{{ $expense->utilityBill->current_reading ?? '—' }} Units</span>
                                </div>
                                <div class="col-md-4">
                                    <span class="text-muted fs-11 d-block">Late Charges</span>
                                    <span class="fw-semi-bold">{{ number_format($expense->utilityBill->late_charges, 2) }} PKR</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Maintenance Info Box -->
                @if($expense->maintenanceRecord)
                    <div class="card bg-light border my-3">
                        <div class="card-header bg-200 py-2">
                            <h6 class="mb-0 text-800"><span class="fas fa-tools me-2"></span>Maintenance Logs</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <span class="text-muted fs-11 d-block">Maintenance Category</span>
                                    <span class="fw-semi-bold">{{ $expense->maintenanceRecord->maintenance_type }}</span>
                                </div>
                                <div class="col-md-4">
                                    <span class="text-muted fs-11 d-block">Asset Name</span>
                                    <span class="fw-semi-bold">{{ $expense->maintenanceRecord->asset_name }}</span>
                                </div>
                                <div class="col-md-4">
                                    <span class="text-muted fs-11 d-block">Warranty Coverage</span>
                                    <span class="fw-semi-bold">{{ $expense->maintenanceRecord->warranty_period_months }} Months</span>
                                </div>
                                <div class="col-md-6">
                                    <span class="text-muted fs-11 d-block">Scheduled Date</span>
                                    <span class="fw-semi-bold">{{ $expense->maintenanceRecord->scheduled_date->format('Y-m-d') }}</span>
                                </div>
                                <div class="col-md-6">
                                    <span class="text-muted fs-11 d-block">Completion Date</span>
                                    <span class="fw-semi-bold">{{ $expense->maintenanceRecord->completion_date ? $expense->maintenanceRecord->completion_date->format('Y-m-d') : 'In Progress' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Itemization table -->
                <div class="my-4">
                    <h6 class="border-bottom pb-2"><span class="fas fa-align-justify me-2"></span>Itemized Entries</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered fs-11 align-middle">
                            <thead class="bg-200">
                                <tr>
                                    <th>GL Category</th>
                                    <th>Description</th>
                                    <th class="text-end" style="width: 15%;">Amount</th>
                                    <th class="text-end" style="width: 15%;">Tax</th>
                                    <th class="text-end" style="width: 15%;">Discount</th>
                                    <th class="text-end" style="width: 15%;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if($expense->is_multiline)
                                    @foreach($expense->items as $itm)
                                        <tr>
                                            <td class="fw-semi-bold">{{ $itm->category->name }}</td>
                                            <td class="text-muted">{{ $itm->description ?? '—' }}</td>
                                            <td class="text-end">{{ number_format($itm->amount, 2) }}</td>
                                            <td class="text-end">{{ number_format($itm->tax_amount, 2) }}</td>
                                            <td class="text-end">{{ number_format($itm->discount_amount, 2) }}</td>
                                            <td class="text-end fw-bold font-monospace">{{ number_format($itm->total_amount, 2) }} PKR</td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td class="fw-semi-bold">{{ $expense->category->name ?? '—' }}</td>
                                        <td class="text-muted">{{ $expense->description ?? '—' }}</td>
                                        <td class="text-end">{{ number_format($expense->amount, 2) }}</td>
                                        <td class="text-end">{{ number_format($expense->tax_amount, 2) }}</td>
                                        <td class="text-end">{{ number_format($expense->discount_amount, 2) }}</td>
                                        <td class="text-end fw-bold font-monospace">{{ number_format($expense->total_amount, 2) }} PKR</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Currency conversion totals -->
                <div class="row g-3 justify-content-end text-end border-top pt-3">
                    <div class="col-md-4">
                        <span class="text-muted fs-11 d-block">Subtotal</span>
                        <span class="fw-semi-bold font-monospace">{{ number_format($expense->amount, 2) }} PKR</span>
                    </div>
                    <div class="col-md-4">
                        <span class="text-muted fs-11 d-block">Tax Amount / Late Charges</span>
                        @php
                            $utilityLate = $expense->utilityBill ? $expense->utilityBill->late_charges : 0;
                            $t = $expense->tax_amount + $utilityLate;
                        @endphp
                        <span class="fw-semi-bold font-monospace">+ {{ number_format($t, 2) }} PKR</span>
                    </div>
                    <div class="col-md-4">
                        <span class="text-muted fs-11 d-block">Discount</span>
                        <span class="fw-semi-bold font-monospace text-danger">- {{ number_format($expense->discount_amount, 2) }} PKR</span>
                    </div>
                    <div class="col-12 mt-2">
                        <span class="text-muted fs-11 d-block">Total Voucher Cost</span>
                        <h3 class="fw-bold font-monospace text-primary mb-0">
                            {{ number_format($expense->total_amount, 2) }} {{ $expense->currency->code }}
                        </h3>
                        @if($expense->currency->code !== 'PKR')
                            <div class="text-muted fs-11 font-monospace mt-1">
                                (Converted to Base Currency: {{ number_format($expense->total_amount_base, 2) }} PKR at rate {{ number_format($expense->exchange_rate, 4) }})
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Attachments list -->
                @if(count($expense->attachments) > 0)
                    <div class="mt-4 border-top pt-3">
                        <h6><span class="fas fa-paperclip me-2 text-primary"></span>Attached Receipts ({{ count($expense->attachments) }})</h6>
                        <div class="row g-3">
                            @foreach($expense->attachments as $att)
                                <div class="col-md-4">
                                    <div class="border rounded p-2 bg-light d-flex justify-content-between align-items-center">
                                        <div class="text-truncate" style="max-width: 80%;">
                                            <span class="fas fa-file me-2 text-secondary"></span>
                                            <a href="{{ Storage::url($att->file_path) }}" target="_blank" class="fs-11 fw-semi-bold">{{ $att->file_name }}</a>
                                        </div>
                                        <div class="text-muted fs-11">{{ number_format($att->file_size / 1024, 0) }} KB</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Right Column: Status & Timeline Panel -->
    <div class="col-lg-4">
        <!-- Status widget -->
        <div class="card mb-3">
            <div class="card-header bg-light">
                <h6 class="mb-0"><span class="fas fa-info-circle me-2 text-primary"></span>Voucher Status</h6>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="text-muted font-sans-serif">Current State</span>
                    @php
                        $statusColors = [
                            'Draft' => 'secondary',
                            'Submitted' => 'info',
                            'Pending Approval' => 'warning',
                            'Approved' => 'primary',
                            'Paid' => 'success',
                            'Posted' => 'success',
                            'Rejected' => 'danger',
                            'Closed' => 'secondary',
                        ];
                        $sc = $statusColors[$expense->status] ?? 'secondary';
                    @endphp
                    <span class="badge badge-subtle-{{ $sc }} rounded-pill px-3 py-1">{{ $expense->status }}</span>
                </div>
                
                @if($canApprove)
                    <div class="d-grid gap-2 mt-4">
                        <button wire:click="initiateApprove" class="btn btn-success btn-sm w-100" type="button">
                            <span class="fas fa-check-double me-1"></span>Approve Voucher
                        </button>
                        <button wire:click="initiateReject" class="btn btn-danger btn-sm w-100" type="button">
                            <span class="fas fa-times me-1"></span>Reject Voucher
                        </button>
                    </div>
                @else
                    <div class="alert alert-secondary py-2 mb-0 fs-11 text-center" role="alert">
                        No pending workflow actions required from you at this stage.
                    </div>
                @endif
            </div>
        </div>

        <!-- Approval Timeline list -->
        <div class="card">
            <div class="card-header bg-light">
                <h6 class="mb-0"><span class="fas fa-history me-2 text-primary"></span>Approval Workflow Logs</h6>
            </div>
            <div class="card-body p-0">
                <div class="p-3">
                    <ul class="list-unstyled mb-0 ps-3 border-start border-2 border-300">
                        @forelse($expense->approvals as $appr)
                            <li class="mb-3 position-relative">
                                <span class="position-absolute start-0 translate-middle rounded-circle bg-{{ $appr->action === 'Approved' ? 'success' : 'danger' }} d-inline-block" style="width: 10px; height: 10px; left: -19px !important; top: 8px;"></span>
                                <div class="fw-semi-bold fs-11">{{ $appr->user->name }} ({{ $appr->role->label }})</div>
                                <div class="text-muted fs-11">{{ $appr->created_at->format('Y-m-d H:i') }}</div>
                                <span class="badge badge-subtle-{{ $appr->action === 'Approved' ? 'success' : 'danger' }} rounded-pill" style="font-size: 8px;">{{ $appr->action }}</span>
                                @if($appr->comments)
                                    <div class="p-2 border rounded bg-light fs-11 mt-1 text-800">
                                        "{{ $appr->comments }}"
                                    </div>
                                @endif
                            </li>
                        @empty
                            <li class="text-muted fs-11">No approval logs found.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirm Workflow Modals -->
    @if($confirmingAction)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0, 0, 0, 0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0">
                    <form wire:submit.prevent="submitApproval">
                        <div class="modal-header bg-{{ $confirmingAction === 'approve' ? 'success' : 'danger' }} text-white">
                            <h5 class="modal-title text-white"><span class="fas fa-check-circle me-2"></span>Confirm {{ ucfirst($confirmingAction) }}</h5>
                            <button type="button" wire:click="cancelAction" class="btn-close btn-close-white" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to {{ $confirmingAction }} this expense? Please provide any comments below.</p>
                            
                            <div class="mb-3">
                                <label class="form-label" for="action_com">Comments {{ $confirmingAction === 'reject' ? '(Required)' : '' }}</label>
                                <textarea wire:model="comments" class="form-control form-control-sm @error('comments') is-invalid @enderror" id="action_com" rows="3" placeholder="Enter comments here..."></textarea>
                                @error('comments') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" wire:click="cancelAction" class="btn btn-falcon-default btn-sm">Cancel</button>
                            <button type="submit" class="btn btn-{{ $confirmingAction === 'approve' ? 'success' : 'danger' }} btn-sm">{{ ucfirst($confirmingAction) }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
