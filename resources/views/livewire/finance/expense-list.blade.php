<div>
    <!-- Main Card container -->
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><span class="fas fa-list-alt me-2 text-primary"></span>Expense Register</h5>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('expenses.create') }}" class="btn btn-falcon-primary btn-sm text-nowrap">
                    <span class="fas fa-plus me-1"></span> New Expense
                </a>
            </div>
        </div>

        <!-- Filtration drawer -->
        <div class="card-body bg-light border-bottom">
            <div class="row g-2">
                <div class="col-md-3">
                    <input wire:model.live.debounce.300ms="search" type="text" class="form-control form-control-sm" placeholder="Search Voucher / Ref / Cost Center...">
                </div>
                <div class="col-md-2">
                    <select wire:model.live="status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="Draft">Draft</option>
                        <option value="Submitted">Submitted</option>
                        <option value="Pending Approval">Pending Approval</option>
                        <option value="Approved">Approved</option>
                        <option value="Paid">Paid</option>
                        <option value="Posted">Posted</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select wire:model.live="branch_id" class="form-select form-select-sm">
                        <option value="">All Branches</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select wire:model.live="expense_category_id" class="form-select form-select-sm">
                        <option value="">All Categories</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select wire:model.live="payment_status" class="form-select form-select-sm">
                        <option value="">All Pay Status</option>
                        <option value="Paid">Paid</option>
                        <option value="Unpaid">Unpaid</option>
                    </select>
                </div>

                <!-- Date ranges -->
                <div class="col-md-3">
                    <input wire:model.live="start_date" type="date" class="form-control form-control-sm" placeholder="Start Date">
                </div>
                <div class="col-md-3">
                    <input wire:model.live="end_date" type="date" class="form-control form-control-sm" placeholder="End Date">
                </div>
            </div>
        </div>

        <!-- Bulk Action bar -->
        @if(count($selectedExpenses) > 0)
            <div class="card-body bg-light border-bottom py-2 d-flex align-items-center gap-2">
                <span class="fs-10 fw-bold">{{ count($selectedExpenses) }} items selected:</span>
                <button wire:click="submitBulk" class="btn btn-falcon-default btn-xs text-info">
                    <span class="fas fa-paper-plane me-1"></span>Submit Drafts
                </button>
                <button wire:click="postBulk" class="btn btn-falcon-default btn-xs text-success">
                    <span class="fas fa-clipboard-list me-1"></span>Post Approved to GL
                </button>
            </div>
        @endif

        <!-- Table Grid -->
        <div class="card-body p-0">
            @if(session('success'))
                <div class="alert alert-success border-2 d-flex align-items-center m-3" role="alert">
                    <div class="bg-success me-3 icon-item"><span class="fas fa-check-circle text-white fs-8"></span></div>
                    <p class="mb-0 flex-grow-1 text-success-800">{{ session('success') }}</p>
                    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger border-2 d-flex align-items-center m-3" role="alert">
                    <div class="bg-danger me-3 icon-item"><span class="fas fa-times-circle text-white fs-8"></span></div>
                    <p class="mb-0 flex-grow-1 text-danger-800">{{ session('error') }}</p>
                    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="table-responsive scrollbar">
                <table class="table table-sm table-striped fs-10 mb-0 align-middle table-hover">
                    <thead class="bg-200 text-900">
                        <tr>
                            <th class="px-3" style="width: 3%;">
                                <input wire:model.live="selectAll" class="form-check-input" type="checkbox">
                            </th>
                            <th style="width: 12%;">Voucher No</th>
                            <th style="width: 10%;">Date</th>
                            <th style="width: 15%;">Branch / Dept</th>
                            <th style="width: 18%;">Category / Vendor</th>
                            <th class="text-end" style="width: 12%;">Total Amount</th>
                            <th style="width: 12%;">Payment Method</th>
                            <th class="text-center" style="width: 10%;">Status</th>
                            <th class="text-end px-3" style="width: 8%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expenses as $exp)
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
                                $sc = $statusColors[$exp->status] ?? 'secondary';
                            @endphp
                            <tr>
                                <td class="px-3">
                                    <input wire:model.live="selectedExpenses" value="{{ $exp->id }}" class="form-check-input" type="checkbox">
                                </td>
                                <td>
                                    <a href="{{ route('expenses.show', $exp->id) }}" class="fw-bold">{{ $exp->expense_number }}</a>
                                </td>
                                <td>{{ $exp->expense_date->format('Y-m-d') }}</td>
                                <td>
                                    <div class="fw-semi-bold">{{ $exp->branch->name ?? '—' }}</div>
                                    @if($exp->department)
                                        <div class="text-muted fs-11">{{ $exp->department }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($exp->is_multiline)
                                        <span class="badge badge-subtle-primary">Split Entries</span>
                                    @else
                                        <div class="fw-semi-bold">{{ $exp->category->name ?? '—' }}</div>
                                    @endif
                                    @if($exp->supplier)
                                        <div class="text-muted fs-11"><span class="fas fa-handshake me-1"></span>{{ $exp->supplier->name }}</div>
                                    @endif
                                </td>
                                <td class="text-end fw-bold font-monospace">{{ number_format($exp->total_amount, 2) }} {{ $exp->currency->code ?? 'PKR' }}</td>
                                <td>
                                    <div>{{ $exp->payment_method }}</div>
                                    @if($exp->payment_method === 'Accounts Payable')
                                        <span class="badge badge-subtle-{{ $exp->payment_status === 'Paid' ? 'success' : 'danger' }} rounded-pill" style="font-size: 8px;">
                                            {{ $exp->payment_status }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-subtle-{{ $sc }} rounded-pill">{{ $exp->status }}</span>
                                </td>
                                <td class="text-end px-3">
                                    <div class="d-flex justify-content-end gap-2">
                                        @if($exp->payment_method === 'Accounts Payable' && $exp->payment_status === 'Unpaid' && in_array($exp->status, ['Approved', 'Posted']))
                                            <button wire:click="openPayModal({{ $exp->id }})" class="btn btn-link p-0 text-success" title="Clear Bill Payment">
                                                <span class="fas fa-receipt"></span>
                                            </button>
                                        @endif
                                        @if($exp->status === 'Draft' || $exp->status === 'Rejected')
                                            <a href="{{ route('expenses.edit', $exp->id) }}" class="btn btn-link p-0 text-primary" title="Edit">
                                                <span class="fas fa-edit"></span>
                                            </a>
                                            <button onclick="confirm('Are you sure you want to delete this expense draft?') || event.stopImmediatePropagation()" wire:click="delete({{ $exp->id }})" class="btn btn-link p-0 text-danger" title="Delete">
                                                <span class="fas fa-trash-alt"></span>
                                            </button>
                                        @else
                                            <a href="{{ route('expenses.show', $exp->id) }}" class="btn btn-link p-0 text-secondary" title="View details">
                                                <span class="fas fa-eye"></span>
                                            </a>
                                        @endif
                                        <a href="{{ route('expenses.create') }}?duplicate={{ $exp->id }}" class="btn btn-link p-0 text-info" title="Duplicate template">
                                            <span class="fas fa-copy"></span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <span class="fas fa-file-invoice-dollar fa-2x mb-2 d-block"></span>
                                    No expense records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-footer d-flex justify-content-end border-top">
                {{ $expenses->links() }}
            </div>
        </div>
    </div>

    <!-- Pay AP Modal -->
    @if($isPayModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0, 0, 0, 0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0">
                    <form wire:submit.prevent="processCreditPayment">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title text-white"><span class="fas fa-receipt me-2"></span>Clear AP Invoice Bill</h5>
                            <button type="button" wire:click="clearPayModal" class="btn-close btn-close-white" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label" for="pay_m">Clear Payment Method</label>
                                <select wire:model.live="payMethod" class="form-select form-select-sm" id="pay_m">
                                    <option value="Cash">Cash in Hand</option>
                                    <option value="Bank">Bank account</option>
                                    <option value="Petty Cash">Petty Cash drawer</option>
                                </select>
                            </div>

                            @if($payMethod === 'Bank')
                                <div class="mb-3">
                                    <label class="form-label" for="bank_pay">Select Bank Account</label>
                                    <select wire:model="payBankAccountId" class="form-select form-select-sm @error('payBankAccountId') is-invalid @enderror" id="bank_pay">
                                        <option value="">Select account</option>
                                        @foreach($bankAccounts as $ba)
                                            <option value="{{ $ba->id }}">{{ $ba->account_name }} ({{ $ba->account_number }})</option>
                                        @endforeach
                                    </select>
                                    @error('payBankAccountId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            @elseif($payMethod === 'Petty Cash')
                                <div class="mb-3">
                                    <label class="form-label" for="petty_pay">Select Petty Cash Account</label>
                                    <select wire:model="payPettyCashAccountId" class="form-select form-select-sm @error('payPettyCashAccountId') is-invalid @enderror" id="petty_pay">
                                        <option value="">Select drawer</option>
                                        @foreach($pettyDrawers as $pd)
                                            <option value="{{ $pd->id }}">{{ $pd->account_name }} (Bal: {{ number_format($pd->current_balance, 2) }} PKR)</option>
                                        @endforeach
                                    </select>
                                    @error('payPettyCashAccountId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            @endif

                            <div class="mb-3">
                                <label class="form-label" for="pay_ref">Reference Number</label>
                                <input wire:model="payReference" type="text" class="form-control form-control-sm" id="pay_ref" placeholder="Cheque / slip ID">
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" wire:click="clearPayModal" class="btn btn-falcon-default btn-sm">Cancel</button>
                            <button type="submit" class="btn btn-success btn-sm">Process Clear</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
