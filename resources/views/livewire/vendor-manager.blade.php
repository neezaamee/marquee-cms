<div class="p-3">
    <!-- Flash Notifications -->
    @if(session('success'))
        <div class="alert alert-success border-2 d-flex align-items-center mb-3" role="alert">
            <i class="fas fa-check-circle me-2 fs-6"></i>
            <div class="flex-grow-1">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Header Toolbar -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h4 class="fw-bold mb-1"><i class="fas fa-store text-primary me-2"></i>Vendor Directory & Partners</h4>
            <p class="text-secondary fs-12 mb-0">Manage contracted event service providers, contact directories, and financial accounts.</p>
        </div>
        <button wire:click="openCreateModal" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i> Register New Vendor</button>
    </div>

    <!-- Filters Bar -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-3">
            <div class="row g-2">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input wire:model.live.debounce.300ms="search" type="text" class="form-control" placeholder="Search by name, code, contact person, phone, email...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select wire:model.live="filterType" class="form-select form-select-sm">
                        <option value="">-- All Vendor Types --</option>
                        @foreach($vendorTypes as $vType)
                            <option value="{{ $vType }}">{{ $vType }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select wire:model.live="filterStatus" class="form-select form-select-sm">
                        <option value="">-- All Statuses --</option>
                        <option value="active">Active Only</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
                <div class="col-md-2 text-end">
                    <button wire:click="$set('search', ''); $set('filterType', ''); $set('filterStatus', '');" class="btn btn-outline-secondary btn-sm w-100"><i class="fas fa-undo me-1"></i> Reset</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Vendors Table -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 fs-12">
                <thead class="bg-200">
                    <tr>
                        <th>Code</th>
                        <th>Vendor Name</th>
                        <th>Type</th>
                        <th>Contact Person</th>
                        <th>Phone</th>
                        <th>Current Balance</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vendors as $vendor)
                        <tr>
                            <td class="fw-bold font-monospace text-primary">{{ $vendor->vendor_code }}</td>
                            <td class="fw-bold text-dark">
                                <a href="{{ route('vendors.show', $vendor->id) }}" class="text-dark">{{ $vendor->name }}</a>
                                @if($vendor->city)
                                    <div class="text-secondary fs-11"><i class="fas fa-map-marker-alt me-1"></i>{{ $vendor->city }}</div>
                                @endif
                            </td>
                            <td><span class="badge badge-subtle-info">{{ $vendor->vendor_type }}</span></td>
                            <td>{{ $vendor->contact_person ?: '—' }}</td>
                            <td class="font-monospace"><i class="fas fa-phone me-1 text-muted"></i>{{ $vendor->phone }}</td>
                            <td class="fw-bold {{ $vendor->current_balance > 0 ? 'text-danger' : 'text-success' }}">
                                Rs. {{ number_format($vendor->current_balance) }}
                            </td>
                            <td>
                                @if($vendor->status === 'active')
                                    <span class="badge badge-subtle-success">Active</span>
                                @elseif($vendor->status === 'suspended')
                                    <span class="badge badge-subtle-warning">Suspended</span>
                                @else
                                    <span class="badge badge-subtle-secondary">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('vendors.show', $vendor->id) }}" class="btn btn-falcon-default btn-xs" title="View Profile"><i class="fas fa-eye text-info"></i></a>
                                    <button wire:click="editVendor({{ $vendor->id }})" class="btn btn-falcon-default btn-xs" title="Edit"><i class="fas fa-edit text-primary"></i></button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No vendor records found matching your criteria.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($vendors->hasPages())
            <div class="card-footer bg-light py-2">
                {{ $vendors->links() }}
            </div>
        @endif
    </div>

    <!-- Vendor Registration & Edit Modal -->
    @if($showVendorModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" role="dialog">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-primary text-white py-2">
                        <h5 class="modal-title fw-bold fs-14">
                            <i class="fas fa-store me-2"></i>{{ $vendorId ? 'Edit Vendor Profile' : 'Register New Vendor' }}
                        </h5>
                        <button wire:click="$set('showVendorModal', false)" type="button" class="btn-close btn-close-white" aria-label="Close"></button>
                    </div>
                    <form wire:submit.prevent="saveVendor">
                        <div class="modal-body p-3 fs-12">
                            <div class="row g-2">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-bold">Vendor Name <span class="text-danger">*</span></label>
                                    <input type="text" wire:model="name" class="form-control form-control-sm @error('name') is-invalid @enderror" placeholder="e.g. ABC Photography & Films">
                                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-bold">Vendor Type <span class="text-danger">*</span></label>
                                    <select wire:model="vendor_type" class="form-select form-select-sm">
                                        @foreach($vendorTypes as $vType)
                                            <option value="{{ $vType }}">{{ $vType }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-bold">Contact Person</label>
                                    <input type="text" wire:model="contact_person" class="form-control form-control-sm" placeholder="e.g. Muhammad Ahmad">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-bold">Primary Phone <span class="text-danger">*</span></label>
                                    <input type="text" wire:model="phone" class="form-control form-control-sm @error('phone') is-invalid @enderror" placeholder="0300-1234567">
                                    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-bold">Alternate Phone / WhatsApp</label>
                                    <input type="text" wire:model="alternate_phone" class="form-control form-control-sm" placeholder="0321-7654321">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-bold">Email Address</label>
                                    <input type="email" wire:model="email" class="form-control form-control-sm @error('email') is-invalid @enderror" placeholder="vendor@example.com">
                                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-8 mb-2">
                                    <label class="form-label fw-bold">Office / Shop Address</label>
                                    <input type="text" wire:model="address" class="form-control form-control-sm" placeholder="Street, Plaza, Sector">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label fw-bold">City</label>
                                    <input type="text" wire:model="city" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-bold">Branch Assignment (Optional)</label>
                                    <select wire:model="branch_id" class="form-select form-select-sm">
                                        <option value="">-- All Marquee Branches (Tenant-wide) --</option>
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-bold">Tax NTN / STRN</label>
                                    <input type="text" wire:model="tax_ntn" class="form-control form-control-sm" placeholder="1234567-8">
                                </div>

                                <div class="col-12 mt-2">
                                    <h6 class="fw-bold text-primary border-bottom pb-1 mb-2 fs-12"><i class="fas fa-university me-1"></i> Banking & Payment Terms</h6>
                                </div>

                                <div class="col-md-4 mb-2">
                                    <label class="form-label fw-bold">Bank Name</label>
                                    <input type="text" wire:model="bank_name" class="form-control form-control-sm" placeholder="e.g. Meezan Bank">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label fw-bold">Account Title</label>
                                    <input type="text" wire:model="account_title" class="form-control form-control-sm" placeholder="Account Title">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label fw-bold">Account Number / IBAN</label>
                                    <input type="text" wire:model="account_number_iban" class="form-control form-control-sm" placeholder="PK00 MEZN 0000...">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label fw-bold">Opening Balance (Rs.)</label>
                                    <input type="number" step="0.01" wire:model="opening_balance" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label fw-bold">Payment Terms</label>
                                    <input type="text" wire:model="payment_terms" class="form-control form-control-sm" placeholder="Net 30">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label fw-bold">Status <span class="text-danger">*</span></label>
                                    <select wire:model="status" class="form-select form-select-sm">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="suspended">Suspended</option>
                                    </select>
                                </div>
                                <div class="col-12 mb-2">
                                    <label class="form-label fw-bold">Notes / Comments</label>
                                    <textarea wire:model="notes" class="form-control form-control-sm" rows="2" placeholder="Internal notes, contract terms, or preferences..."></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-light py-2">
                            <button wire:click="$set('showVendorModal', false)" type="button" class="btn btn-secondary btn-sm px-3">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm px-4"><i class="fas fa-save me-1"></i> Save Vendor</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
