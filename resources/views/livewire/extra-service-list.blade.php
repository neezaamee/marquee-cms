<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><span class="fas fa-puzzle-piece me-2 text-primary"></span>Add-ons & Extra Services</h5>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <!-- Search -->
                <div class="input-group input-group-sm" style="max-width: 200px;">
                    <input wire:model.live.debounce.300ms="search" class="form-control" type="search" placeholder="Search add-ons..." />
                    <span class="input-group-text"><span class="fas fa-search"></span></span>
                </div>

                <!-- Status Filter -->
                <select wire:model.live="filterStatus" class="form-select form-select-sm" style="min-width:130px">
                    <option value="">All Statuses</option>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>

                @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_settings'))
                    <a class="btn btn-falcon-primary btn-sm text-nowrap" href="{{ route('extra-services.create') }}">
                        <span class="fas fa-plus me-1" data-fa-transform="shrink-3"></span> Add Add-on
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
                            <th class="px-3" style="width: 80px;">#</th>
                            <th>Add-on / Service Name</th>
                            <th>Default Price</th>
                            <th class="text-center" style="width: 150px;">Status</th>
                            <th class="text-end px-3" style="width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($extraServices as $item)
                            <tr>
                                <td class="px-3">
                                    <span class="font-monospace text-secondary">#{{ $item->id }}</span>
                                </td>
                                <td class="fw-semi-bold text-800">
                                    {{ $item->service_name }}
                                </td>
                                <td class="font-monospace text-800">
                                    Rs. {{ number_format($item->default_price, 2) }}
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-subtle-{{ $item->status === 'Active' ? 'success' : 'secondary' }} rounded-pill">{{ $item->status }}</span>
                                </td>
                                <td class="text-end px-3">
                                    <div class="d-flex justify-content-end gap-2">
                                        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_settings'))
                                            <a class="btn btn-link p-0" href="{{ route('extra-services.edit', $item->id) }}" data-bs-toggle="tooltip" title="Edit">
                                                <span class="text-primary fas fa-edit"></span>
                                            </a>
                                            <button class="btn btn-link p-0" type="button" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal" wire:click="confirmDeletion({{ $item->id }})" title="Delete Add-on">
                                                <span class="text-danger fas fa-trash-alt"></span>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <span class="fas fa-puzzle-piece fa-2x mb-2 d-block"></span>
                                    No add-ons found in catalog.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($extraServices->hasPages())
            <div class="card-footer d-flex align-items-center justify-content-center bg-light">
                {{ $extraServices->links() }}
            </div>
        @endif
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
                    <p class="mb-0 text-900">Are you sure you want to delete this add-on/extra service? This action will permanently remove it from the catalog.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-falcon-default btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button wire:click="deleteRecord" type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">
                        <span class="fas fa-trash-alt me-1"></span>Delete Add-on
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
