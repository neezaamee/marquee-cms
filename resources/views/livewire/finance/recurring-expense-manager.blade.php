<div>
    <!-- Top KPI Cards -->
    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-md-4">
            <div class="card overflow-hidden shadow-none border">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted text-uppercase fs-11 mb-1">Active Schedules</h6>
                            <h4 class="fw-bold font-monospace text-primary mb-0">{{ $templates->where('is_active', true)->count() }}</h4>
                            <span class="fs-11 text-muted">Automated recurring templates</span>
                        </div>
                        <div class="icon-item bg-subtle-primary text-primary rounded-circle"><span class="fas fa-sync-alt"></span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-md-4">
            <div class="card overflow-hidden shadow-none border">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted text-uppercase fs-11 mb-1">Active Recurring Commitment</h6>
                            <h4 class="fw-bold font-monospace text-success mb-0">{{ number_format($templates->where('is_active', true)->sum('total_amount'), 2) }} PKR</h4>
                            <span class="fs-11 text-muted">Per cycle obligation</span>
                        </div>
                        <div class="icon-item bg-subtle-success text-success rounded-circle"><span class="fas fa-money-bill-wave"></span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-md-4">
            <div class="card overflow-hidden shadow-none border">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted text-uppercase fs-11 mb-1">Quick Action</h6>
                            <button wire:click="openCreateForm" class="btn btn-falcon-primary btn-sm mt-1" type="button">
                                <span class="fas fa-plus me-1"></span> New Recurring Template
                            </button>
                        </div>
                        <div class="icon-item bg-subtle-info text-info rounded-circle"><span class="fas fa-calendar-check"></span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Card registry -->
    <div class="card mb-3 shadow-none border">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2 py-2">
            <h6 class="mb-0"><span class="fas fa-history me-2 text-primary"></span>Recurring Expense Schedules</h6>
            <span class="text-muted fs-11">Generate vouchers on-demand with one click or let schedules run automatically</span>
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
                            <th class="px-3" style="width: 22%;">Description / Purpose</th>
                            <th style="width: 16%;">Category</th>
                            <th style="width: 12%;">Branch</th>
                            <th style="width: 10%;">Frequency</th>
                            <th style="width: 11%;">Next Due</th>
                            <th style="width: 11%;">Last Generated</th>
                            <th class="text-end" style="width: 10%;">Amount (PKR)</th>
                            <th class="text-center" style="width: 8%;">Status</th>
                            <th class="text-end px-3" style="width: 10%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($templates as $tmp)
                            @php
                                $isDue = $tmp->is_active && $tmp->next_generation_date && $tmp->next_generation_date->format('Y-m-d') <= date('Y-m-d');
                            @endphp
                            <tr>
                                <td class="px-3">
                                    <div class="fw-semi-bold">{{ $tmp->description }}</div>
                                    @if($tmp->supplier)
                                        <div class="text-muted fs-11"><span class="fas fa-user-tag me-1"></span>{{ $tmp->supplier->name }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-subtle-secondary font-sans-serif">{{ $tmp->category->name ?? '—' }}</span>
                                </td>
                                <td>{{ $tmp->branch->name ?? 'All Branches' }}</td>
                                <td><span class="badge badge-subtle-primary">{{ $tmp->frequency }}</span></td>
                                <td>
                                    <span class="{{ $isDue ? 'text-danger fw-bold' : '' }}">
                                        {{ $tmp->next_generation_date ? $tmp->next_generation_date->format('d M Y') : '—' }}
                                    </span>
                                    @if($isDue)
                                        <span class="badge badge-subtle-danger rounded-pill ms-1" style="font-size: 8px;">Due</span>
                                    @endif
                                </td>
                                <td>{{ $tmp->last_generated_date ? $tmp->last_generated_date->format('d M Y') : 'Never' }}</td>
                                <td class="text-end fw-bold font-monospace">{{ number_format($tmp->total_amount, 2) }}</td>
                                <td class="text-center">
                                    @if($tmp->is_active)
                                        <span class="badge badge-subtle-success rounded-pill"><span class="fas fa-check-circle me-1"></span>Active</span>
                                    @else
                                        <span class="badge badge-subtle-secondary rounded-pill"><span class="fas fa-pause-circle me-1"></span>Paused</span>
                                    @endif
                                </td>
                                <td class="text-end px-3">
                                    <div class="d-flex justify-content-end gap-2 align-items-center">
                                        @if($tmp->is_active)
                                            <button wire:click="generateNow({{ $tmp->id }})" wire:loading.attr="disabled" class="btn btn-sm btn-link p-0 text-success" title="Generate Voucher Now (Post to GL)">
                                                <span class="fas fa-bolt"></span>
                                            </button>
                                        @endif
                                        <button wire:click="toggleActive({{ $tmp->id }})" class="btn btn-sm btn-link p-0 text-{{ $tmp->is_active ? 'warning' : 'info' }}" title="{{ $tmp->is_active ? 'Pause Schedule' : 'Resume Schedule' }}">
                                            <span class="fas fa-{{ $tmp->is_active ? 'pause' : 'play' }}"></span>
                                        </button>
                                        <button wire:click="skipCycle({{ $tmp->id }})" class="btn btn-sm btn-link p-0 text-secondary" title="Skip Next Cycle">
                                            <span class="fas fa-forward"></span>
                                        </button>
                                        <button wire:click="edit({{ $tmp->id }})" class="btn btn-sm btn-link p-0 text-primary" title="Edit Template">
                                            <span class="fas fa-edit"></span>
                                        </button>
                                        <button onclick="confirm('Delete this recurring expense template?') || event.stopImmediatePropagation()" wire:click="delete({{ $tmp->id }})" class="btn btn-sm btn-link p-0 text-danger" title="Delete">
                                            <span class="fas fa-trash-alt"></span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <span class="fas fa-sync-alt fa-2x mb-2 d-block"></span>
                                    No recurring expense templates defined yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="card-footer d-flex justify-content-end py-2">
                {{ $templates->links() }}
            </div>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    @if($isFormOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0, 0, 0, 0.5);">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow">
                    <form wire:submit.prevent="save">
                        <div class="modal-header bg-light">
                            <h5 class="modal-title fs-10"><span class="fas fa-sync-alt me-2 text-primary"></span>{{ $editId ? 'Edit Recurring Schedule' : 'Create Recurring Schedule' }}</h5>
                            <button type="button" wire:click="closeForm" class="btn-close" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="category">Expense Category <span class="text-danger">*</span></label>
                                    <select wire:model="expense_category_id" class="form-select form-select-sm @error('expense_category_id') is-invalid @enderror" id="category">
                                        <option value="">Select category</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('expense_category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="branch">Branch (Location)</label>
                                    <select wire:model="branch_id" class="form-select form-select-sm @error('branch_id') is-invalid @enderror" id="branch">
                                        <option value="">All Branches (Company-Wide)</option>
                                        @foreach($branches as $br)
                                            <option value="{{ $br->id }}">{{ $br->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="freq">Billing Frequency <span class="text-danger">*</span></label>
                                    <select wire:model="frequency" class="form-select form-select-sm @error('frequency') is-invalid @enderror" id="freq">
                                        <option value="Daily">Daily</option>
                                        <option value="Weekly">Weekly</option>
                                        <option value="Monthly">Monthly</option>
                                        <option value="Quarterly">Quarterly</option>
                                        <option value="Yearly">Yearly</option>
                                    </select>
                                    @error('frequency') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="sub_amt">Recurring Amount (PKR) <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text font-monospace">PKR</span>
                                        <input wire:model="amount" type="number" step="0.01" class="form-control form-control-sm @error('amount') is-invalid @enderror" id="sub_amt" placeholder="0.00">
                                    </div>
                                    @error('amount') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="supplier">Paid To / Supplier (Optional)</label>
                                    <select wire:model="supplier_id" class="form-select form-select-sm @error('supplier_id') is-invalid @enderror" id="supplier">
                                        <option value="">None / Direct</option>
                                        @foreach($suppliers as $sup)
                                            <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('supplier_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="start_dt">Schedule Start Date <span class="text-danger">*</span></label>
                                    <input wire:model="start_date" type="date" class="form-control form-control-sm @error('start_date') is-invalid @enderror" id="start_dt">
                                    @error('start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="end_dt">End Date (Optional, leave blank if indefinite)</label>
                                    <input wire:model="end_date" type="date" class="form-control form-control-sm @error('end_date') is-invalid @enderror" id="end_dt">
                                    @error('end_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-12">
                                    <label class="form-label" for="desc">Description / Purpose <span class="text-danger">*</span></label>
                                    <textarea wire:model="description" class="form-control form-control-sm @error('description') is-invalid @enderror" id="desc" rows="2" placeholder="e.g. Monthly SNGPL Gas Bill or Ground Rent"></textarea>
                                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input wire:model="is_active" class="form-check-input" type="checkbox" id="tmpl_active">
                                        <label class="form-check-label fw-semi-bold" for="tmpl_active">Activate recurring schedule immediately</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-light py-2">
                            <button type="button" wire:click="closeForm" class="btn btn-falcon-default btn-sm">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm px-3">
                                <span class="fas fa-save me-1"></span>Save Schedule
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
