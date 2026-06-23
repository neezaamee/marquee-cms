<div>
    <div class="card border border-200 shadow-none">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><span class="fas fa-file-signature me-2 text-primary"></span>Purchase Orders</h5>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <!-- Supplier Filter -->
                <select wire:model.live="filterSupplier" class="form-select form-select-sm" style="max-width: 150px;">
                    <option value="">All Suppliers</option>
                    @foreach($suppliers as $supp)
                        <option value="{{ $supp->id }}">{{ $supp->name }}</option>
                    @endforeach
                </select>

                <!-- Status Filter -->
                <select wire:model.live="filterStatus" class="form-select form-select-sm" style="max-width: 150px;">
                    <option value="">All Statuses</option>
                    <option value="Draft">Draft</option>
                    <option value="Approved">Approved</option>
                    <option value="Partially Received">Partially Received</option>
                    <option value="Completed">Completed</option>
                    <option value="Cancelled">Cancelled</option>
                </select>

                <!-- Search -->
                <div class="input-group input-group-sm" style="max-width: 180px;">
                    <input wire:model.live.debounce.300ms="search" class="form-control" type="search" placeholder="PO Number..." />
                    <span class="input-group-text"><span class="fas fa-search"></span></span>
                </div>

                @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_inventory'))
                    <a class="btn btn-falcon-primary btn-sm text-nowrap" href="{{ route('purchase-orders.create') }}">
                        <span class="fas fa-plus me-1"></span>New PO
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
                            <th class="px-3" style="width: 150px;">PO Number</th>
                            <th>Supplier</th>
                            <th>Branch</th>
                            <th>Order Date</th>
                            <th>Expected Delivery</th>
                            <th class="text-end" style="width: 120px;">PO Total</th>
                            <th class="text-center" style="width: 150px;">Status</th>
                            <th class="text-end px-3" style="width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchaseOrders as $po)
                            <tr>
                                <td class="px-3 font-monospace fw-bold"><span class="badge badge-subtle-secondary fs-11">{{ $po->po_number }}</span></td>
                                <td class="fw-semi-bold">{{ $po->supplier->name ?? '—' }}</td>
                                <td>{{ $po->branch->name ?? 'Head Office' }}</td>
                                <td>{{ $po->order_date->format('Y-m-d') }}</td>
                                <td>{{ $po->expected_delivery_date ? $po->expected_delivery_date->format('Y-m-d') : '—' }}</td>
                                <td class="text-end font-monospace">Rs. {{ number_format($po->total_amount, 2) }}</td>
                                <td class="text-center">
                                    @php
                                        $statuses = [
                                            'Draft' => 'secondary',
                                            'Approved' => 'primary',
                                            'Partially Received' => 'warning',
                                            'Completed' => 'success',
                                            'Cancelled' => 'danger'
                                        ];
                                        $sc = $statuses[$po->status] ?? 'dark';
                                    @endphp
                                    <span class="badge badge-subtle-{{ $sc }} rounded-pill">{{ $po->status }}</span>
                                </td>
                                <td class="text-end px-3">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="{{ route('purchase-orders.edit', $po->id) }}" class="btn btn-link p-0" title="{{ $po->status === 'Draft' ? 'Edit Draft' : 'View Details' }}">
                                            <span class="text-primary fas fa-{{ $po->status === 'Draft' ? 'edit' : 'eye' }}"></span>
                                        </a>
                                        @if($po->status === 'Draft' && (auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_inventory')))
                                            <button class="btn btn-link p-0" type="button" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal" wire:click="confirmDeletion({{ $po->id }})" title="Delete">
                                                <span class="text-danger fas fa-trash-alt"></span>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">No purchase orders found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($purchaseOrders->hasPages())
            <div class="card-footer d-flex align-items-center justify-content-center bg-light">
                {{ $purchaseOrders->links() }}
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
                    <p class="mb-0 text-900">Are you sure you want to remove this draft purchase order? This action will permanently remove this draft.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-falcon-default btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button wire:click="deleteRecord" type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">
                        <span class="fas fa-trash-alt me-1"></span>Delete PO
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
