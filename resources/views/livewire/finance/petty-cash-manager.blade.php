<div>
    <!-- KPI Row -->
    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-md-3">
            <div class="card overflow-hidden shadow-none border">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted text-uppercase fs-11 mb-1">Total Drawer Cash</h6>
                            <h4 class="fw-bold font-monospace text-primary mb-0">{{ number_format($totalBalance, 2) }}</h4>
                            <span class="fs-11 text-muted">Across all active drawers</span>
                        </div>
                        <div class="icon-item bg-subtle-primary text-primary rounded-circle"><span class="fas fa-wallet"></span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-md-3">
            <div class="card overflow-hidden shadow-none border">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted text-uppercase fs-11 mb-1">Active Drawers</h6>
                            <h4 class="fw-bold font-monospace text-success mb-0">{{ $activeDrawersCount }}</h4>
                            <span class="fs-11 text-muted">Authorized locations</span>
                        </div>
                        <div class="icon-item bg-subtle-success text-success rounded-circle"><span class="fas fa-cash-register"></span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-md-3">
            <div class="card overflow-hidden shadow-none border">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted text-uppercase fs-11 mb-1">Low Cash Warning</h6>
                            <h4 class="fw-bold font-monospace {{ $lowBalanceCount > 0 ? 'text-danger' : 'text-secondary' }} mb-0">{{ $lowBalanceCount }}</h4>
                            <span class="fs-11 text-muted">Below 20% threshold</span>
                        </div>
                        <div class="icon-item bg-subtle-{{ $lowBalanceCount > 0 ? 'danger' : 'secondary' }} text-{{ $lowBalanceCount > 0 ? 'danger' : 'secondary' }} rounded-circle"><span class="fas fa-exclamation-triangle"></span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-md-3">
            <div class="card overflow-hidden shadow-none border">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted text-uppercase fs-11 mb-1">Disbursed MTD</h6>
                            <h4 class="fw-bold font-monospace text-info mb-0">{{ number_format($disbursedMtd, 2) }}</h4>
                            <span class="fs-11 text-muted">This month expenses</span>
                        </div>
                        <div class="icon-item bg-subtle-info text-info rounded-circle"><span class="fas fa-receipt"></span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Header Block & Table -->
    <div class="card mb-3 shadow-none border">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2 py-2">
            <h6 class="mb-0"><span class="fas fa-cash-register me-2 text-primary"></span>Petty Cash Drawer Accounts</h6>
            <div class="d-flex gap-2">
                <a href="{{ route('expenses.create') }}?payment_method=Petty+Cash" class="btn btn-outline-success btn-sm">
                    <span class="fas fa-receipt me-1"></span> Record Cash Expense
                </a>
                <button wire:click="openCreateForm" class="btn btn-falcon-primary btn-sm" type="button">
                    <span class="fas fa-plus me-1"></span> New Drawer
                </button>
            </div>
        </div>

        <!-- Filter Toolbar -->
        <div class="card-body bg-light border-bottom py-2">
            <div class="row g-2">
                <div class="col-md-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><span class="fas fa-search"></span></span>
                        <input wire:model.live.debounce.300ms="search" type="text" class="form-control" placeholder="Search drawer by name...">
                    </div>
                </div>
                <div class="col-md-6">
                    <select wire:model.live="filterBranch" class="form-select form-select-sm">
                        <option value="">All Branches</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        
        <div class="card-body p-0">
            @if(session('success'))
                <div class="alert alert-success border-2 d-flex align-items-center m-3 py-2" role="alert">
                    <div class="bg-success me-2 icon-item icon-item-sm rounded-circle text-white d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;"><span class="fas fa-check fs-11"></span></div>
                    <p class="mb-0 flex-grow-1 fs-11 text-success-800">{{ session('success') }}</p>
                    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger border-2 d-flex align-items-center m-3 py-2" role="alert">
                    <div class="bg-danger me-2 icon-item icon-item-sm rounded-circle text-white d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;"><span class="fas fa-times fs-11"></span></div>
                    <p class="mb-0 flex-grow-1 fs-11 text-danger-800">{{ session('error') }}</p>
                    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="table-responsive scrollbar">
                <table class="table table-sm table-striped fs-10 mb-0 align-middle table-hover">
                    <thead class="bg-200 text-900">
                        <tr>
                            <th class="px-3" style="width: 22%;">Drawer Account Name</th>
                            <th style="width: 15%;">Branch</th>
                            <th style="width: 15%;">Custodian</th>
                            <th style="width: 16%;">GL Account</th>
                            <th class="text-end" style="width: 11%;">Limit</th>
                            <th class="text-end" style="width: 11%;">Current Balance</th>
                            <th class="text-center" style="width: 8%;">Status</th>
                            <th class="text-end px-3" style="width: 12%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($accounts as $acc)
                            @php
                                $isLow = $acc->limit_amount > 0 && ($acc->current_balance / $acc->limit_amount) < 0.20;
                            @endphp
                            <tr>
                                <td class="px-3 fw-semi-bold">
                                    {{ $acc->account_name }}
                                </td>
                                <td>{{ $acc->branch->name ?? '—' }}</td>
                                <td>
                                    <span class="text-800">{{ $acc->custodian->name ?? '—' }}</span>
                                </td>
                                <td>
                                    @if($acc->glAccount)
                                        <span class="font-monospace text-secondary">[{{ $acc->glAccount->account_code }}]</span> {{ $acc->glAccount->name }}
                                    @else
                                        <span class="text-muted">Standard Petty Cash [1002]</span>
                                    @endif
                                </td>
                                <td class="text-end fw-bold font-monospace">{{ number_format($acc->limit_amount, 2) }}</td>
                                <td class="text-end fw-bold font-monospace">
                                    <span class="{{ $isLow ? 'text-danger' : 'text-success' }}">
                                        {{ number_format($acc->current_balance, 2) }} PKR
                                    </span>
                                    @if($isLow)
                                        <span class="badge badge-subtle-danger rounded-pill ms-1" style="font-size: 8px;">Low</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($acc->is_active)
                                        <span class="badge badge-subtle-success rounded-pill"><span class="fas fa-check-circle me-1"></span>Active</span>
                                    @else
                                        <span class="badge badge-subtle-secondary rounded-pill">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end px-3">
                                    <div class="d-flex justify-content-end gap-2 align-items-center">
                                        <a href="{{ route('expenses.create') }}?payment_method=Petty+Cash&petty_cash_account_id={{ $acc->id }}&branch_id={{ $acc->branch_id }}" class="btn btn-sm btn-link p-0 text-success" title="Record Expense From This Drawer">
                                            <span class="fas fa-receipt"></span>
                                        </a>
                                        <button wire:click="openReplenish({{ $acc->id }})" class="btn btn-sm btn-link p-0 text-primary" title="Replenish Drawer Cash">
                                            <span class="fas fa-donate"></span>
                                        </button>
                                        <button wire:click="openReconcile({{ $acc->id }})" class="btn btn-sm btn-link p-0 text-info" title="Reconcile Cash Balance">
                                            <span class="fas fa-clipboard-check"></span>
                                        </button>
                                        <button wire:click="edit({{ $acc->id }})" class="btn btn-sm btn-link p-0 text-secondary" title="Edit Setup">
                                            <span class="fas fa-edit"></span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <span class="fas fa-wallet fa-2x mb-2 d-block"></span>
                                    No petty cash accounts configured yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="card-footer d-flex justify-content-end py-2">
                {{ $accounts->links() }}
            </div>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    @if($isFormOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0, 0, 0, 0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <form wire:submit.prevent="save">
                        <div class="modal-header bg-light">
                            <h5 class="modal-title fs-10"><span class="fas fa-cash-register me-2 text-primary"></span>{{ $editId ? 'Modify Drawer Setup' : 'Create Petty Cash Drawer' }}</h5>
                            <button type="button" wire:click="closeForm" class="btn-close" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label" for="acc_name">Drawer Account Name <span class="text-danger">*</span></label>
                                <input wire:model="account_name" type="text" class="form-control form-control-sm @error('account_name') is-invalid @enderror" id="acc_name" placeholder="e.g. Main Marquee Front Desk Drawer">
                                @error('account_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="br_id">Branch Location <span class="text-danger">*</span></label>
                                    <select wire:model="branch_id" class="form-select form-select-sm @error('branch_id') is-invalid @enderror" id="br_id">
                                        <option value="">Select branch</option>
                                        @foreach($branches as $b)
                                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="cust_id">Custodian / Incharge <span class="text-danger">*</span></label>
                                    <select wire:model="custodian_id" class="form-select form-select-sm @error('custodian_id') is-invalid @enderror" id="cust_id">
                                        <option value="">Select staff</option>
                                        @foreach($users as $u)
                                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('custodian_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="lim_amt">Authorized Limit (PKR) <span class="text-danger">*</span></label>
                                    <input wire:model="limit_amount" type="number" step="0.01" class="form-control form-control-sm @error('limit_amount') is-invalid @enderror" id="lim_amt" placeholder="50000.00">
                                    @error('limit_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="cur_bal">Current Cash Balance (PKR) <span class="text-danger">*</span></label>
                                    <input wire:model="current_balance" type="number" step="0.01" class="form-control form-control-sm @error('current_balance') is-invalid @enderror" id="cur_bal" placeholder="0.00">
                                    @error('current_balance') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="gl_acc">GL Accounting Map (Optional)</label>
                                <select wire:model="gl_account_id" class="form-select form-select-sm @error('gl_account_id') is-invalid @enderror" id="gl_acc">
                                    <option value="">Auto-map to Standard Petty Cash [1002]</option>
                                    @foreach($glAccounts as $acc)
                                        <option value="{{ $acc->id }}">[{{ $acc->account_code }}] {{ $acc->name }}</option>
                                    @endforeach
                                </select>
                                <span class="text-muted fs-11">Defaults automatically to the standard Petty Cash Drawer asset account.</span>
                                @error('gl_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-check form-switch">
                                <input wire:model="is_active" class="form-check-input" type="checkbox" id="drw_active">
                                <label class="form-check-label fw-semi-bold" for="drw_active">Drawer is active for disbursements</label>
                            </div>
                        </div>
                        <div class="modal-footer bg-light py-2">
                            <button type="button" wire:click="closeForm" class="btn btn-falcon-default btn-sm">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm px-3">
                                <span class="fas fa-save me-1"></span>Save Drawer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Replenish Cash Modal -->
    @if($isReplenishOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0, 0, 0, 0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <form wire:submit.prevent="submitReplenish">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title text-white fs-10"><span class="fas fa-donate me-2"></span>Replenish Petty Cash Drawer</h5>
                            <button type="button" wire:click="closeForm" class="btn-close btn-close-white" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label" for="rep_amt">Replenish Amount (PKR) <span class="text-danger">*</span></label>
                                <input wire:model="replenishAmount" type="number" step="0.01" class="form-control form-control-sm @error('replenishAmount') is-invalid @enderror" id="rep_amt" placeholder="e.g. 20000.00">
                                @error('replenishAmount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="rep_src">Funding Source <span class="text-danger">*</span></label>
                                <select wire:model.live="replenishSource" class="form-select form-select-sm" id="rep_src">
                                    <option value="Cash">Main Cash Account (Cash in Hand)</option>
                                    <option value="Bank">Bank Account Transfer / Withdrawal</option>
                                </select>
                            </div>

                            @if($replenishSource === 'Bank')
                                <div class="mb-3">
                                    <label class="form-label" for="rep_bank">Source Bank Account <span class="text-danger">*</span></label>
                                    <select wire:model="replenishBankAccountId" class="form-select form-select-sm @error('replenishBankAccountId') is-invalid @enderror" id="rep_bank">
                                        <option value="">Select bank account</option>
                                        @foreach($bankAccounts as $ba)
                                            <option value="{{ $ba->id }}">{{ $ba->account_name }} ({{ $ba->account_number }})</option>
                                        @endforeach
                                    </select>
                                    @error('replenishBankAccountId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            @endif
                            <span class="text-muted fs-11"><span class="fas fa-info-circle me-1"></span>This will automatically generate a balanced Journal Voucher in the General Ledger.</span>
                        </div>
                        <div class="modal-footer bg-light py-2">
                            <button type="button" wire:click="closeForm" class="btn btn-falcon-default btn-sm">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm px-3">
                                <span class="fas fa-check-circle me-1"></span>Confirm Replenishment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Reconcile Physical Cash Modal -->
    @if($isReconcileOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0, 0, 0, 0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <form wire:submit.prevent="submitReconcile">
                        <div class="modal-header bg-info text-white">
                            <h5 class="modal-title text-white fs-10"><span class="fas fa-clipboard-check me-2"></span>Physical Cash Reconciliation</h5>
                            <button type="button" wire:click="closeForm" class="btn-close btn-close-white" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label" for="phys_bal">Physical Counted Cash (PKR) <span class="text-danger">*</span></label>
                                <input wire:model="physicalBalance" type="number" step="0.01" class="form-control form-control-sm @error('physicalBalance') is-invalid @enderror" id="phys_bal">
                                @error('physicalBalance') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="rec_notes">Reconciliation Notes / Difference Reason</label>
                                <textarea wire:model="reconcileNotes" class="form-control form-control-sm @error('reconcileNotes') is-invalid @enderror" id="rec_notes" rows="3" placeholder="Explain any cash overage or shortage..."></textarea>
                                @error('reconcileNotes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="modal-footer bg-light py-2">
                            <button type="button" wire:click="closeForm" class="btn btn-falcon-default btn-sm">Cancel</button>
                            <button type="submit" class="btn btn-info btn-sm text-white px-3">
                                <span class="fas fa-check me-1"></span>Save Reconciliation
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
