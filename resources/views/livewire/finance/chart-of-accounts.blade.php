<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><span class="fas fa-sitemap me-2 text-primary"></span>Chart of Accounts</h5>
            <div class="d-flex align-items-center gap-2">
                @if(!$isFormOpen)
                    <button wire:click="openCreateForm" class="btn btn-falcon-primary btn-sm text-nowrap" type="button">
                        <span class="fas fa-plus me-1" data-fa-transform="shrink-3"></span> Add Account
                    </button>
                @else
                    <button wire:click="closeForm" class="btn btn-falcon-default btn-sm text-nowrap" type="button">
                        <span class="fas fa-arrow-left me-1"></span> Back to Chart
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

            @if(session('error'))
                <div class="alert alert-danger border-2 d-flex align-items-center m-3" role="alert">
                    <div class="bg-danger me-3 icon-item"><span class="fas fa-times-circle text-white fs-8"></span></div>
                    <p class="mb-0 flex-grow-1 text-danger-800">{{ session('error') }}</p>
                    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if($isFormOpen)
                <div class="p-4 bg-light border-bottom">
                    <h6 class="mb-3">{{ $editId ? 'Edit' : 'Create' }} COA Account</h6>
                    <form wire:submit.prevent="save">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label" for="acc_code">Account Code</label>
                                <input wire:model="account_code" type="text" class="form-control form-control-sm @error('account_code') is-invalid @enderror" id="acc_code" placeholder="e.g. 1001" {{ $editId && Account::find($editId)?->system_generated ? 'disabled' : '' }}>
                                @error('account_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-5">
                                <label class="form-label" for="acc_name">Account Name</label>
                                <input wire:model="name" type="text" class="form-control form-control-sm @error('name') is-invalid @enderror" id="acc_name" placeholder="e.g. Cash in Hand">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="acc_parent">Parent Account</label>
                                <select wire:model="parent_id" class="form-select form-select-sm @error('parent_id') is-invalid @enderror" id="acc_parent" {{ $editId && Account::find($editId)?->system_generated ? 'disabled' : '' }}>
                                    <option value="">No Parent (Root Account)</option>
                                    @foreach($potentialParents as $parent)
                                        <option value="{{ $parent->id }}">[{{ $parent->account_code }}] {{ $parent->name }}</option>
                                    @endforeach
                                </select>
                                @error('parent_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="acc_type">Account Type</label>
                                <select wire:model="account_type_id" class="form-select form-select-sm @error('account_type_id') is-invalid @enderror" id="acc_type" {{ $editId && Account::find($editId)?->system_generated ? 'disabled' : '' }}>
                                    <option value="">Select Account Type</option>
                                    @foreach($accountTypes as $type)
                                        <option value="{{ $type->id }}">{{ $type->name }} ({{ $type->nature }})</option>
                                    @endforeach
                                </select>
                                @error('account_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="acc_desc">Description</label>
                                <input wire:model="description" type="text" class="form-control form-control-sm @error('description') is-invalid @enderror" id="acc_desc" placeholder="Brief details about this account">
                                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-2 d-flex align-items-end">
                                <div class="form-check mb-2">
                                    <input wire:model="is_active" class="form-check-input" type="checkbox" id="acc_active">
                                    <label class="form-check-label mb-0" for="acc_active">
                                        Is Active
                                    </label>
                                </div>
                            </div>

                            @if($editId && Account::find($editId)?->system_generated)
                                <div class="col-12">
                                    <div class="alert alert-warning py-2 mb-0 fs-11" role="alert">
                                        <span class="fas fa-exclamation-triangle me-1"></span>
                                        This is a system-generated account. Code, parent, and type restrictions apply to protect ledger stability.
                                    </div>
                                </div>
                            @endif

                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <span class="fas fa-save me-1"></span>Save Account
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
                            <th class="px-3" style="width: 15%;">Account Code</th>
                            <th style="width: 35%;">Account Name</th>
                            <th style="width: 15%;">Account Type</th>
                            <th style="width: 10%;">Nature</th>
                            <th style="width: 15%;">Description</th>
                            <th class="text-center" style="width: 8%;">Status</th>
                            <th class="text-end px-3" style="width: 7%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($accounts as $account)
                            <tr>
                                <td class="px-3 font-monospace fw-bold text-secondary">{{ $account->account_code }}</td>
                                <td class="fw-semi-bold">
                                    <div style="padding-left: {{ $account->depth * 20 }}px;">
                                        @if($account->depth > 0)
                                            <span class="text-400 me-1">├──</span>
                                        @endif
                                        <span class="{{ $account->depth == 0 ? 'text-900 fw-bold fs-10' : 'text-800' }}">
                                            {{ $account->name }}
                                        </span>
                                        @if($account->system_generated)
                                            <span class="badge badge-subtle-primary rounded-pill font-monospace ms-2" style="font-size: 8px;">System</span>
                                        @endif
                                    </div>
                                </td>
                                <td>{{ $account->accountType->name ?? '—' }}</td>
                                <td>
                                    @php
                                        $natureColors = [
                                            'Asset' => 'success',
                                            'Liability' => 'warning',
                                            'Equity' => 'info',
                                            'Income' => 'primary',
                                            'Expense' => 'danger'
                                        ];
                                        $nc = $natureColors[$account->nature] ?? 'secondary';
                                    @endphp
                                    <span class="badge badge-subtle-{{ $nc }} rounded-pill">{{ $account->nature }}</span>
                                </td>
                                <td class="text-muted">{{ Str::limit($account->description, 30) }}</td>
                                <td class="text-center">
                                    @if($account->is_active)
                                        <span class="badge badge-subtle-success rounded-pill"><span class="fas fa-check"></span></span>
                                    @else
                                        <span class="badge badge-subtle-secondary rounded-pill">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end px-3">
                                    <div class="d-flex justify-content-end gap-2">
                                        <button wire:click="edit({{ $account->id }})" class="btn btn-link p-0 text-primary" title="Edit">
                                            <span class="fas fa-edit"></span>
                                        </button>
                                        @if(!$account->system_generated)
                                            <button onclick="confirm('Are you sure you want to delete this account?') || event.stopImmediatePropagation()" wire:click="delete({{ $account->id }})" class="btn btn-link p-0 text-danger" title="Delete">
                                                <span class="fas fa-trash-alt"></span>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <span class="fas fa-sitemap fa-2x mb-2 d-block"></span>
                                    No accounts found in Chart of Accounts.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
