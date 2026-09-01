<div>
    <div class="row g-3">
        @if($showForm)
        <!-- Create / Edit Form -->
        <div class="col-md-4">
            <div class="card border border-200">
                <div class="card-header bg-light">
                    <h5 class="mb-0">{{ $editId ? 'Edit COA Category' : 'Create COA Category' }}</h5>
                </div>
                <div class="card-body">
                    <form wire:submit.prevent="save">
                        <div class="mb-3">
                            <label class="form-label" for="at-code">Type Code *</label>
                            <input wire:model="code" type="text" class="form-control @error('code') is-invalid @enderror" id="at-code" placeholder="e.g. ASSET" />
                            @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="at-name">Type Name *</label>
                            <input wire:model="name" type="text" class="form-control @error('name') is-invalid @enderror" id="at-name" placeholder="e.g. Current Assets" />
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="at-nature">Nature *</label>
                            <select wire:model="nature" class="form-select @error('nature') is-invalid @enderror" id="at-nature">
                                <option value="">Select Nature...</option>
                                <option value="Asset">Asset</option>
                                <option value="Liability">Liability</option>
                                <option value="Equity">Equity</option>
                                <option value="Income">Income</option>
                                <option value="Expense">Expense</option>
                            </select>
                            @error('nature') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" wire:click="resetForm" class="btn btn-falcon-default btn-sm">Cancel</button>
                            <button type="submit" class="btn btn-falcon-primary btn-sm">
                                <span class="fas fa-save me-1"></span>Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif

        <!-- Account Types Table -->
        <div class="{{ $showForm ? 'col-md-8' : 'col-md-12' }}">
            <div class="card border border-200">
                <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0"><span class="fas fa-tags me-2 text-primary"></span>COA Categories</h5>
                    <div class="d-flex align-items-center gap-2">
                        <!-- Nature Filter -->
                        <select wire:model.live="natureFilter" class="form-select form-select-sm" style="max-width: 140px;">
                            <option value="all">All Natures</option>
                            <option value="Asset">Asset</option>
                            <option value="Liability">Liability</option>
                            <option value="Equity">Equity</option>
                            <option value="Income">Income</option>
                            <option value="Expense">Expense</option>
                        </select>
                        <!-- Search -->
                        <div class="input-group input-group-sm">
                            <input wire:model.live.debounce.300ms="search" class="form-control" type="search" placeholder="Search types..." />
                            <span class="input-group-text"><span class="fas fa-search"></span></span>
                        </div>
                        @if(!$showForm && ($isSuperAdmin || auth()->user()->hasPermission('manage_accounting')))
                            <button wire:click="create" class="btn btn-falcon-primary btn-sm text-nowrap">
                                <span class="fas fa-plus me-1"></span>Add Category
                            </button>
                        @endif
                    </div>
                </div>

                <div class="card-body p-0">
                    @if(session('success'))
                        <div class="alert alert-success border-2 d-flex align-items-center m-3 animate__animated animate__fadeIn" role="alert">
                            <div class="bg-success me-3 icon-item"><span class="fas fa-check-circle text-white fs-8"></span></div>
                            <p class="mb-0 flex-1">{{ session('success') }}</p>
                            <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger border-2 d-flex align-items-center m-3 animate__animated animate__fadeIn" role="alert">
                            <div class="bg-danger me-3 icon-item"><span class="fas fa-times-circle text-white fs-8"></span></div>
                            <p class="mb-0 flex-1">{{ session('error') }}</p>
                            <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="table-responsive scrollbar">
                        <table class="table table-sm table-striped fs-10 mb-0 align-middle">
                            <thead class="bg-200 text-900">
                                <tr>
                                    <th class="px-3" style="width: 140px;">Code</th>
                                    <th>Name</th>
                                    <th style="width: 120px;">Nature</th>
                                    <th style="width: 120px;">Scope</th>
                                    <th class="text-end px-3" style="width: 100px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($accountTypes as $at)
                                    <tr>
                                        <td class="px-3 font-monospace fw-bold text-secondary">{{ $at->code }}</td>
                                        <td class="fw-semi-bold text-900">{{ $at->name }}</td>
                                        <td>
                                            @php
                                                $natureColors = [
                                                    'Asset' => 'primary',
                                                    'Liability' => 'warning',
                                                    'Equity' => 'info',
                                                    'Income' => 'success',
                                                    'Expense' => 'danger',
                                                ];
                                                $color = $natureColors[$at->nature] ?? 'secondary';
                                            @endphp
                                            <span class="badge badge-subtle-{{ $color }} rounded-pill">{{ $at->nature }}</span>
                                        </td>
                                        <td>
                                            @if(is_null($at->marquee_id))
                                                <span class="badge badge-subtle-secondary fs-11">Global Default</span>
                                            @else
                                                <span class="badge badge-subtle-info fs-11">Tenant Custom</span>
                                            @endif
                                        </td>
                                        <td class="text-end px-3">
                                            <div class="d-flex justify-content-end gap-2">
                                                @if($isSuperAdmin || (auth()->user()->hasPermission('manage_accounting') && !is_null($at->marquee_id)))
                                                    <button wire:click="edit({{ $at->id }})" class="btn btn-link p-0" title="Edit">
                                                        <span class="text-primary fas fa-edit"></span>
                                                    </button>
                                                    <button wire:click="confirmDeletion({{ $at->id }})" class="btn btn-link p-0" title="Delete">
                                                        <span class="text-danger fas fa-trash-alt"></span>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">No COA categories found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    @if($confirmingDeletionId)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title text-white"><span class="fas fa-exclamation-triangle me-2"></span>Confirm Deletion</h5>
                        <button type="button" wire:click="cancelDelete" class="btn-close btn-close-white" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0 text-900">Are you sure you want to delete this COA Category? This cannot be undone if there are no linked accounts.</p>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" wire:click="cancelDelete" class="btn btn-falcon-default btn-sm">Cancel</button>
                        <button type="button" wire:click="deleteRecord" class="btn btn-danger btn-sm">
                            <span class="fas fa-trash-alt me-1"></span>Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
