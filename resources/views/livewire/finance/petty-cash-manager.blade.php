<div>
    <!-- Header Block -->
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><span class="fas fa-wallet me-2 text-primary"></span>Petty Cash Drawer Accounts</h5>
            <div>
                <button wire:click="openCreateForm" class="btn btn-falcon-primary btn-sm" type="button">
                    <span class="fas fa-plus me-1"></span> New Cash Drawer
                </button>
            </div>
        </div>
        
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
                            <th class="px-3" style="width: 25%;">Drawer Account Name</th>
                            <th style="width: 15%;">Branch</th>
                            <th style="width: 20%;">GL Mapped Account</th>
                            <th style="width: 15%;">Custodian</th>
                            <th class="text-end" style="width: 10%;">Limit Amount</th>
                            <th class="text-end" style="width: 10%;">Current Balance</th>
                            <th class="text-center" style="width: 8%;">Status</th>
                            <th class="text-end px-3" style="width: 12%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($accounts as $acc)
                            <tr>
                                <td class="px-3 fw-semi-bold">{{ $acc->account_name }}</td>
                                <td>{{ $acc->branch->name ?? '—' }}</td>
                                <td>
                                    @if($acc->glAccount)
                                        <span class="font-monospace text-secondary">[{{ $acc->glAccount->account_code }}]</span> {{ $acc->glAccount->name }}
                                    @else
                                        <span class="text-muted">Not Mapped</span>
                                    @endif
                                </td>
                                <td>{{ $acc->custodian->name ?? '—' }}</td>
                                <td class="text-end fw-bold">{{ number_format($acc->limit_amount, 2) }} PKR</td>
                                <td class="text-end fw-bold">
                                    <span class="{{ $acc->current_balance < ($acc->limit_amount * 0.2) ? 'text-danger' : 'text-success' }}">
                                        {{ number_format($acc->current_balance, 2) }} PKR
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($acc->is_active)
                                        <span class="badge badge-subtle-success rounded-pill"><span class="fas fa-check"></span> Active</span>
                                    @else
                                        <span class="badge badge-subtle-secondary rounded-pill">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end px-3">
                                    <div class="d-flex justify-content-end gap-2">
                                        <button wire:click="openReplenish({{ $acc->id }})" class="btn btn-link p-0 text-success" title="Replenish Drawer">
                                            <span class="fas fa-donate"></span>
                                        </button>
                                        <button wire:click="openReconcile({{ $acc->id }})" class="btn btn-link p-0 text-info" title="Reconcile Cash">
                                            <span class="fas fa-clipboard-check"></span>
                                        </button>
                                        <button wire:click="edit({{ $acc->id }})" class="btn btn-link p-0 text-primary" title="Edit Setup">
                                            <span class="fas fa-edit"></span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <span class="fas fa-wallet fa-2x mb-2 d-block"></span>
                                    No petty cash accounts mapped.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="card-footer d-flex justify-content-end">
                {{ $accounts->links() }}
            </div>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    @if($isFormOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0, 0, 0, 0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0">
                    <form wire:submit.prevent="save">
                        <div class="modal-header bg-light">
                            <h5 class="modal-title"><span class="fas fa-edit me-2"></span>{{ $editId ? 'Modify Drawer Setup' : 'Create Petty Cash Drawer' }}</h5>
                            <button type="button" wire:click="closeForm" class="btn-close" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label" for="acc_name">Drawer Account Name</label>
                                <input wire:model="account_name" type="text" class="form-control form-control-sm @error('account_name') is-invalid @enderror" id="acc_name" placeholder="e.g. Main Office Cash Box">
                                @error('account_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="br_id">Branch Scoping</label>
                                <select wire:model="branch_id" class="form-select form-select-sm @error('branch_id') is-invalid @enderror" id="br_id">
                                    <option value="">Select branch</option>
                                    @foreach($branches as $b)
                                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                                    @endforeach
                                </select>
                                @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="gl_acc">GL Asset Account Mapping</label>
                                <select wire:model="gl_account_id" class="form-select form-select-sm @error('gl_account_id') is-invalid @enderror" id="gl_acc">
                                    <option value="">Select general ledger account</option>
                                    @foreach($glAccounts as $gl)
                                        <option value="{{ $gl->id }}">[{{ $gl->account_code }}] {{ $gl->name }}</option>
                                    @endforeach
                                </select>
                                @error('gl_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="cust_id">Custodian</label>
                                <select wire:model="custodian_id" class="form-select form-select-sm @error('custodian_id') is-invalid @enderror" id="cust_id">
                                    <option value="">Select employee/custodian</option>
                                    @foreach($users as $u)
                                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->role->label ?? '' }})</option>
                                    @endforeach
                                </select>
                                @error('custodian_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="row g-3">
                                <div class="col-6 mb-3">
                                    <label class="form-label" for="lim_amt">Limit Amount (PKR)</label>
                                    <input wire:model="limit_amount" type="number" step="0.01" class="form-control form-control-sm @error('limit_amount') is-invalid @enderror" id="lim_amt">
                                    @error('limit_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label" for="curr_bal">Opening Balance (PKR)</label>
                                    <input wire:model="current_balance" type="number" step="0.01" class="form-control form-control-sm @error('current_balance') is-invalid @enderror" id="curr_bal">
                                    @error('current_balance') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="form-check">
                                <input wire:model="is_active" class="form-check-input" type="checkbox" id="dr_active">
                                <label class="form-check-label mb-0" for="dr_active">Drawer is active</label>
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" wire:click="closeForm" class="btn btn-falcon-default btn-sm">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm">Save drawer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Replenish Modal -->
    @if($isReplenishOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0, 0, 0, 0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0">
                    <form wire:submit.prevent="submitReplenish">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title text-white"><span class="fas fa-donate me-2"></span>Replenish Petty Cash Drawer</h5>
                            <button type="button" wire:click="closeForm" class="btn-close btn-close-white" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label" for="rep_amt">Replenish Amount (PKR)</label>
                                <input wire:model="replenishAmount" type="number" step="0.01" class="form-control form-control-sm @error('replenishAmount') is-invalid @enderror" id="rep_amt" placeholder="Enter amount to add">
                                @error('replenishAmount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="rep_src">Funding Source</label>
                                <select wire:model="replenishSource" class="form-select form-select-sm" id="rep_src">
                                    <option value="Cash">Cash in Hand</option>
                                    <option value="Bank">Bank Transfer</option>
                                </select>
                            </div>

                            @if($replenishSource === 'Bank')
                                <div class="mb-3">
                                    <label class="form-label" for="bank_src">Bank Account Source</label>
                                    <select wire:model="replenishBankAccountId" class="form-select form-select-sm @error('replenishBankAccountId') is-invalid @enderror" id="bank_src">
                                        <option value="">Select funding bank account</option>
                                        @foreach($bankAccounts as $ba)
                                            <option value="{{ $ba->id }}">{{ $ba->account_name }} ({{ $ba->account_number }})</option>
                                        @endforeach
                                    </select>
                                    @error('replenishBankAccountId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            @endif
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" wire:click="closeForm" class="btn btn-falcon-default btn-sm">Cancel</button>
                            <button type="submit" class="btn btn-success btn-sm">Process Transfer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Reconcile Modal -->
    @if($isReconcileOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0, 0, 0, 0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0">
                    <form wire:submit.prevent="submitReconcile">
                        <div class="modal-header bg-info text-white">
                            <h5 class="modal-title text-white"><span class="fas fa-clipboard-check me-2"></span>Reconcile Cash Drawer Balance</h5>
                            <button type="button" wire:click="closeForm" class="btn-close btn-close-white" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label" for="phys_bal">Physical Count Balance (PKR)</label>
                                <input wire:model="physicalBalance" type="number" step="0.01" class="form-control form-control-sm @error('physicalBalance') is-invalid @enderror" id="phys_bal" placeholder="Enter physical counted amount">
                                @error('physicalBalance') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="rec_notes">Reconciliation Audit Notes</label>
                                <textarea wire:model="reconcileNotes" class="form-control form-control-sm @error('reconcileNotes') is-invalid @enderror" id="rec_notes" rows="3" placeholder="Explain discrepancies or audits..."></textarea>
                                @error('reconcileNotes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" wire:click="closeForm" class="btn btn-falcon-default btn-sm">Cancel</button>
                            <button type="submit" class="btn btn-info btn-sm">Submit Audit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
