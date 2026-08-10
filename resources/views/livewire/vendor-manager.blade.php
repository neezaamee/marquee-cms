<div class="p-3">
    <!-- Flash Notifications -->
    @if(session('success'))
        <div class="alert alert-success border-2 d-flex align-items-center mb-3" role="alert">
            <i class="fas fa-check-circle me-2 fs-6"></i>
            <div class="flex-grow-1">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($showVendorModal)
        <!-- Falcon Card Form for Register / Edit Provider -->
        <div class="card mb-3">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <span class="fas fa-store me-2 text-primary"></span>
                    {{ $vendorId ? 'Edit Service Provider Profile' : 'Register New Service Provider' }}
                </h5>
                <button class="btn btn-falcon-default btn-sm" wire:click="$set('showVendorModal', false)">
                    <span class="fas fa-chevron-left me-1" data-fa-transform="shrink-4"></span> Back
                </button>
            </div>

            <div class="card-body bg-light">
                <form wire:submit.prevent="saveVendor">
                    <div class="row g-3">
                        
                        <!-- Basic Information Section -->
                        <div class="row navbar-vertical-label-wrapper mt-4 mb-2">
                            <div class="col-auto navbar-vertical-label text-primary">Basic Information</div>
                            <div class="col ps-0"><hr class="mb-0 navbar-vertical-divider" /></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semi-bold" for="name">Provider Name <span class="text-danger">*</span></label>
                            <input type="text" id="name" wire:model="name" class="form-control @error('name') is-invalid @enderror" placeholder="e.g. ABC Photography & Films" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semi-bold" for="vendor_type">Provider Type <span class="text-danger">*</span></label>
                            <select id="vendor_type" wire:model="vendor_type" class="form-select">
                                @foreach($vendorTypes as $vType)
                                    <option value="{{ $vType }}">{{ $vType }}</option>
                                @endforeach
                            </select>
                            @error('vendor_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semi-bold" for="status">Status <span class="text-danger">*</span></label>
                            <select id="status" wire:model="status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="suspended">Suspended</option>
                            </select>
                            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <!-- Contact Details Section -->
                        <div class="row navbar-vertical-label-wrapper mt-4 mb-2">
                            <div class="col-auto navbar-vertical-label text-primary">Contact & Address</div>
                            <div class="col ps-0"><hr class="mb-0 navbar-vertical-divider" /></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semi-bold" for="contact_person">Contact Person</label>
                            <input type="text" id="contact_person" wire:model="contact_person" class="form-control" placeholder="e.g. Muhammad Ahmad">
                            @error('contact_person') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semi-bold" for="phone">Primary Phone <span class="text-danger">*</span></label>
                            <input type="text" id="phone" wire:model="phone" class="form-control @error('phone') is-invalid @enderror" placeholder="0300-1234567" required>
                            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semi-bold" for="alternate_phone">Alternate Phone / WhatsApp</label>
                            <input type="text" id="alternate_phone" wire:model="alternate_phone" class="form-control" placeholder="0321-7654321">
                            @error('alternate_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semi-bold" for="email">Email Address</label>
                            <input type="email" id="email" wire:model="email" class="form-control @error('email') is-invalid @enderror" placeholder="provider@example.com">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-8">
                            <label class="form-label fw-semi-bold" for="address">Office / Shop Address</label>
                            <input type="text" id="address" wire:model="address" class="form-control" placeholder="Street, Plaza, Sector">
                            @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semi-bold" for="city">City</label>
                            <input type="text" id="city" wire:model="city" class="form-control">
                            @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semi-bold" for="branch_id">Branch Assignment (Optional)</label>
                            <select id="branch_id" wire:model="branch_id" class="form-select">
                                <option value="">-- All Marquee Branches (Tenant-wide) --</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semi-bold" for="tax_ntn">Tax NTN / STRN</label>
                            <input type="text" id="tax_ntn" wire:model="tax_ntn" class="form-control" placeholder="1234567-8">
                            @error('tax_ntn') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <!-- Banking & Payment Terms Section -->
                        <div class="row navbar-vertical-label-wrapper mt-4 mb-2">
                            <div class="col-auto navbar-vertical-label text-primary">Banking & Payment Terms</div>
                            <div class="col ps-0"><hr class="mb-0 navbar-vertical-divider" /></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semi-bold" for="bank_name">Bank Name</label>
                            <input type="text" id="bank_name" wire:model="bank_name" class="form-control" placeholder="e.g. Meezan Bank">
                            @error('bank_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semi-bold" for="account_title">Account Title</label>
                            <input type="text" id="account_title" wire:model="account_title" class="form-control" placeholder="Account Title">
                            @error('account_title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semi-bold" for="account_number_iban">Account Number / IBAN</label>
                            <input type="text" id="account_number_iban" wire:model="account_number_iban" class="form-control" placeholder="PK00 MEZN 0000...">
                            @error('account_number_iban') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semi-bold" for="opening_balance">Opening Balance (Rs.)</label>
                            <input type="number" step="0.01" id="opening_balance" wire:model="opening_balance" class="form-control">
                            @error('opening_balance') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semi-bold" for="payment_terms">Payment Terms</label>
                            <input type="text" id="payment_terms" wire:model="payment_terms" class="form-control" placeholder="Net 30">
                            @error('payment_terms') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semi-bold" for="notes">Notes / Comments</label>
                            <textarea id="notes" wire:model="notes" class="form-control" rows="3" placeholder="Internal notes, contract terms, or preferences..."></textarea>
                            @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 mt-4 text-end">
                            <button type="button" wire:click="$set('showVendorModal', false)" class="btn btn-falcon-default btn-sm me-2">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm px-4"><span class="fas fa-save me-1"></span> Save Service Provider</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @else
        <!-- Header Toolbar -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <h4 class="fw-bold mb-1"><i class="fas fa-store text-primary me-2"></i>Service Provider Directory & Partners</h4>
                <p class="text-secondary fs-12 mb-0">Manage contracted event service providers, contact directories, and financial accounts.</p>
            </div>
            <button wire:click="openCreateModal" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i> Register New Provider</button>
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
                            <option value="">-- All Provider Types --</option>
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
                            <th>Provider Name</th>
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
                                <td colspan="8" class="text-center py-4 text-muted">No service provider records found matching your criteria.</td>
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
    @endif
</div>
