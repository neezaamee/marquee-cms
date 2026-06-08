<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><span class="fas fa-utensils me-2 text-primary"></span>Menu Items</h5>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <!-- Search -->
                <div class="input-group input-group-sm" style="max-width: 180px;">
                    <input wire:model.live.debounce.300ms="search" class="form-control" type="search" placeholder="Search items..." />
                    <span class="input-group-text"><span class="fas fa-search"></span></span>
                </div>

                <!-- Category Filter -->
                <select wire:model.live="filterCategory" class="form-select form-select-sm" style="min-width:140px">
                    <option value="">All Categories</option>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}">{{ $c->category_name }}</option>
                    @endforeach
                </select>

                <!-- Status Filter -->
                <select wire:model.live="filterStatus" class="form-select form-select-sm" style="min-width:120px">
                    <option value="">All Statuses</option>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>

                @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('create_menus'))
                    <a class="btn btn-falcon-primary btn-sm text-nowrap" href="{{ route('menu-items.create') }}">
                        <span class="fas fa-plus me-1" data-fa-transform="shrink-3"></span> Add Menu Item
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
                            <th class="px-3" style="width: 50px;">Image</th>
                            <th>Code</th>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th>Unit</th>
                            <th>Base Cost</th>
                            <th>Selling Price</th>
                            <th class="text-center">Status</th>
                            <th class="text-end px-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($menuItems as $item)
                            <tr>
                                <td class="px-3">
                                    <img src="{{ $item->image_url }}" alt="{{ $item->item_name }}" class="rounded border" style="width: 32px; height: 32px; object-fit: cover;">
                                </td>
                                <td>
                                    <span class="badge badge-subtle-primary fs-11 font-monospace">{{ $item->item_code }}</span>
                                </td>
                                <td class="fw-semi-bold">
                                    <a href="{{ route('menu-items.show', $item->id) }}">{{ $item->item_name }}</a>
                                </td>
                                <td>
                                    <span class="text-800 fw-semi-bold">{{ $item->category->category_name }}</span>
                                </td>
                                <td>
                                    <span class="badge badge-subtle-info fs-11">{{ $item->unit }}</span>
                                </td>
                                <td class="font-monospace text-secondary">
                                    {{ $item->base_cost ? 'PKR ' . number_format($item->base_cost, 2) : '—' }}
                                </td>
                                <td class="font-monospace text-success fw-semi-bold">
                                    PKR {{ number_format($item->selling_price, 2) }}
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-subtle-{{ $item->status === 'Active' ? 'success' : 'secondary' }} rounded-pill">{{ $item->status }}</span>
                                </td>
                                <td class="text-end px-3">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a class="btn btn-link p-0" href="{{ route('menu-items.show', $item->id) }}" data-bs-toggle="tooltip" title="View Details">
                                            <span class="text-info fas fa-eye"></span>
                                        </a>
                                        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('edit_menus'))
                                            <a class="btn btn-link p-0" href="{{ route('menu-items.edit', $item->id) }}" data-bs-toggle="tooltip" title="Edit">
                                                <span class="text-primary fas fa-edit"></span>
                                            </a>
                                        @endif
                                        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('delete_menus'))
                                            <button class="btn btn-link p-0" type="button" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal" wire:click="confirmDeletion({{ $item->id }})" title="Delete Item">
                                                <span class="text-danger fas fa-trash-alt"></span>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <span class="fas fa-hamburger fa-2x mb-2 d-block"></span>
                                    No menu items found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($menuItems->hasPages())
            <div class="card-footer d-flex align-items-center justify-content-center bg-light">
                {{ $menuItems->links() }}
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
                    <p class="mb-0 text-900">Are you sure you want to delete this menu item? This will soft-delete the record and is reversible, but it will remove it from any existing packages and search listings.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-falcon-default btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button wire:click="deleteRecord" type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">
                        <span class="fas fa-trash-alt me-1"></span>Delete Menu Item
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
