<div class="row g-3">
    <!-- Category Form Pane -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    <span class="fas fa-folder-plus me-2 text-primary"></span>
                    {{ $editId ? 'Edit Category' : 'Create Category' }}
                </h5>
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

                <form wire:submit.prevent="save">
                    <div class="mb-3">
                        <label class="form-label font-sans-serif" for="cat_code">Category Code</label>
                        <input wire:model="category_code" type="text" class="form-control form-control-sm @error('category_code') is-invalid @enderror" id="cat_code" placeholder="e.g. UTIL-E">
                        @error('category_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-sans-serif" for="cat_name">Category Name</label>
                        <input wire:model="name" type="text" class="form-control form-control-sm @error('name') is-invalid @enderror" id="cat_name" placeholder="e.g. Electricity Bill">
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-sans-serif" for="parent_id">Parent Category</label>
                        <select wire:model="parent_id" class="form-select form-select-sm @error('parent_id') is-invalid @enderror" id="parent_id">
                            <option value="">No Parent (Root Category)</option>
                            @foreach($categories->whereNull('parent_id') as $rootCat)
                                @if($rootCat->id != $editId)
                                    <option value="{{ $rootCat->id }}">{{ $rootCat->name }}</option>
                                @endif
                            @endforeach
                        </select>
                        @error('parent_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-sans-serif" for="default_account">Default GL Account</label>
                        <select wire:model="default_account_id" class="form-select form-select-sm @error('default_account_id') is-invalid @enderror" id="default_account">
                            <option value="">Select Expense Account</option>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}">[{{ $acc->account_code }}] {{ $acc->name }}</option>
                            @endforeach
                        </select>
                        @error('default_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="row g-3">
                        <div class="col-6 mb-3">
                            <label class="form-label font-sans-serif" for="tax_rate">Default Tax (%)</label>
                            <input wire:model="default_tax_rate" type="number" step="0.01" class="form-control form-control-sm @error('default_tax_rate') is-invalid @enderror" id="tax_rate">
                            @error('default_tax_rate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-6 mb-3">
                            <label class="form-label font-sans-serif" for="budget_amt">Default Budget</label>
                            <input wire:model="default_budget_amount" type="number" step="0.01" class="form-control form-control-sm @error('default_budget_amount') is-invalid @enderror" id="budget_amt">
                            @error('default_budget_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-6 mb-3">
                            <label class="form-label font-sans-serif" for="disp_order">Display Order</label>
                            <input wire:model="display_order" type="number" class="form-control form-control-sm @error('display_order') is-invalid @enderror" id="disp_order">
                            @error('display_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-6 d-flex align-items-end mb-3">
                            <div class="form-check mb-2">
                                <input wire:model="is_active" class="form-check-input" type="checkbox" id="cat_active">
                                <label class="form-check-label mb-0" for="cat_active">Is Active</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-sans-serif" for="cat_desc">Description</label>
                        <textarea wire:model="description" class="form-control form-control-sm @error('description') is-invalid @enderror" id="cat_desc" rows="2"></textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary btn-sm px-3">
                            <span class="fas fa-save me-1"></span>Save
                        </button>
                        <button type="button" wire:click="resetInputFields" class="btn btn-falcon-default btn-sm px-3">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Category Registry Tree View -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header bg-light">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0"><span class="fas fa-folder me-2 text-primary"></span>Category Hierarchy</h5>
                    <div class="d-flex align-items-center gap-2">
                        <!-- Status Filter -->
                        <select wire:model.live="statusFilter" class="form-select form-select-sm" style="max-width: 140px;">
                            <option value="all">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <!-- Search -->
                        <div class="input-group input-group-sm">
                            <input wire:model.live.debounce.300ms="search" class="form-control" type="search" placeholder="Search categories..." />
                            <span class="input-group-text"><span class="fas fa-search"></span></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                @if(session('error'))
                    <div class="alert alert-danger border-2 d-flex align-items-center m-3 animate__animated animate__fadeIn" role="alert">
                        <div class="bg-danger me-3 icon-item"><span class="fas fa-times-circle text-white fs-8"></span></div>
                        <p class="mb-0 flex-grow-1 text-danger-800">{{ session('error') }}</p>
                        <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                <div class="table-responsive scrollbar">
                    <table class="table table-sm table-striped fs-10 mb-0 align-middle table-hover">
                        <thead class="bg-200 text-900">
                            <tr>
                                <th class="px-3" style="width: 20%;">Category Code</th>
                                <th style="width: 30%;">Category Name</th>
                                <th style="width: 25%;">Default GL Account</th>
                                <th class="text-end" style="width: 10%;">Tax Rate</th>
                                <th class="text-center" style="width: 8%;">Status</th>
                                <th class="text-end px-3" style="width: 7%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categories as $category)
                                @php
                                    $depth = $category->parent_id ? 1 : 0;
                                @endphp
                                <tr>
                                    <td class="px-3 font-monospace fw-bold text-secondary">{{ $category->category_code }}</td>
                                    <td class="fw-semi-bold">
                                        <div style="padding-left: {{ $depth * 20 }}px;">
                                            @if($depth > 0)
                                                <span class="text-400 me-1">└──</span>
                                            @endif
                                            <span class="{{ $depth == 0 ? 'text-900 fw-bold fs-10' : 'text-800' }}">
                                                {{ $category->name }}
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        @if($category->defaultAccount)
                                            <span class="text-700">[{{ $category->defaultAccount->account_code }}] {{ $category->defaultAccount->name }}</span>
                                        @else
                                            <span class="text-muted fs-11">Not Mapped</span>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ number_format($category->default_tax_rate, 2) }}%</td>
                                    <td class="text-center">
                                        @if($category->is_active)
                                            <span class="badge badge-subtle-success rounded-pill"><span class="fas fa-check"></span></span>
                                        @else
                                            <span class="badge badge-subtle-secondary rounded-pill">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-end px-3">
                                        <div class="d-flex justify-content-end gap-2">
                                            <button wire:click="moveUp({{ $category->id }})" class="btn btn-link p-0 text-secondary" title="Move Up">
                                                <span class="fas fa-caret-up"></span>
                                            </button>
                                            <button wire:click="moveDown({{ $category->id }})" class="btn btn-link p-0 text-secondary" title="Move Down">
                                                <span class="fas fa-caret-down"></span>
                                            </button>
                                            <button wire:click="edit({{ $category->id }})" class="btn btn-link p-0 text-primary" title="Edit">
                                                <span class="fas fa-edit"></span>
                                            </button>
                                            <button wire:click="confirmDelete({{ $category->id }})" class="btn btn-link p-0 text-danger" title="Delete">
                                                <span class="fas fa-trash-alt"></span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <span class="fas fa-folder-open fa-2x mb-2 d-block"></span>
                                        No categories found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirm Deletion Modal -->
    @if($confirmingDeletion)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0, 0, 0, 0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title text-white"><span class="fas fa-exclamation-triangle me-2"></span>Confirm Deletion</h5>
                        <button type="button" wire:click="cancelDelete" class="btn-close btn-close-white" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this category? This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" wire:click="cancelDelete" class="btn btn-falcon-default btn-sm">Cancel</button>
                        <button type="button" wire:click="deleteCategory" class="btn btn-danger btn-sm">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
