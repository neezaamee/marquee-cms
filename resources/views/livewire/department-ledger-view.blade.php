<div class="container-fluid p-0">
    <div class="row mb-3">
        <div class="col-12">
            <h4 class="mb-0 text-secondary"><span class="fas fa-clipboard-list me-2 text-primary"></span>Department Stock Ledgers</h4>
        </div>
    </div>

    <div class="card border border-200">
        <div class="card-header bg-light">
            <div class="row g-2">
                <div class="col-md-6">
                    <label class="form-label mb-1 fw-semi-bold">Filter Department</label>
                    <select wire:model.live="filterDepartment" class="form-select form-select-sm">
                        <option value="">Select Department</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }} ({{ $dept->department_code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label mb-1 fw-semi-bold">Filter Item</label>
                    <select wire:model.live="filterItem" class="form-select form-select-sm">
                        <option value="">All Items</option>
                        @foreach($inventoryItems as $item)
                            <option value="{{ $item->id }}">{{ $item->name }} ({{ $item->item_code }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0 align-middle">
                    <thead class="bg-200">
                        <tr>
                            <th class="px-3">Date</th>
                            <th>Department</th>
                            <th>Item Code</th>
                            <th>Item Name</th>
                            <th>Tx Type</th>
                            <th class="text-end">Qty In</th>
                            <th class="text-end">Qty Out</th>
                            <th class="text-end">Balance</th>
                            <th class="text-end">Unit Price</th>
                            <th class="text-end px-3">Total Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ledgerEntries as $entry)
                            <tr>
                                <td class="px-3">{{ $entry->transaction_date->format('Y-m-d') }}</td>
                                <td class="fw-semi-bold">{{ $entry->department->name }}</td>
                                <td class="font-monospace fs-11">{{ $entry->item->item_code }}</td>
                                <td>{{ $entry->item->name }}</td>
                                <td>
                                    <span class="badge badge-subtle-{{ in_array($entry->transaction_type, ['Issue', 'Adjustment']) ? 'success' : 'danger' }}">
                                        {{ $entry->transaction_type }}
                                    </span>
                                </td>
                                <td class="text-end font-monospace text-success">{{ $entry->qty_in > 0 ? number_format($entry->qty_in, 2) : '—' }}</td>
                                <td class="text-end font-monospace text-danger">{{ $entry->qty_out > 0 ? number_format($entry->qty_out, 2) : '—' }}</td>
                                <td class="text-end font-monospace fw-bold text-primary">{{ number_format($entry->running_balance, 2) }}</td>
                                <td class="text-end font-monospace text-muted">{{ number_format($entry->unit_price, 2) }}</td>
                                <td class="text-end font-monospace px-3 fw-semi-bold text-dark">{{ number_format($entry->total_cost, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <span class="fas fa-history fa-2x mb-2 d-block"></span>
                                    No stock ledger transactions logged for the filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($ledgerEntries->hasPages())
            <div class="card-footer bg-light p-2 text-center">
                {{ $ledgerEntries->links() }}
            </div>
        @endif
    </div>
</div>
