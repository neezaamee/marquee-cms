<div>
    @if(session('success'))
        <div class="alert alert-success border-2 d-flex align-items-center mb-3" role="alert">
            <i class="fas fa-check-circle me-2 fs-6"></i>
            <div class="flex-grow-1">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($showServiceModal)
        <!-- Falcon Card Form for Add / Edit Service -->
        <div class="card mb-3">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <span class="fas fa-concierge-bell me-2 text-primary"></span>
                    {{ $serviceId ? 'Edit Service Details' : 'Add New Service to Catalogue' }}
                </h6>
                <button class="btn btn-falcon-default btn-sm" wire:click="$set('showServiceModal', false)">
                    <span class="fas fa-chevron-left me-1" data-fa-transform="shrink-4"></span> Back
                </button>
            </div>

            <div class="card-body bg-light">
                <form wire:submit.prevent="saveService">
                    <div class="row g-3">
                        @if(!$vendor)
                            <div class="col-12">
                                <label class="form-label fw-semi-bold" for="selectedVendorId">Select Service Provider <span class="text-danger">*</span></label>
                                <select id="selectedVendorId" wire:model="selectedVendorId" class="form-select @error('selectedVendorId') is-invalid @enderror">
                                    <option value="">-- Choose Service Provider --</option>
                                    @foreach($vendors as $v)
                                        <option value="{{ $v->id }}">{{ $v->name }} ({{ $v->vendor_type }})</option>
                                    @endforeach
                                </select>
                                @error('selectedVendorId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        @endif

                        <div class="col-md-6">
                            <label class="form-label fw-semi-bold" for="service_name">Service Name <span class="text-danger">*</span></label>
                            <input type="text" id="service_name" wire:model="service_name" class="form-control @error('service_name') is-invalid @enderror" placeholder="e.g. Drone Videography Package" required>
                            @error('service_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semi-bold" for="unit">Billing Unit <span class="text-danger">*</span></label>
                            <select id="unit" wire:model="unit" class="form-select">
                                <option value="Event">Event</option>
                                <option value="Day">Day</option>
                                <option value="Session">Session</option>
                                <option value="Hour">Hour</option>
                                <option value="Piece">Piece / Item</option>
                            </select>
                            @error('unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semi-bold" for="default_sale_price">Default Sale Price (Rs.) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" id="default_sale_price" wire:model="default_sale_price" class="form-control @error('default_sale_price') is-invalid @enderror" required>
                            @error('default_sale_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semi-bold" for="status">Status <span class="text-danger">*</span></label>
                            <select id="status" wire:model="status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semi-bold" for="description">Description / Scope</label>
                            <textarea id="description" wire:model="description" class="form-control @error('description') is-invalid @enderror" rows="3" placeholder="Details of equipment, coverage, or deliverables..."></textarea>
                            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 mt-4 text-end">
                            <button type="button" wire:click="$set('showServiceModal', false)" class="btn btn-falcon-default btn-sm me-2">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm px-4"><span class="fas fa-save me-1"></span> Save Service</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @else
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h6 class="fw-bold mb-0"><i class="fas fa-concierge-bell text-primary me-2"></i>Services Catalogue</h6>
                <div class="text-secondary fs-11">Services and items provided by contracted service providers.</div>
            </div>
            <button wire:click="openCreateModal" class="btn btn-primary btn-xs"><i class="fas fa-plus me-1"></i> Add Service</button>
        </div>

        <!-- Services Table -->
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 fs-12">
                    <thead class="bg-200">
                        <tr>
                            <th>Code</th>
                            @if(!$vendor) <th>Service Provider</th> @endif
                            <th>Service Name</th>
                            <th>Billing Unit</th>
                            <th>Default Price</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($services as $srv)
                            <tr>
                                <td class="fw-bold font-monospace text-primary">{{ $srv->service_code }}</td>
                                @if(!$vendor)
                                    <td class="fw-semibold">{{ $srv->vendor->name ?? '—' }}</td>
                                @endif
                                <td class="fw-bold">{{ $srv->service_name }}</td>
                                <td><span class="badge badge-subtle-secondary">{{ $srv->unit }}</span></td>
                                <td class="fw-bold text-dark">Rs. {{ number_format($srv->default_sale_price) }}</td>
                                <td>
                                    @if($srv->status === 'active')
                                        <span class="badge badge-subtle-success">Active</span>
                                    @else
                                        <span class="badge badge-subtle-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <button wire:click="editService({{ $srv->id }})" class="btn btn-falcon-default btn-xs"><i class="fas fa-edit text-primary"></i> Edit</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $vendor ? 6 : 7 }}" class="text-center py-4 text-muted">No services registered.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
