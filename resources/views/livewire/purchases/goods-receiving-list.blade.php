<div>
    <div class="card border border-200 shadow-none">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><span class="fas fa-truck-loading me-2 text-primary"></span>Goods Receiving Notes (GRN)</h5>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <!-- Supplier Filter -->
                <select wire:model.live="filterSupplier" class="form-select form-select-sm" style="max-width: 150px;">
                    <option value="">All Suppliers</option>
                    @foreach($suppliers as $supp)
                        <option value="{{ $supp->id }}">{{ $supp->name }}</option>
                    @endforeach
                </select>

                <!-- Search -->
                <div class="input-group input-group-sm" style="max-width: 180px;">
                    <input wire:model.live.debounce.300ms="search" class="form-control" type="search" placeholder="GRN Number..." />
                    <span class="input-group-text"><span class="fas fa-search"></span></span>
                </div>

                @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_inventory'))
                    <a class="btn btn-falcon-primary btn-sm text-nowrap" href="{{ route('goods-receipts.create') }}">
                        <span class="fas fa-plus me-1"></span>New GRN Entry
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

            <div class="table-responsive scrollbar">
                <table class="table table-sm table-striped fs-10 mb-0 align-middle">
                    <thead class="bg-200 text-900">
                        <tr>
                            <th class="px-3" style="width: 150px;">GRN Number</th>
                            <th>Reference PO</th>
                            <th>Supplier</th>
                            <th>Branch</th>
                            <th>Received Date</th>
                            <th>Notes</th>
                            <th class="text-end px-3" style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($goodsReceipts as $grn)
                            <tr>
                                <td class="px-3 font-monospace fw-bold"><span class="badge badge-subtle-success fs-11">{{ $grn->grn_number }}</span></td>
                                <td class="font-monospace fw-semi-bold">
                                    <a href="{{ route('purchase-orders.edit', $grn->purchase_order_id) }}">#{{ $grn->purchaseOrder->po_number }}</a>
                                </td>
                                <td class="fw-semi-bold">{{ $grn->supplier->name ?? '—' }}</td>
                                <td>{{ $grn->branch->name ?? 'Head Office' }}</td>
                                <td>{{ $grn->received_date->format('Y-m-d') }}</td>
                                <td class="text-muted">{{ $grn->notes ?: '—' }}</td>
                                <td class="text-end px-3">
                                    <a href="{{ route('goods-receipts.show', $grn->id) }}" class="btn btn-link p-0" title="View GRN Details">
                                        <span class="text-info fas fa-eye"></span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No GRNs recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($goodsReceipts->hasPages())
            <div class="card-footer d-flex align-items-center justify-content-center bg-light">
                {{ $goodsReceipts->links() }}
            </div>
        @endif
    </div>
</div>
