<div>
    <div class="card border border-200 shadow-none">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><span class="fas fa-undo me-2 text-primary"></span>Purchase Returns</h5>
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
                    <input wire:model.live.debounce.300ms="search" class="form-control" type="search" placeholder="Return Number..." />
                    <span class="input-group-text"><span class="fas fa-search"></span></span>
                </div>

                @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_inventory'))
                    <a class="btn btn-falcon-primary btn-sm text-nowrap" href="{{ route('purchase-returns.create') }}">
                        <span class="fas fa-plus me-1"></span>New Purchase Return
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
                            <th class="px-3" style="width: 150px;">Return Number</th>
                            <th>Reference Invoice</th>
                            <th>Supplier</th>
                            <th>Branch</th>
                            <th>Return Date</th>
                            <th class="text-end" style="width: 120px;">Net Amount</th>
                            <th>Posted JV</th>
                            <th class="text-center" style="width: 120px;">Status</th>
                            <th class="text-end px-3" style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchaseReturns as $ret)
                            <tr>
                                <td class="px-3 font-monospace fw-bold"><span class="badge badge-subtle-secondary fs-11">{{ $ret->return_number }}</span></td>
                                <td class="font-monospace">
                                    @if($ret->purchaseInvoice)
                                        <a href="{{ route('purchase-invoices.edit', $ret->purchase_invoice_id) }}">#{{ $ret->purchaseInvoice->invoice_number }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="fw-semi-bold">{{ $ret->supplier->name ?? '—' }}</td>
                                <td>{{ $ret->branch->name ?? 'Head Office' }}</td>
                                <td>{{ $ret->return_date->format('Y-m-d') }}</td>
                                <td class="text-end font-monospace fw-bold">Rs. {{ number_format($ret->net_amount, 2) }}</td>
                                <td class="font-monospace">
                                    @if($ret->journalVoucher)
                                        <span class="badge bg-light text-primary"><span class="fas fa-book me-1"></span>{{ $ret->journalVoucher->voucher_no }}</span>
                                    @else
                                        <span class="text-muted">Not Posted</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @php
                                        $statuses = [
                                            'Draft' => 'secondary',
                                            'Posted' => 'success',
                                            'Cancelled' => 'danger'
                                        ];
                                        $sc = $statuses[$ret->status] ?? 'dark';
                                    @endphp
                                    <span class="badge badge-subtle-{{ $sc }} rounded-pill">{{ $ret->status }}</span>
                                </td>
                                <td class="text-end px-3">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="{{ route('purchase-returns.edit', $ret->id) }}" class="btn btn-link p-0" title="{{ $ret->status === 'Draft' ? 'Edit Return' : 'View Details' }}">
                                            <span class="text-primary fas fa-{{ $ret->status === 'Draft' ? 'edit' : 'eye' }}"></span>
                                        </a>
                                        @if($ret->status === 'Draft' && (auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_inventory')))
                                            <button class="btn btn-link p-0" type="button" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal" wire:click="confirmDeletion({{ $ret->id }})" title="Delete">
                                                <span class="text-danger fas fa-trash-alt"></span>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">No purchase returns found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($purchaseReturns->hasPages())
            <div class="card-footer d-flex align-items-center justify-content-center bg-light">
                {{ $purchaseReturns->links() }}
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
                    <p class="mb-0 text-900">Are you sure you want to remove this draft purchase return? This action will permanently remove this draft return note.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-falcon-default btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button wire:click="deleteRecord" type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">
                        <span class="fas fa-trash-alt me-1"></span>Delete Return
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
