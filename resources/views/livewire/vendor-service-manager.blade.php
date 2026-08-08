<div>
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h6 class="fw-bold mb-0"><i class="fas fa-concierge-bell text-primary me-2"></i>Vendor Services Catalogue</h6>
            <div class="text-secondary fs-11">Services and items provided by contracted vendors.</div>
        </div>
        <button wire:click="openCreateModal" class="btn btn-primary btn-xs"><i class="fas fa-plus me-1"></i> Add Service</button>
    </div>

    @if(session('success'))
        <div class="alert alert-success py-2 fs-12 mb-3">{{ session('success') }}</div>
    @endif

    <!-- Services Table -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 fs-12">
                <thead class="bg-200">
                    <tr>
                        <th>Code</th>
                        @if(!$vendor) <th>Vendor</th> @endif
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
                            <td colspan="{{ $vendor ? 6 : 7 }}" class="text-center py-4 text-muted">No vendor services registered.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Service Modal -->
    @if($showServiceModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-primary text-white py-2">
                        <h6 class="modal-title fw-bold fs-13"><i class="fas fa-concierge-bell me-2"></i>{{ $serviceId ? 'Edit Vendor Service' : 'Add Vendor Service' }}</h6>
                        <button wire:click="$set('showServiceModal', false)" type="button" class="btn-close btn-close-white" aria-label="Close"></button>
                    </div>
                    <form wire:submit.prevent="saveService">
                        <div class="modal-body p-3 fs-12">
                            @if(!$vendor)
                                <div class="mb-2">
                                    <label class="form-label fw-bold">Select Vendor <span class="text-danger">*</span></label>
                                    <select wire:model="selectedVendorId" class="form-select form-select-sm">
                                        <option value="">-- Choose Vendor --</option>
                                        @foreach($vendors as $v)
                                            <option value="{{ $v->id }}">{{ $v->name }} ({{ $v->vendor_type }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="mb-2">
                                <label class="form-label fw-bold">Service Name <span class="text-danger">*</span></label>
                                <input type="text" wire:model="service_name" class="form-control form-control-sm @error('service_name') is-invalid @enderror" placeholder="e.g. Drone Videography Package">
                                @error('service_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Billing Unit <span class="text-danger">*</span></label>
                                    <select wire:model="unit" class="form-select form-select-sm">
                                        <option value="Event">Event</option>
                                        <option value="Day">Day</option>
                                        <option value="Session">Session</option>
                                        <option value="Hour">Hour</option>
                                        <option value="Piece">Piece / Item</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Default Sale Price (Rs.)</label>
                                    <input type="number" step="0.01" wire:model="default_sale_price" class="form-control form-control-sm">
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-bold">Status <span class="text-danger">*</span></label>
                                <select wire:model="status" class="form-select form-select-sm">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-bold">Description / Scope</label>
                                <textarea wire:model="description" class="form-control form-control-sm" rows="2" placeholder="Details of equipment, coverage, or deliverables..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer bg-light py-2">
                            <button wire:click="$set('showServiceModal', false)" type="button" class="btn btn-secondary btn-sm px-3">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm px-4"><i class="fas fa-save me-1"></i> Save Service</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
