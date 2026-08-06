<div>
    <!-- Main Card registry -->
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><span class="fas fa-sync-alt me-2 text-primary"></span>Recurring Expenses Templates</h5>
            <div>
                <button wire:click="openCreateForm" class="btn btn-falcon-primary btn-sm" type="button">
                    <span class="fas fa-plus me-1"></span> New Recurring Template
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

            <div class="table-responsive scrollbar">
                <table class="table table-sm table-striped fs-10 mb-0 align-middle table-hover">
                    <thead class="bg-200 text-900">
                        <tr>
                            <th class="px-3" style="width: 25%;">Template Description</th>
                            <th style="width: 15%;">Category / Type</th>
                            <th style="width: 10%;">Frequency</th>
                            <th style="width: 12%;">Next Schedule</th>
                            <th style="width: 12%;">Last Processed</th>
                            <th class="text-end" style="width: 12%;">Voucher Amount</th>
                            <th class="text-center" style="width: 8%;">Status</th>
                            <th class="text-end px-3" style="width: 10%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($templates as $tmp)
                            <tr>
                                <td class="px-3 fw-semi-bold">{{ $tmp->description }}</td>
                                <td>
                                    <div class="fw-semi-bold">{{ $tmp->category->name }}</div>
                                    <div class="text-muted fs-11">{{ $tmp->type->name }}</div>
                                </td>
                                <td><span class="badge badge-subtle-primary">{{ $tmp->frequency }}</span></td>
                                <td>{{ $tmp->next_generation_date->format('Y-m-d') }}</td>
                                <td>{{ $tmp->last_generated_date ? $tmp->last_generated_date->format('Y-m-d') : 'Never' }}</td>
                                <td class="text-end fw-bold font-monospace">{{ number_format($tmp->total_amount, 2) }} PKR</td>
                                <td class="text-center">
                                    @if($tmp->is_active)
                                        <span class="badge badge-subtle-success rounded-pill"><span class="fas fa-play me-1"></span>Active</span>
                                    @else
                                        <span class="badge badge-subtle-secondary rounded-pill"><span class="fas fa-pause me-1"></span>Paused</span>
                                    @endif
                                </td>
                                <td class="text-end px-3">
                                    <div class="d-flex justify-content-end gap-2">
                                        <button wire:click="toggleActive({{ $tmp->id }})" class="btn btn-link p-0 text-{{ $tmp->is_active ? 'warning' : 'success' }}" title="{{ $tmp->is_active ? 'Pause Schedule' : 'Resume Schedule' }}">
                                            <span class="fas fa-{{ $tmp->is_active ? 'pause' : 'play' }}"></span>
                                        </button>
                                        <button wire:click="skipCycle({{ $tmp->id }})" class="btn btn-link p-0 text-info" title="Skip Cycle">
                                            <span class="fas fa-step-forward"></span>
                                        </button>
                                        <button wire:click="edit({{ $tmp->id }})" class="btn btn-link p-0 text-primary" title="Edit Template">
                                            <span class="fas fa-edit"></span>
                                        </button>
                                        <button onclick="confirm('Are you sure you want to delete this template?') || event.stopImmediatePropagation()" wire:click="delete({{ $tmp->id }})" class="btn btn-link p-0 text-danger" title="Delete Template">
                                            <span class="fas fa-trash-alt"></span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <span class="fas fa-sync-alt fa-2x mb-2 d-block"></span>
                                    No recurring templates defined.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="card-footer d-flex justify-content-end">
                {{ $templates->links() }}
            </div>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    @if($isFormOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0, 0, 0, 0.5);">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0">
                    <form wire:submit.prevent="save">
                        <div class="modal-header bg-light">
                            <h5 class="modal-title"><span class="fas fa-sync-alt me-2"></span>{{ $editId ? 'Edit Recurring Setup' : 'Create Recurring Template' }}</h5>
                            <button type="button" wire:click="closeForm" class="btn-close" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="category">Expense Category</label>
                                    <select wire:model="expense_category_id" class="form-select form-select-sm @error('expense_category_id') is-invalid @enderror" id="category">
                                        <option value="">Select category</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('expense_category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="type">Expense Type</label>
                                    <select wire:model="expense_type_id" class="form-select form-select-sm @error('expense_type_id') is-invalid @enderror" id="type">
                                        <option value="">Select type</option>
                                        @foreach($expenseTypes as $type)
                                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('expense_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="branch">Branch (Optional)</label>
                                    <select wire:model="branch_id" class="form-select form-select-sm @error('branch_id') is-invalid @enderror" id="branch">
                                        <option value="">All Branches</option>
                                        @foreach($branches as $br)
                                            <option value="{{ $br->id }}">{{ $br->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="department">Department (Optional)</label>
                                    <select wire:model="department" class="form-select form-select-sm @error('department') is-invalid @enderror" id="department">
                                        <option value="">Select department</option>
                                        @foreach($departments as $dept)
                                            <option value="{{ $dept }}">{{ $dept }}</option>
                                        @endforeach
                                    </select>
                                    @error('department') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="cost_center">Cost Center (Optional)</label>
                                    <input wire:model="cost_center" type="text" class="form-control form-control-sm @error('cost_center') is-invalid @enderror" id="cost_center">
                                    @error('cost_center') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="supplier">Supplier / Vendor (Optional)</label>
                                    <select wire:model="supplier_id" class="form-select form-select-sm @error('supplier_id') is-invalid @enderror" id="supplier">
                                        <option value="">Select supplier</option>
                                        @foreach($suppliers as $sup)
                                            <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('supplier_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="employee">Employee (Optional)</label>
                                    <select wire:model="employee_id" class="form-select form-select-sm @error('employee_id') is-invalid @enderror" id="employee">
                                        <option value="">Select employee</option>
                                        @foreach($employees as $emp)
                                            <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('employee_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="sub_amt">Subtotal Amount (PKR)</label>
                                    <input wire:model="amount" type="number" step="0.01" class="form-control form-control-sm @error('amount') is-invalid @enderror" id="sub_amt">
                                    @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="tax_amt">Tax Amount (PKR)</label>
                                    <input wire:model="tax_amount" type="number" step="0.01" class="form-control form-control-sm @error('tax_amount') is-invalid @enderror" id="tax_amt">
                                    @error('tax_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="disc_amt">Discount Amount (PKR)</label>
                                    <input wire:model="discount_amount" type="number" step="0.01" class="form-control form-control-sm @error('discount_amount') is-invalid @enderror" id="disc_amt">
                                    @error('discount_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="freq">Billing Frequency</label>
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
                                    <label class="form-label" for="start_dt">Start Date</label>
                                    <input wire:model="start_date" type="date" class="form-control form-control-sm @error('start_date') is-invalid @enderror" id="start_dt">
                                    @error('start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="end_dt">End Date (Optional)</label>
                                    <input wire:model="end_date" type="date" class="form-control form-control-sm @error('end_date') is-invalid @enderror" id="end_dt">
                                    @error('end_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-12">
                                    <label class="form-label" for="desc">Template Description / Narration</label>
                                    <textarea wire:model="description" class="form-control form-control-sm @error('description') is-invalid @enderror" id="desc" rows="3" placeholder="e.g. Monthly Internet Bill for Branch Office"></textarea>
                                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-12">
                                    <div class="form-check">
                                        <input wire:model="is_active" class="form-check-input" type="checkbox" id="tmpl_active">
                                        <label class="form-check-label mb-0" for="tmpl_active">Template schedule is active</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" wire:click="closeForm" class="btn btn-falcon-default btn-sm">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm">Save Template</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
