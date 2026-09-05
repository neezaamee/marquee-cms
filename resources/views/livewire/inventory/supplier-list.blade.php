<div>
    <div class="row g-3">
        <!-- Sidebar Form to Create/Edit Supplier -->
        @if($showForm)
            <div class="col-md-4">
                <div class="card border border-200">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">{{ $editId ? 'Edit Supplier' : 'Register Supplier' }}</h5>
                    </div>
                    <div class="card-body">
                        <form wire:submit.prevent="save">
                            <div class="mb-3">
                                <label class="form-label" for="supplier-name">Supplier/Company Name *</label>
                                <input wire:model="name" class="form-control @error('name') is-invalid @enderror" id="supplier-name" type="text" placeholder="e.g. Al-Makkah Foods" />
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="contact-person">Contact Person</label>
                                <input wire:model="contact_person" class="form-control @error('contact_person') is-invalid @enderror" id="contact-person" type="text" placeholder="e.g. Haji Ali" />
                                @error('contact_person') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="mobile-number">Mobile Number *</label>
                                <input wire:model="mobile_number" class="form-control @error('mobile_number') is-invalid @enderror" id="mobile-number" type="text" placeholder="e.g. 0300-1234567" />
                                @error('mobile_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="whatsapp-number">WhatsApp Number</label>
                                <input wire:model="whatsapp_number" class="form-control" id="whatsapp-number" type="text" placeholder="e.g. 0300-1234567" />
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="supplier-email">Email Address</label>
                                <input wire:model="email" class="form-control @error('email') is-invalid @enderror" id="supplier-email" type="email" placeholder="e.g. contact@almakkah.com" />
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="supplier-city">City</label>
                                <input wire:model="city" class="form-control" id="supplier-city" type="text" placeholder="e.g. Lahore" />
                            </div>

                            <div class="mb-3">
                                <label class="form-label fs-11 fw-bold text-600" for="supplier-address">Address</label>
                                <input wire:model="address" class="form-control form-control-sm" id="supplier-address" type="text" placeholder="Supplier shop address..." />
                            </div>

                            <!-- Supplier Categories Multi-Select -->
                            <div class="mb-3">
                                <label class="form-label fs-11 fw-bold text-600">Supplier Categories</label>
                                <div class="border rounded p-2 bg-white" style="max-height: 180px; overflow-y: auto;">
                                    @forelse($availableCategories as $cat)
                                        <div class="form-check mb-1">
                                            <input wire:model="selectedCategories" class="form-check-input" type="checkbox" value="{{ $cat->id }}" id="cat-check-{{ $cat->id }}">
                                            <label class="form-check-label fs-11 d-flex align-items-center justify-content-between" for="cat-check-{{ $cat->id }}">
                                                <span>{{ $cat->name }}</span>
                                                <span class="badge badge-subtle-secondary font-monospace" style="font-size: 9px;">{{ $cat->code }}</span>
                                            </label>
                                        </div>
                                    @empty
                                        <p class="text-muted fs-11 mb-0 py-1">No categories configured. <a href="{{ route('supplier-categories.index') }}" target="_blank">Manage Categories</a></p>
                                    @endforelse
                                </div>
                                @error('selectedCategories') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                                <small class="text-500 fs-11 d-block mt-1">Check all procurement types provided by this supplier.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fs-11 fw-bold text-600" for="supplier-desc">Opening Balance *</label>
                                <input wire:model="opening_balance" type="number" step="0.01" class="form-control form-control-sm @error('opening_balance') is-invalid @enderror" id="opening-balance" {{ $editId ? 'disabled' : '' }} />
                                @error('opening_balance') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <small class="text-muted d-block mt-1 fs-11">Starting outstanding payable amount. (Can only set on creation).</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fs-11 fw-bold text-600" for="supplier-notes">Notes</label>
                                <textarea wire:model="notes" class="form-control form-control-sm" id="supplier-notes" rows="2" placeholder="Credit term notes..."></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fs-11 fw-bold text-600" for="supplier-status">Status *</label>
                                <select wire:model="status" class="form-select form-select-sm" id="supplier-status">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" wire:click="resetForm" class="btn btn-falcon-default btn-sm">Cancel</button>
                                <button type="submit" class="btn btn-falcon-primary btn-sm">
                                    <span class="fas fa-save me-1"></span>Save Supplier
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <!-- Main Suppliers Listing Table -->
        <div class="{{ $showForm ? 'col-md-8' : 'col-md-12' }}">
            <div class="card border border-200">
                <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2 py-2">
                    <h5 class="mb-0 fs-13"><span class="fas fa-truck me-2 text-primary"></span>Supplier Directory</h5>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <!-- Category Filter -->
                        <select wire:model.live="categoryFilter" class="form-select form-select-sm" style="width: 160px;">
                            <option value="all">All Categories</option>
                            @foreach($availableCategories as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>

                        <!-- Search -->
                        <div class="input-group input-group-sm" style="width: 200px;">
                            <input wire:model.live.debounce.300ms="search" class="form-control" type="search" placeholder="Search suppliers..." />
                            <span class="input-group-text bg-white"><span class="fas fa-search text-400"></span></span>
                        </div>
                        @if(!$showForm && (auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_inventory')))
                            <button wire:click="create" class="btn btn-falcon-primary btn-sm text-nowrap">
                                <span class="fas fa-plus me-1"></span>Register Supplier
                            </button>
                        @endif
                    </div>
                </div>

                <div class="card-body p-0">
                    @if(session('success'))
                        <div class="alert alert-success border-2 d-flex align-items-center m-3" role="alert">
                            <div class="bg-success me-3 icon-item"><span class="fas fa-check-circle text-white fs-8"></span></div>
                            <p class="mb-0 flex-1">{{ session('success') }}</p>
                            <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="table-responsive scrollbar">
                        <table class="table table-sm table-striped fs-11 mb-0 align-middle">
                            <thead class="bg-200 text-900">
                                <tr>
                                    <th class="ps-3" style="width: 100px;">Code</th>
                                    <th>Supplier Name</th>
                                    <th>Categories</th>
                                    <th>Contact Person</th>
                                    <th>Mobile</th>
                                    <th>City</th>
                                    <th class="text-end" style="width: 120px;">Outstanding</th>
                                    <th class="text-center" style="width: 90px;">Status</th>
                                    <th class="text-end pe-3" style="width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($suppliers as $supp)
                                    <tr>
                                        <td class="ps-3 font-monospace fw-bold"><span class="badge badge-subtle-secondary fs-11">{{ $supp->supplier_code }}</span></td>
                                        <td class="fw-semi-bold text-900">
                                            <a href="{{ route('suppliers.ledger', $supp->id) }}">{{ $supp->name }}</a>
                                        </td>
                                        <td>
                                            @if($supp->categories->isNotEmpty())
                                                @php
                                                    $firstCats = $supp->categories->take(2);
                                                    $remaining = $supp->categories->count() - 2;
                                                @endphp
                                                @foreach($firstCats as $sc)
                                                    <span class="badge badge-subtle-primary rounded-pill me-1" style="font-size: 10px;">{{ $sc->name }}</span>
                                                @endforeach
                                                @if($remaining > 0)
                                                    <span class="badge badge-subtle-secondary rounded-pill" style="font-size: 10px;" title="{{ $supp->categories->slice(2)->pluck('name')->join(', ') }}">
                                                        +{{ $remaining }} more
                                                    </span>
                                                @endif
                                            @else
                                                <span class="text-muted fs-11">—</span>
                                            @endif
                                        </td>
                                        <td>{{ $supp->contact_person ?: '—' }}</td>
                                        <td class="font-monospace fs-11">{{ $supp->mobile_number }}</td>
                                        <td>{{ $supp->city ?: '—' }}</td>
                                        <td class="text-end font-monospace fw-bold text-danger">Rs. {{ number_format($supp->current_balance, 2) }}</td>
                                        <td class="text-center">
                                            <span class="badge badge-subtle-{{ $supp->status === 'Active' ? 'success' : 'secondary' }} rounded-pill">
                                                {{ $supp->status }}
                                            </span>
                                        </td>
                                        <td class="text-end px-3">
                                            <div class="d-flex justify-content-end gap-2">
                                                <a href="{{ route('suppliers.ledger', $supp->id) }}" class="btn btn-link p-0" title="Ledger Statement">
                                                    <span class="text-info fas fa-file-invoice-dollar"></span>
                                                </a>
                                                @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_inventory'))
                                                    <button wire:click="edit({{ $supp->id }})" class="btn btn-link p-0" title="Edit">
                                                        <span class="text-primary fas fa-edit"></span>
                                                    </button>
                                                    <button class="btn btn-link p-0" type="button" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal" wire:click="confirmDeletion({{ $supp->id }})" title="Delete">
                                                        <span class="text-danger fas fa-trash-alt"></span>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">No suppliers registered.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($suppliers->hasPages())
                    <div class="card-footer d-flex align-items-center justify-content-center bg-light">
                        {{ $suppliers->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div wire:ignore.self class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title text-white" id="deleteConfirmModalLabel">
                        <span class="fas fa-exclamation-triangle me-2"></span>Confirm Deletion
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-start">
                    <p class="mb-0 text-900">Are you sure you want to delete this supplier profile? Outstanding accounts payable balances must still be cleared manually.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-falcon-default btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button wire:click="deleteRecord" type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">
                        <span class="fas fa-trash-alt me-1"></span>Delete Supplier
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
