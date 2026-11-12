<div>
    <!-- KPI Summary Cards -->
    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm bg-body-tertiary">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="fs-11 text-muted text-uppercase fw-bold">Cash in Hand</span>
                            <h4 class="mb-0 fw-bolder text-success mt-1">Rs. {{ number_format($totalCashBalance, 2) }}</h4>
                        </div>
                        <div class="avatar avatar-m bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center">
                            <span class="fas fa-money-bill-wave fa-lg"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm bg-body-tertiary">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="fs-11 text-muted text-uppercase fw-bold">Bank Balance</span>
                            <h4 class="mb-0 fw-bolder text-primary mt-1">Rs. {{ number_format($totalBankBalance, 2) }}</h4>
                        </div>
                        <div class="avatar avatar-m bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center">
                            <span class="fas fa-university fa-lg"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm bg-body-tertiary">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="fs-11 text-muted text-uppercase fw-bold">Total Liquid Assets</span>
                            <h4 class="mb-0 fw-bolder text-dark mt-1">Rs. {{ number_format($totalLiquidBalance, 2) }}</h4>
                        </div>
                        <div class="avatar avatar-m bg-info-subtle text-info rounded-circle d-flex align-items-center justify-content-center">
                            <span class="fas fa-coins fa-lg"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm bg-primary-subtle d-flex justify-content-center">
                <div class="card-body p-3 text-center">
                    <a href="{{ route('finance.daily-cash-bank-report') }}" class="btn btn-primary btn-sm fw-bold w-100">
                        <span class="fas fa-file-invoice-dollar me-1"></span> Daily Day-Book Report
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><span class="fas fa-university me-2 text-primary"></span>Cash & Bank Accounts</h5>
            <div class="d-flex align-items-center gap-2">
                @if(!$isFormOpen)
                    <button wire:click="openCreateForm" class="btn btn-falcon-primary btn-sm text-nowrap" type="button">
                        <span class="fas fa-plus me-1" data-fa-transform="shrink-3"></span> Add Cash/Bank Account
                    </button>
                @else
                    <button wire:click="closeForm" class="btn btn-falcon-default btn-sm text-nowrap" type="button">
                        <span class="fas fa-arrow-left me-1"></span> Back to List
                    </button>
                @endif
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

            @if($isFormOpen)
                <div class="p-4 bg-light border-bottom">
                    <h6 class="mb-3">{{ $editId ? 'Edit' : 'Register' }} Cash/Bank Account</h6>
                    <form wire:submit.prevent="save">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="cb_acc">General Ledger Account <span class="text-danger">*</span></label>
                                <select wire:model="account_id" class="form-select form-select-sm @error('account_id') is-invalid @enderror" id="cb_acc">
                                    <option value="">Select COA Account</option>
                                    @foreach($accounts as $acc)
                                        <option value="{{ $acc->id }}">[{{ $acc->account_code }}] {{ $acc->name }}</option>
                                    @endforeach
                                </select>
                                @error('account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label" for="cb_type">Account Type <span class="text-danger">*</span></label>
                                <select wire:model.live="type" class="form-select form-select-sm @error('type') is-invalid @enderror" id="cb_type">
                                    <option value="cash">Cash Register / Drawer</option>
                                    <option value="bank">Bank Account</option>
                                </select>
                                @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label" for="cb_status">Status</label>
                                <select wire:model="status" class="form-select form-select-sm @error('status') is-invalid @enderror" id="cb_status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                                @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            @if($type === 'bank')
                                <div class="col-md-4">
                                    <label class="form-label" for="cb_bank_name">Bank Name <span class="text-danger">*</span></label>
                                    <input wire:model="bank_name" type="text" class="form-control form-control-sm @error('bank_name') is-invalid @enderror" id="cb_bank_name" placeholder="e.g. Habib Bank Limited">
                                    @error('bank_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="cb_acc_num">Account Number <span class="text-danger">*</span></label>
                                    <input wire:model="account_number" type="text" class="form-control form-control-sm @error('account_number') is-invalid @enderror" id="cb_acc_num" placeholder="e.g. 1234567890">
                                    @error('account_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="cb_iban">IBAN</label>
                                    <input wire:model="iban" type="text" class="form-control form-control-sm @error('iban') is-invalid @enderror" id="cb_iban" placeholder="e.g. PK00HABB00000123456789">
                                    @error('iban') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="cb_branch">Branch Name</label>
                                    <input wire:model="branch_name" type="text" class="form-control form-control-sm @error('branch_name') is-invalid @enderror" id="cb_branch" placeholder="e.g. Gulberg Branch">
                                    @error('branch_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            @endif

                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <span class="fas fa-save me-1"></span>Save Mapping
                                </button>
                                <button type="button" wire:click="closeForm" class="btn btn-link btn-sm text-secondary">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            @endif

            <div class="table-responsive scrollbar">
                <table class="table table-sm table-striped fs-10 mb-0 align-middle table-hover">
                    <thead class="bg-200 text-900">
                        <tr>
                            <th class="px-3" style="width: 22%;">COA Account</th>
                            <th style="width: 12%;">Type</th>
                            <th style="width: 18%;">Bank Name</th>
                            <th style="width: 13%;">Account Number</th>
                            <th style="width: 12%;">IBAN</th>
                            <th class="text-end" style="width: 13%;">Current Balance</th>
                            <th class="text-center" style="width: 5%;">Status</th>
                            <th class="text-end px-3" style="width: 5%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cashBankAccounts as $cb)
                            <tr>
                                <td class="px-3 fw-bold">
                                    [{{ $cb->account->account_code ?? '—' }}] {{ $cb->account->name ?? '—' }}
                                </td>
                                <td>
                                    @if($cb->type === 'cash')
                                        <span class="badge badge-subtle-success rounded-pill"><span class="fas fa-money-bill-wave me-1"></span>Cash drawer</span>
                                    @else
                                        <span class="badge badge-subtle-primary rounded-pill"><span class="fas fa-university me-1"></span>Bank account</span>
                                    @endif
                                </td>
                                <td>{{ $cb->bank_name ?? '—' }}</td>
                                <td class="font-monospace">{{ $cb->account_number ?? '—' }}</td>
                                <td class="font-monospace">{{ $cb->iban ?? '—' }}</td>
                                <td class="text-end font-monospace fw-bold {{ ($accountBalances[$cb->account_id] ?? 0) >= 0 ? 'text-900' : 'text-danger' }}">
                                    Rs. {{ number_format($accountBalances[$cb->account_id] ?? 0, 2) }}
                                </td>
                                <td class="text-center">
                                    @if($cb->status === 'active')
                                        <span class="badge badge-subtle-success rounded-pill">Active</span>
                                    @else
                                        <span class="badge badge-subtle-secondary rounded-pill">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end px-3">
                                    <div class="d-flex justify-content-end gap-2 align-items-center">
                                        @if($cb->account_id)
                                            <a href="{{ route('finance.general-ledger', ['account_id' => $cb->account_id]) }}" class="btn btn-link p-0 text-info" title="View Full General Ledger">
                                                <span class="fas fa-book-open"></span>
                                            </a>
                                        @endif
                                        <button wire:click="edit({{ $cb->id }})" class="btn btn-link p-0 text-primary" title="Edit">
                                            <span class="fas fa-edit"></span>
                                        </button>
                                        <button onclick="confirm('Are you sure you want to remove this mapping?') || event.stopImmediatePropagation()" wire:click="delete({{ $cb->id }})" class="btn btn-link p-0 text-danger" title="Delete">
                                            <span class="fas fa-trash-alt"></span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <span class="fas fa-university fa-2x mb-2 d-block"></span>
                                    No cash or bank accounts registered.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($cashBankAccounts->hasPages())
            <div class="card-footer d-flex align-items-center justify-content-center bg-light">
                {{ $cashBankAccounts->links() }}
            </div>
        @endif
    </div>
</div>
