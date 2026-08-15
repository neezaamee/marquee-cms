<div>
    {{-- ── KPI Summary Bar ─────────────────────────────────────────────────── --}}
    <div class="row g-3 mb-3">
        <div class="col-sm-4">
            <div class="card bg-success bg-opacity-10 border border-success border-opacity-25 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-success bg-opacity-25 d-flex align-items-center justify-content-center" style="width:42px;height:42px;">
                            <span class="fas fa-arrow-down text-success fs-10"></span>
                        </div>
                        <div>
                            <p class="mb-0 text-muted fs-11">Total Stock In</p>
                            <h5 class="mb-0 fw-bold text-success font-monospace">{{ number_format($totalIn, 2) }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card bg-danger bg-opacity-10 border border-danger border-opacity-25 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-danger bg-opacity-25 d-flex align-items-center justify-content-center" style="width:42px;height:42px;">
                            <span class="fas fa-arrow-up text-danger fs-10"></span>
                        </div>
                        <div>
                            <p class="mb-0 text-muted fs-11">Total Stock Out</p>
                            <h5 class="mb-0 fw-bold text-danger font-monospace">{{ number_format($totalOut, 2) }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card bg-primary bg-opacity-10 border border-primary border-opacity-25 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-primary bg-opacity-25 d-flex align-items-center justify-content-center" style="width:42px;height:42px;">
                            <span class="fas fa-coins text-primary fs-10"></span>
                        </div>
                        <div>
                            <p class="mb-0 text-muted fs-11">Total Cost Value</p>
                            <h5 class="mb-0 fw-bold text-primary font-monospace">{{ number_format($totalCost, 2) }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Filter Card ─────────────────────────────────────────────────────── --}}
    <div class="card border border-200 mb-3">
        <div class="card-body p-3 bg-light">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fs-11 mb-1 fw-semi-bold text-700">Search Item</label>
                    <div class="input-group input-group-sm">
                        <input wire:model.live.debounce.400ms="search" class="form-control" type="search" placeholder="Item name or code…" />
                        <span class="input-group-text"><span class="fas fa-search"></span></span>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label fs-11 mb-1 fw-semi-bold text-700">Branch</label>
                    <select wire:model.live="filterBranch" class="form-select form-select-sm" {{ auth()->user()->branch_id && !auth()->user()->isSuperAdmin() ? 'disabled' : '' }}>
                        <option value="">All Branches</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fs-11 mb-1 fw-semi-bold text-700">Item</label>
                    <select wire:model.live="filterItem" class="form-select form-select-sm">
                        <option value="">All Items</option>
                        @foreach($items as $it)
                            <option value="{{ $it->id }}">{{ $it->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fs-11 mb-1 fw-semi-bold text-700">Type</label>
                    <select wire:model.live="filterType" class="form-select form-select-sm">
                        <option value="">All Types</option>
                        @foreach($transactionTypes as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label fs-11 mb-1 fw-semi-bold text-700">From</label>
                    <input wire:model.live="filterDateFrom" type="date" class="form-control form-control-sm" />
                </div>
                <div class="col-md-1">
                    <label class="form-label fs-11 mb-1 fw-semi-bold text-700">To</label>
                    <input wire:model.live="filterDateTo" type="date" class="form-control form-control-sm" />
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button wire:click="clearFilters" class="btn btn-outline-secondary btn-sm w-100" title="Clear filters">
                        <span class="fas fa-times"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Ledger Table ─────────────────────────────────────────────────────── --}}
    <div class="card border border-200">
        <div class="card-body p-0">
            <div class="table-responsive scrollbar">
                <table class="table table-sm table-striped fs-10 mb-0 align-middle">
                    <thead class="bg-200 text-900">
                        <tr>
                            <th class="px-3" style="width:100px">Date</th>
                            <th>Item</th>
                            <th>Branch</th>
                            <th>Type</th>
                            <th>Reference</th>
                            <th class="text-end" style="width:90px">Qty In</th>
                            <th class="text-end" style="width:90px">Qty Out</th>
                            <th class="text-end" style="width:100px">Balance</th>
                            <th class="text-end" style="width:85px">Unit Cost</th>
                            <th class="text-end" style="width:95px">Total Cost</th>
                            <th style="width:90px">User</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ledger as $row)
                            @php
                                $typeColor = match($row->transaction_type) {
                                    'GRN','Return','Opening' => 'success',
                                    'Issue','PurchaseReturn' => 'danger',
                                    'Adjustment'             => 'info',
                                    'Wastage','Damage','Expiry' => 'warning',
                                    default                  => 'secondary',
                                };
                            @endphp
                            <tr>
                                <td class="px-3 font-monospace text-muted">{{ $row->transaction_date->format('Y-m-d') }}</td>
                                <td>
                                    <span class="fw-semi-bold">{{ $row->item_name }}</span>
                                    <span class="text-muted fs-11 d-block">{{ $row->item_code }}</span>
                                </td>
                                <td class="fs-11 text-muted">{{ $row->branch_name }}</td>
                                <td>
                                    <span class="badge badge-subtle-{{ $typeColor }} rounded-pill">{{ $row->transaction_type }}</span>
                                </td>
                                <td class="fs-11 text-muted font-monospace">
                                    @if($row->reference_type && $row->reference_id)
                                        {{ class_basename($row->reference_type) }} #{{ $row->reference_id }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-end font-monospace {{ $row->qty_in > 0 ? 'text-success fw-bold' : 'text-muted' }}">
                                    {{ $row->qty_in > 0 ? '+'.number_format($row->qty_in, 2) : '—' }}
                                </td>
                                <td class="text-end font-monospace {{ $row->qty_out > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                                    {{ $row->qty_out > 0 ? '-'.number_format($row->qty_out, 2) : '—' }}
                                </td>
                                <td class="text-end font-monospace fw-bold {{ $row->running_balance < 0 ? 'text-danger' : '' }}">
                                    {{ number_format($row->running_balance, 2) }}
                                </td>
                                <td class="text-end font-monospace text-muted">{{ number_format($row->unit_price, 2) }}</td>
                                <td class="text-end font-monospace text-muted">{{ number_format($row->total_cost, 2) }}</td>
                                <td class="fs-11 text-muted">{{ $row->creator->name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-5 text-muted">
                                    <span class="fas fa-database fa-2x mb-2 d-block text-300"></span>
                                    No ledger entries found for the selected filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($ledger->hasPages())
            <div class="card-footer bg-light d-flex align-items-center justify-content-between p-2">
                <small class="text-muted">Showing {{ $ledger->firstItem() }}–{{ $ledger->lastItem() }} of {{ $ledger->total() }} entries</small>
                {{ $ledger->links() }}
            </div>
        @else
            <div class="card-footer bg-light p-2">
                <small class="text-muted">{{ $ledger->total() }} entries total</small>
            </div>
        @endif
    </div>
</div>
