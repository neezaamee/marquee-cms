<div>
    <style>
        @media print {
            @page {
                size: A4 landscape;
                margin: 12mm 10mm 15mm 10mm;
            }

            .d-print-none,
            .btn,
            .navbar-vertical,
            .navbar-top,
            .footer,
            #navbarVerticalCollapse {
                display: none !important;
            }

            .d-print-block {
                display: block !important;
            }

            .table-responsive {
                overflow: visible !important;
            }

            .table {
                border-collapse: collapse !important;
                width: 100% !important;
                font-size: 10px !important;
            }

            .table th, .table td {
                border: 1px solid #cbd5e1 !important;
                padding: 5px 7px !important;
                color: #0f172a !important;
            }

            .table thead th {
                background-color: #f1f5f9 !important;
                color: #0f172a !important;
                font-weight: 700 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .card {
                border: none !important;
                box-shadow: none !important;
            }

            .card-header {
                display: none !important;
            }
        }
    </style>

    <!-- Main Card container -->
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><span class="fas fa-list-alt me-2 text-primary"></span>Expense Register</h5>
            <div class="d-flex align-items-center flex-wrap gap-2">
                <!-- Per Page Selector -->
                <div class="d-flex align-items-center me-1 d-print-none">
                    <label class="me-1 fs-11 text-muted text-nowrap mb-0" for="perPageSelect">Show:</label>
                    <select wire:model.live="perPage" id="perPageSelect" class="form-select form-select-sm" style="width: 75px;">
                        <option value="10">10</option>
                        <option value="15">15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>

                <!-- Export CSV -->
                <button wire:click="exportCSV" wire:loading.attr="disabled" class="btn btn-falcon-default btn-sm text-nowrap d-print-none" title="Export Filtered CSV">
                    <span wire:loading.remove wire:target="exportCSV"><span class="fas fa-file-csv text-success me-1"></span>Export CSV</span>
                    <span wire:loading wire:target="exportCSV"><span class="spinner-border spinner-border-sm me-1"></span>Exporting...</span>
                </button>

                <!-- PDF Download -->
                <a href="{{ $this->pdfUrl }}" target="_blank" class="btn btn-falcon-default btn-sm text-nowrap d-print-none" title="Download Formatted PDF">
                    <span class="fas fa-file-pdf text-danger me-1"></span>PDF
                </a>

                <!-- Print View -->
                <a href="{{ $this->printUrl }}" target="_blank" class="btn btn-falcon-default btn-sm text-nowrap d-print-none" title="Print Register">
                    <span class="fas fa-print text-primary me-1"></span>Print
                </a>

                <a href="{{ route('expenses.create') }}" class="btn btn-falcon-primary btn-sm text-nowrap d-print-none">
                    <span class="fas fa-plus me-1"></span> New Expense
                </a>
            </div>
        </div>

        <!-- Dedicated Print Header (visible only on paper print / PDF) -->
        <div class="d-none d-print-block p-3 border-bottom">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    @php
                        $user = auth()->user();
                        $activeMarquee = $user ? \App\Models\Marquee::find($user->getActiveMarqueeId() ?: $user->marquee_id) : null;
                        $selectedBranch = $branch_id ? \App\Models\Branch::find($branch_id) : null;
                        $selectedCategory = $expense_category_id ? \App\Models\ExpenseCategory::find($expense_category_id) : null;
                    @endphp
                    <h3 class="mb-1 text-primary fw-bold" style="font-size: 18px;">{{ $activeMarquee->name ?? config('app.name', 'Marquee CMS') }}</h3>
                    <p class="mb-0 text-muted" style="font-size: 10px;">
                        {{ $activeMarquee->address ?? 'Corporate Accounts & Finance' }} &bull; Phone: {{ $activeMarquee->contact_phone ?? '—' }}
                    </p>
                </div>
                <div class="text-end">
                    <h4 class="mb-1 fw-bold text-dark" style="font-size: 15px;">Expense Register Log</h4>
                    <div class="text-muted" style="font-size: 9.5px;">
                        <span><strong>Scope:</strong> {{ $selectedBranch ? $selectedBranch->name : 'All Branches' }}</span> |
                        @if($selectedCategory)
                            <span><strong>Category:</strong> {{ $selectedCategory->name }}</span> |
                        @endif
                        @if($status)
                            <span><strong>Status:</strong> {{ $status }}</span> |
                        @endif
                        <span><strong>Period:</strong> {{ $start_date ?: 'All Dates' }} {{ $end_date ? 'to ' . $end_date : '' }}</span>
                        <br>
                        <span><strong>Printed:</strong> {{ now()->format('d M Y, h:i A') }}</span> |
                        <span><strong>User:</strong> {{ auth()->user()->name ?? 'System' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtration drawer -->
        <div class="card-body bg-light border-bottom d-print-none">
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
                        <option value="Partially Paid">Partially Paid</option>
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
                            <th class="px-3 d-print-none" style="width: 3%;">
                                <input wire:model.live="selectAll" class="form-check-input" type="checkbox">
                            </th>
                            <th style="width: 12%;">Voucher No</th>
                            <th style="width: 10%;">Date</th>
                            <th style="width: 15%;">Branch / Dept</th>
                            <th style="width: 18%;">Category / Vendor</th>
                            <th class="text-end" style="width: 12%;">Total Amount</th>
                            <th style="width: 12%;">Payment Method</th>
                            <th class="text-center" style="width: 10%;">Status</th>
                            <th class="text-end px-3 d-print-none" style="width: 8%;">Actions</th>
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
                                <td class="px-3 d-print-none">
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
                                        @if($exp->payment_status === 'Paid')
                                            <span class="badge badge-subtle-success rounded-pill" style="font-size: 8px;">Paid</span>
                                        @elseif($exp->payment_status === 'Partially Paid')
                                            @php
                                                $pct = $exp->total_amount > 0 ? round(($exp->paid_amount / $exp->total_amount) * 100) : 0;
                                                $due = max(0, $exp->total_amount - ($exp->paid_amount ?? 0));
                                            @endphp
                                            <span class="badge badge-subtle-warning rounded-pill" style="font-size: 8px;" title="Paid: {{ number_format($exp->paid_amount, 2) }} / Due: {{ number_format($due, 2) }}">
                                                Partially Paid ({{ $pct }}%)
                                            </span>
                                            <div class="text-muted" style="font-size: 8px;">Due: {{ number_format($due, 2) }}</div>
                                        @else
                                            <span class="badge badge-subtle-danger rounded-pill" style="font-size: 8px;">Unpaid</span>
                                        @endif
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-subtle-{{ $sc }} rounded-pill">{{ $exp->status }}</span>
                                </td>
                                <td class="text-end px-3 d-print-none">
                                    <div class="d-flex justify-content-end gap-2">
                                        @if($exp->payment_method === 'Accounts Payable' && in_array($exp->payment_status, ['Unpaid', 'Partially Paid']) && in_array($exp->status, ['Approved', 'Posted']))
                                            <button wire:click="openPayModal({{ $exp->id }})" class="btn btn-link p-0 text-success" title="Clear Bill Payment">
                                                <span class="fas fa-receipt"></span>
                                            </button>
                                        @endif
                                        @if(in_array($exp->status, ['Draft', 'Submitted', 'Approved']))
                                            <button wire:click="postDirectly({{ $exp->id }})" wire:loading.attr="disabled" class="btn btn-link p-0 text-success" title="Post to General Ledger">
                                                <span class="fas fa-paper-plane"></span>
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

            <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2 border-top py-2 d-print-none">
                <div class="fs-11 text-muted">
                    Showing {{ $expenses->firstItem() ?? 0 }} to {{ $expenses->lastItem() ?? 0 }} of {{ $expenses->total() }} expenses
                </div>
                <div>
                    {{ $expenses->links() }}
                </div>
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
                            @php
                                $payingExp = $payingExpenseId ? \App\Models\Expense::find($payingExpenseId) : null;
                                $remDue = $payingExp ? max(0, $payingExp->total_amount - ($payingExp->paid_amount ?? 0)) : 0;
                            @endphp
                            @if($payingExp)
                                <div class="alert alert-subtle-info py-2 px-3 fs-11 mb-3">
                                    <div><strong>Voucher:</strong> {{ $payingExp->expense_number }} &bull; {{ $payingExp->supplier->name ?? 'Vendor' }}</div>
                                    <div><strong>Total Bill:</strong> {{ number_format($payingExp->total_amount, 2) }} PKR</div>
                                    @if($payingExp->paid_amount > 0)
                                        <div><strong>Already Settled:</strong> {{ number_format($payingExp->paid_amount, 2) }} PKR</div>
                                    @endif
                                    <div class="fw-bold text-success fs-10 mt-1">Amount to Clear: {{ number_format($remDue, 2) }} PKR</div>
                                </div>
                            @endif

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
