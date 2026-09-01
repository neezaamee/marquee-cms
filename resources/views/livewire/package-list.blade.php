<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><span class="fas fa-cubes me-2 text-primary"></span>Packages Management</h5>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <!-- Search -->
                <div class="input-group input-group-sm" style="max-width: 180px;">
                    <input wire:model.live.debounce.300ms="search" class="form-control" type="search" placeholder="Search packages..." />
                    <span class="input-group-text"><span class="fas fa-search"></span></span>
                </div>

                <!-- Type Filter -->
                <select wire:model.live="filterType" class="form-select form-select-sm" style="min-width:120px">
                    <option value="">All Types</option>
                    <option value="Silver">Silver</option>
                    <option value="Gold">Gold</option>
                    <option value="Platinum">Platinum</option>
                    <option value="VIP">VIP</option>
                    <option value="Custom">Custom</option>
                </select>

                <!-- Status Filter -->
                <select wire:model.live="filterStatus" class="form-select form-select-sm" style="min-width:120px">
                    <option value="">All Statuses</option>
                    <option value="Draft">Draft</option>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                    <option value="Archived">Archived</option>
                </select>

                @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('create_packages'))
                    <a class="btn btn-falcon-primary btn-sm text-nowrap" href="{{ route('packages.create') }}">
                        <span class="fas fa-plus me-1" data-fa-transform="shrink-3"></span> Add Package
                    </a>
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
            @if(session('error'))
                <div class="alert alert-danger border-2 d-flex align-items-center m-3 animate__animated animate__fadeIn" role="alert">
                    <div class="bg-danger me-3 icon-item"><span class="fas fa-times-circle text-white fs-8"></span></div>
                    <p class="mb-0 flex-1">{{ session('error') }}</p>
                    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger border-2 d-flex align-items-center m-3" role="alert">
                    <div class="bg-danger me-3 icon-item"><span class="fas fa-times-circle text-white fs-8"></span></div>
                    <p class="mb-0 flex-1">{{ session('error') }}</p>
                    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="table-responsive scrollbar">
                <table class="table table-sm table-striped fs-10 mb-0 align-middle">
                    <thead class="bg-200 text-900">
                        <tr>
                            <th>Code</th>
                            <th>Package Name</th>
                            <th>Tier Type</th>
                            <th>Guests Allowed</th>
                            <th>Per Plate Price</th>
                            <th>Seasonal Availability</th>
                            <th>Items Count</th>
                            <th class="text-center">Status</th>
                            <th class="text-end px-3" style="width: 180px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($packages as $item)
                            <tr>
                                <td>
                                    <span class="badge badge-subtle-primary fs-11 font-monospace">{{ $item->package_code }}</span>
                                </td>
                                <td class="fw-semi-bold">
                                    <a href="{{ route('packages.preview', $item->id) }}">{{ $item->package_name }}</a>
                                </td>
                                <td>
                                    <span class="text-secondary fw-semi-bold">{{ $item->package_type }}</span>
                                </td>
                                <td>
                                    {{ $item->minimum_guests }} - {{ $item->maximum_guests ?: 'Unlimited' }}
                                </td>
                                <td class="font-monospace text-success fw-semi-bold">
                                    PKR {{ number_format($item->per_plate_price, 2) }}
                                </td>
                                <td>
                                    @if($item->seasonal_package)
                                        @if($item->isSeasonalActive())
                                            <span class="badge badge-subtle-success rounded-pill" data-bs-toggle="tooltip" title="{{ $item->season_start_date->format('M d, Y') }} to {{ $item->season_end_date->format('M d, Y') }}">
                                                <span class="fas fa-snowflake me-1"></span>Seasonal (Active)
                                            </span>
                                        @else
                                            <span class="badge badge-subtle-warning rounded-pill" data-bs-toggle="tooltip" title="{{ $item->season_start_date->format('M d, Y') }} to {{ $item->season_end_date->format('M d, Y') }}">
                                                <span class="fas fa-snowflake me-1"></span>Seasonal (Expired)
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-muted fs-11">Standard</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-subtle-secondary rounded-pill font-monospace fs-11">
                                        {{ $item->menuItems->count() }} items
                                    </span>
                                </td>
                                <td class="text-center">
                                    @php
                                        $statusClass = 'secondary';
                                        if($item->status === 'Active') $statusClass = 'success';
                                        elseif($item->status === 'Draft') $statusClass = 'info';
                                        elseif($item->status === 'Archived') $statusClass = 'warning';
                                    @endphp
                                    <span class="badge badge-subtle-{{ $statusClass }} rounded-pill">{{ $item->status }}</span>
                                </td>
                                <td class="text-end px-3">
                                    <div class="d-flex justify-content-end gap-2 align-items-center">
                                        <a class="btn btn-link p-0" href="{{ route('packages.preview', $item->id) }}" data-bs-toggle="tooltip" title="Preview Details">
                                            <span class="text-info fas fa-eye"></span>
                                        </a>
                                        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('edit_packages'))
                                            <a class="btn btn-link p-0" href="{{ route('packages.builder', $item->id) }}" data-bs-toggle="tooltip" title="Build Package Menu">
                                                <span class="text-success fas fa-utensils"></span>
                                            </a>
                                            <a class="btn btn-link p-0" href="{{ route('packages.edit', $item->id) }}" data-bs-toggle="tooltip" title="Edit Metadata">
                                                <span class="text-primary fas fa-edit"></span>
                                            </a>
                                            <button class="btn btn-link p-0" type="button" data-bs-toggle="modal" data-bs-target="#cloneModal" wire:click="setupClone({{ $item->id }})" title="Clone / Duplicate Package">
                                                <span class="text-secondary fas fa-copy"></span>
                                            </button>
                                        @endif
                                        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('delete_packages'))
                                            <button class="btn btn-link p-0" type="button" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal" wire:click="confirmDeletion({{ $item->id }})" title="Delete Package">
                                                <span class="text-danger fas fa-trash-alt"></span>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <span class="fas fa-cubes fa-2x mb-2 d-block"></span>
                                    No packages found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($packages->hasPages())
            <div class="card-footer d-flex align-items-center justify-content-center bg-light">
                {{ $packages->links() }}
            </div>
        @endif
    </div>

    <!-- Clone Modal -->
    <div wire:ignore.self class="modal fade" id="cloneModal" tabindex="-1" aria-labelledby="cloneModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white" id="cloneModalLabel">
                        <span class="fas fa-copy me-2"></span>Duplicate Package
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form wire:submit.prevent="clonePackage">
                    <div class="modal-body text-start">
                        <div class="mb-3">
                            <label class="form-label text-900" for="cloneName">New Package Name *</label>
                            <input wire:model="cloneName" type="text" class="form-control @error('cloneName') is-invalid @enderror" id="cloneName" required>
                            @error('cloneName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-900" for="cloneCode">New Package Code *</label>
                            <input wire:model="cloneCode" type="text" class="form-control @error('cloneCode') is-invalid @enderror" id="cloneCode" required>
                            @error('cloneCode') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text fs-11 text-500">Short unique code identifying the duplicated package.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-falcon-default btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <span class="fas fa-check me-1"></span>Duplicate Package
                        </button>
                    </div>
                </form>
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
                    <p class="mb-0 text-900">Are you sure you want to delete this package? This action will soft-delete the record and is reversible, but it will make it unavailable for future event bookings.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-falcon-default btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button wire:click="deleteRecord" type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">
                        <span class="fas fa-trash-alt me-1"></span>Delete Package
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const cloneModalEl = document.getElementById('cloneModal');
        const cloneModal = bootstrap.Modal.getOrCreateInstance(cloneModalEl);
        
        window.addEventListener('close-clone-modal', () => {
            cloneModal.hide();
        });
    });
</script>
