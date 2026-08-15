<div>
    {{-- ── Alerts ─────────────────────────────────────────────────────────── --}}
    @if (session()->has('success'))
        <div class="alert alert-success border-2 d-flex align-items-center mb-3" role="alert">
            <span class="fas fa-check-circle me-2"></span>
            <div class="flex-1">{{ session('success') }}</div>
            <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="alert alert-danger border-2 d-flex align-items-center mb-3" role="alert">
            <span class="fas fa-exclamation-circle me-2"></span>
            <div class="flex-1">{{ session('error') }}</div>
            <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════
         LIST VIEW
    ═══════════════════════════════════════════════════════════════════════ --}}
    @if($mode === 'list')
        <div class="row g-3 mb-3 align-items-center justify-content-between">
            <div class="col-auto">
                <h3 class="mb-0 text-secondary">
                    <span class="fas fa-clipboard-check me-2 text-primary"></span>Stock Adjustments & Counts
                </h3>
                <p class="text-600 fs-10 mb-0">Record physical counts, opening stock, wastage, damage, expiry adjustments</p>
            </div>
            <div class="col-auto d-flex gap-2 flex-wrap">
                {{-- TASK 2 Level 1: UI loading state on all buttons --}}
                <button wire:click="openAdjustmentForm('Opening')" wire:loading.attr="disabled"
                    class="btn btn-outline-info btn-sm">
                    <span wire:loading.remove><span class="fas fa-plus me-1"></span>Opening Stock</span>
                    <span wire:loading><span class="fas fa-spinner fa-spin me-1"></span>Loading...</span>
                </button>
                <div class="dropdown">
                    <button class="btn btn-outline-warning btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <span class="fas fa-minus me-1"></span>Write-Off
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><button class="dropdown-item" wire:click="openAdjustmentForm('Wastage')">
                            <span class="fas fa-fire-alt me-2 text-warning"></span>Wastage
                        </button></li>
                        <li><button class="dropdown-item" wire:click="openAdjustmentForm('Damage')">
                            <span class="fas fa-box-open me-2 text-danger"></span>Damage
                        </button></li>
                        <li><button class="dropdown-item" wire:click="openAdjustmentForm('Expiry')">
                            <span class="fas fa-calendar-times me-2 text-secondary"></span>Expiry
                        </button></li>
                        <li><button class="dropdown-item" wire:click="openAdjustmentForm('Adjustment')">
                            <span class="fas fa-balance-scale me-2 text-primary"></span>Manual Adjustment
                        </button></li>
                    </ul>
                </div>
                <button wire:click="openCreateForm" wire:loading.attr="disabled"
                    class="btn btn-primary btn-sm">
                    <span wire:loading.remove wire:target="openCreateForm">
                        <span class="fas fa-clipboard-list me-1"></span>New Stock Take / Count
                    </span>
                    <span wire:loading wire:target="openCreateForm">
                        <span class="fas fa-spinner fa-spin me-1"></span>Loading...
                    </span>
                </button>
            </div>
        </div>

        {{-- Filters --}}
        <div class="card border border-200 mb-3">
            <div class="card-body p-3 bg-light">
                <div class="row g-2 align-items-center">
                    <div class="col-md-6">
                        <div class="input-group input-group-sm">
                            <input wire:model.live.debounce.300ms="search" class="form-control" type="search"
                                placeholder="Search by Stock Take Number (e.g. STK-...)" />
                            <span class="input-group-text"><span class="fas fa-search"></span></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <select wire:model.live="filterStatus" class="form-select form-select-sm">
                            <option value="">All Statuses</option>
                            <option value="Draft">Draft</option>
                            <option value="Approved">Approved</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- Data Table --}}
        <div class="card border border-200">
            <div class="card-body p-0">
                <div class="table-responsive scrollbar">
                    <table class="table table-sm table-striped fs-10 mb-0 align-middle">
                        <thead class="bg-200 text-900">
                            <tr>
                                <th class="px-3" style="width: 150px;">Stock Take #</th>
                                <th>Count Date</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th>Approved By</th>
                                <th class="text-end px-3" style="width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stockTakes as $st)
                                <tr>
                                    <td class="px-3 font-monospace fw-bold text-primary">{{ $st->stock_take_number }}</td>
                                    <td>{{ $st->count_date->format('Y-m-d') }}</td>
                                    <td>
                                        @if($st->status === 'Draft')
                                            <span class="badge badge-subtle-warning rounded-pill">Draft</span>
                                        @elseif($st->status === 'Approved')
                                            <span class="badge badge-subtle-success rounded-pill">Approved</span>
                                        @else
                                            <span class="badge badge-subtle-secondary rounded-pill">Cancelled</span>
                                        @endif
                                    </td>
                                    <td>{{ $st->creator->name ?? '—' }}</td>
                                    <td>{{ $st->approver->name ?? '—' }}</td>
                                    <td class="text-end px-3">
                                        <button wire:click="viewDetails({{ $st->id }})" class="btn btn-link btn-sm p-0 me-2 text-primary">
                                            <span class="fas fa-eye" data-bs-toggle="tooltip" title="View Details"></span> View
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <span class="fas fa-clipboard-list fa-2x mb-2 d-block"></span>
                                        No stock count records found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($stockTakes->hasPages())
                <div class="card-footer bg-light d-flex align-items-center justify-content-center p-2">
                    {{ $stockTakes->links() }}
                </div>
            @endif
        </div>

    {{-- ═══════════════════════════════════════════════════════════════════════
         DIRECT ADJUSTMENT FORM (Opening / Wastage / Damage / Expiry / Adjustment)
    ═══════════════════════════════════════════════════════════════════════ --}}
    @elseif($mode === 'adjustment')
        <div class="card border border-200">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                @php
                    $adjColors = ['Opening'=>'info','Wastage'=>'warning','Damage'=>'danger','Expiry'=>'secondary','Adjustment'=>'primary'];
                    $adjIcons  = ['Opening'=>'fa-plus-circle','Wastage'=>'fa-fire-alt','Damage'=>'fa-box-open','Expiry'=>'fa-calendar-times','Adjustment'=>'fa-balance-scale'];
                @endphp
                <h5 class="mb-0">
                    <span class="fas {{ $adjIcons[$adj_type] ?? 'fa-edit' }} me-2 text-{{ $adjColors[$adj_type] ?? 'primary' }}"></span>
                    Record {{ $adj_type }} Entry
                </h5>
                <button wire:click="$set('mode','list')" class="btn btn-outline-secondary btn-xs">
                    <span class="fas fa-times me-1"></span>Back
                </button>
            </div>
            <div class="card-body">

                {{-- Opening duplicate warning --}}
                @if($adj_has_existing_opening && $adj_type === 'Opening')
                    <div class="alert alert-warning border-warning-subtle fs-11 mb-3">
                        <span class="fas fa-exclamation-triangle me-2"></span>
                        <strong>Warning:</strong> An Opening Stock entry already exists for this item in this branch.
                        Recording another will add a second positive adjustment. Consider using a regular
                        <em>Adjustment</em> instead.
                    </div>
                @endif

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semi-bold">Adjustment Type</label>
                        <select wire:model.live="adj_type" class="form-select form-select-sm">
                            <option value="Opening">Opening Stock</option>
                            <option value="Adjustment">Manual Adjustment (IN)</option>
                            <option value="Wastage">Wastage (OUT)</option>
                            <option value="Damage">Damage (OUT)</option>
                            <option value="Expiry">Expiry (OUT)</option>
                        </select>
                        @error('adj_type') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                        <div class="mt-1">
                            <span class="badge badge-subtle-{{ $adj_direction === 'in' ? 'success' : 'danger' }} rounded-pill fs-11">
                                {{ $adj_direction === 'in' ? '↑ Stock IN' : '↓ Stock OUT' }}
                            </span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semi-bold">Item <span class="text-danger">*</span></label>
                        <select wire:model.live="adj_item_id" class="form-select form-select-sm">
                            <option value="">Select Item…</option>
                            @foreach($inventoryItems as $it)
                                <option value="{{ $it->id }}">{{ $it->name }} ({{ $it->item_code }})</option>
                            @endforeach
                        </select>
                        @error('adj_item_id') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semi-bold">Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control form-control-sm" wire:model="adj_date">
                        @error('adj_date') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semi-bold">Quantity <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" class="form-control form-control-sm" wire:model="adj_quantity" placeholder="0.00">
                        @error('adj_quantity') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semi-bold">Unit Cost <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">PKR</span>
                            <input type="number" step="0.01" min="0" class="form-control" wire:model="adj_unit_cost" placeholder="0.00">
                        </div>
                        @error('adj_unit_cost') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semi-bold">Reference <span class="text-muted fs-11">(optional)</span></label>
                        <input type="text" class="form-control form-control-sm" wire:model="adj_reference" placeholder="e.g. Booking #123, Audit #5">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semi-bold">Reason / Notes <span class="text-muted fs-11">(optional)</span></label>
                        <input type="text" class="form-control form-control-sm" wire:model="adj_reason" placeholder="e.g. Kitchen preparation loss">
                    </div>
                </div>

                {{-- Total Cost Preview --}}
                @if($adj_quantity > 0 && $adj_unit_cost > 0)
                    <div class="mt-3 p-2 bg-light rounded border">
                        <span class="text-muted fs-11">Total Cost Preview:</span>
                        <strong class="ms-2 font-monospace">PKR {{ number_format($adj_quantity * $adj_unit_cost, 2) }}</strong>
                    </div>
                @endif

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button wire:click="$set('mode','list')" class="btn btn-outline-secondary btn-sm">Cancel</button>
                    <button wire:click="saveAdjustment"
                        wire:loading.attr="disabled"
                        wire:target="saveAdjustment"
                        class="btn btn-{{ $adjColors[$adj_type] ?? 'primary' }} btn-sm">
                        <span wire:loading.remove wire:target="saveAdjustment">
                            <span class="fas fa-check me-1"></span>Post {{ $adj_type }} Entry
                        </span>
                        <span wire:loading wire:target="saveAdjustment">
                            <span class="fas fa-spinner fa-spin me-1"></span>Saving...
                        </span>
                    </button>
                </div>
            </div>
        </div>

    {{-- ═══════════════════════════════════════════════════════════════════════
         STOCK TAKE COUNT SHEET FORM
    ═══════════════════════════════════════════════════════════════════════ --}}
    @elseif($mode === 'stock-take')
        <div class="card border border-200">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><span class="fas fa-plus-circle me-2 text-primary"></span>New Stock Take Sheet</h5>
                <button wire:click="resetForm" class="btn btn-outline-secondary btn-xs">
                    <span class="fas fa-times me-1"></span>Back
                </button>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semi-bold">Stock Take Number</label>
                        <input type="text" class="form-control form-control-sm font-monospace fw-bold" wire:model="stock_take_number" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semi-bold">Count Date</label>
                        <input type="date" class="form-control form-control-sm" wire:model="count_date">
                        @error('count_date') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semi-bold">Filter Category for Sheet</label>
                        <select wire:model.live="categoryId" class="form-select form-select-sm">
                            <option value="">All Categories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semi-bold">General Notes / Remarks</label>
                    <textarea class="form-control form-control-sm" rows="2" wire:model="notes" placeholder="e.g. Monthly stock take, audit discrepancies..."></textarea>
                </div>

                <hr class="text-200">

                <h6 class="mb-3 text-700">Inventory Items Count List</h6>
                <div class="table-responsive scrollbar mb-3">
                    <table class="table table-sm table-striped fs-10 align-middle">
                        <thead class="bg-light text-900">
                            <tr>
                                <th class="ps-3" style="width: 120px;">Code</th>
                                <th>Item Name</th>
                                <th class="text-end" style="width: 120px;">System Qty</th>
                                <th class="text-end" style="width: 150px;">Physical Qty</th>
                                <th class="text-end" style="width: 120px;">Difference</th>
                                <th>Variance Explanation / Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($formItems as $index => $item)
                                @php
                                    $diff = (float)($formItems[$index]['physical_qty'] ?? 0) - (float)$item['system_qty'];
                                @endphp
                                <tr>
                                    <td class="ps-3 font-monospace">{{ $item['item_code'] }}</td>
                                    <td class="fw-semi-bold">{{ $item['name'] }}</td>
                                    <td class="text-end font-monospace text-muted">{{ number_format($item['system_qty'], 2) }}</td>
                                    <td class="text-end">
                                        <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end font-monospace py-0 fs-11 ms-auto" style="width: 120px;" wire:model.live.debounce.300ms="formItems.{{ $index }}.physical_qty">
                                        @error("formItems.{$index}.physical_qty") <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                                    </td>
                                    <td class="text-end font-monospace fw-bold {{ $diff == 0 ? 'text-muted' : ($diff > 0 ? 'text-success' : 'text-danger') }}">
                                        {{ $diff > 0 ? '+' : '' }}{{ number_format($diff, 2) }}
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm py-0 fs-11" placeholder="e.g. spillage, damaged" wire:model="formItems.{{ $index }}.reason">
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No items match filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button wire:click="resetForm" class="btn btn-outline-secondary btn-sm">Cancel</button>
                    {{-- TASK 2 Level 1: UI loading state on save button --}}
                    <button wire:click="save"
                        wire:loading.attr="disabled"
                        wire:target="save"
                        class="btn btn-primary btn-sm">
                        <span wire:loading.remove wire:target="save">Save Count Draft</span>
                        <span wire:loading wire:target="save">
                            <span class="fas fa-spinner fa-spin me-1"></span>Saving...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ── Stock Take View Modal ─────────────────────────────────────────── --}}
    @if($isViewModalOpen && $viewStockTake)
        <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5);" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content border border-200">
                    <div class="modal-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="modal-title"><span class="fas fa-file-alt me-2 text-primary"></span>Stock Take Details: {{ $viewStockTake->stock_take_number }}</h5>
                        <button type="button" class="btn-close" wire:click="$set('isViewModalOpen', false)" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0">
                        <div class="p-3">
                            <div class="row g-2 mb-3">
                                <div class="col-sm-4">
                                    <span class="text-muted fs-11 d-block mb-1">Status</span>
                                    @if($viewStockTake->status === 'Draft')
                                        <span class="badge badge-subtle-warning rounded-pill">Draft</span>
                                    @elseif($viewStockTake->status === 'Approved')
                                        <span class="badge badge-subtle-success rounded-pill">Approved</span>
                                    @else
                                        <span class="badge badge-subtle-secondary rounded-pill">Cancelled</span>
                                    @endif
                                </div>
                                <div class="col-sm-4">
                                    <span class="text-muted fs-11 d-block mb-1">Count Date</span>
                                    <strong>{{ $viewStockTake->count_date->format('Y-m-d') }}</strong>
                                </div>
                                <div class="col-sm-4">
                                    <span class="text-muted fs-11 d-block mb-1">Created By</span>
                                    <strong>{{ $viewStockTake->creator->name ?? 'System' }}</strong>
                                </div>
                            </div>
                            @if($viewStockTake->notes)
                                <div class="bg-light p-2 rounded mb-3">
                                    <span class="text-muted fs-11 d-block mb-1">Notes:</span>
                                    <p class="mb-0 fs-11">{{ $viewStockTake->notes }}</p>
                                </div>
                            @endif
                            @if($viewStockTake->status === 'Approved' && $viewStockTake->approved_at)
                                <div class="alert alert-success-subtle border border-success-subtle p-2 mb-3 fs-11">
                                    <span class="fas fa-check-circle me-1 text-success"></span>
                                    Approved by <strong>{{ $viewStockTake->approver->name ?? 'System' }}</strong>
                                    on <strong>{{ $viewStockTake->approved_at->format('Y-m-d H:i') }}</strong>. Ledger adjustments have been posted.
                                </div>
                            @endif
                        </div>

                        <div class="table-responsive border-top">
                            <table class="table table-sm table-striped mb-0 fs-11 align-middle">
                                <thead class="bg-200">
                                    <tr>
                                        <th class="ps-3">Item Code</th>
                                        <th>Item Name</th>
                                        <th class="text-end">System Qty</th>
                                        <th class="text-end">Physical Qty</th>
                                        <th class="text-end">Difference</th>
                                        <th class="pe-3">Reason</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($viewStockTake->items as $row)
                                        <tr>
                                            <td class="ps-3 font-monospace">{{ $row->item->item_code ?? '—' }}</td>
                                            <td class="fw-semi-bold">{{ $row->item->name ?? '—' }}</td>
                                            <td class="text-end font-monospace text-muted">{{ number_format($row->system_qty, 2) }}</td>
                                            <td class="text-end font-monospace fw-bold">{{ number_format($row->physical_qty, 2) }}</td>
                                            <td class="text-end font-monospace fw-bold {{ $row->difference == 0 ? 'text-muted' : ($row->difference > 0 ? 'text-success' : 'text-danger') }}">
                                                {{ $row->difference > 0 ? '+' : '' }}{{ number_format($row->difference, 2) }}
                                            </td>
                                            <td class="pe-3 text-muted">{{ $row->reason ?: '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer bg-light d-flex justify-content-between">
                        <div>
                            @if($viewStockTake->status === 'Draft')
                                @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('inventory.adjust'))
                                    {{-- TASK 2 Level 1: Loading state on Approve button --}}
                                    <button type="button"
                                        class="btn btn-success btn-sm me-1"
                                        wire:click="approveStockTake({{ $viewStockTake->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="approveStockTake">
                                        <span wire:loading.remove wire:target="approveStockTake">
                                            <span class="fas fa-check me-1"></span>Approve & Adjust
                                        </span>
                                        <span wire:loading wire:target="approveStockTake">
                                            <span class="fas fa-spinner fa-spin me-1"></span>Processing...
                                        </span>
                                    </button>
                                @endif
                                <button type="button" class="btn btn-outline-danger btn-sm" wire:click="cancelStockTake({{ $viewStockTake->id }})">
                                    <span class="fas fa-trash me-1"></span>Cancel Count
                                </button>
                            @endif
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="$set('isViewModalOpen', false)">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
