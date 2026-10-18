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
                <button wire:click="openAdjustmentForm('Opening')" wire:loading.attr="disabled"
                    class="btn btn-outline-info btn-sm">
                    <span wire:loading.remove><span class="fas fa-plus me-1"></span>Opening Stock</span>
                    <span wire:loading><span class="fas fa-spinner fa-spin me-1"></span>Loading...</span>
                </button>
                <div class="dropdown">
                    <button class="btn btn-outline-warning btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <span class="fas fa-minus me-1"></span>Write-Off / Loss
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
                <button wire:click="startMonthlyStockTake" wire:loading.attr="disabled"
                    class="btn btn-outline-primary btn-sm" title="Perform standard monthly physical inventory count">
                    <span class="fas fa-calendar-alt me-1"></span>Monthly Stock Take
                </button>
                <a href="{{ route('inventory.stock-takes.count-sheet.print', ['branch_id' => $selected_branch_id, 'category_id' => $categoryId]) }}" target="_blank"
                    class="btn btn-outline-secondary btn-sm" title="Print blank count sheet for floor auditors">
                    <span class="fas fa-print me-1"></span>Print Count Sheet
                </a>
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

        {{-- Filters & Navigation Tabs --}}
        <div class="card border border-200 mb-3">
            <div class="card-body p-3 bg-light">
                <div class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <div class="input-group input-group-sm">
                            <input wire:model.live.debounce.300ms="search" class="form-control" type="search"
                                placeholder="{{ $activeTab === 'counts' ? 'Search by Stock Take # (e.g. STK-...)' : 'Search by item name or code...' }}" />
                            <span class="input-group-text"><span class="fas fa-search"></span></span>
                        </div>
                    </div>
                    @if(auth()->user()->branch_id && !auth()->user()->isSuperAdmin())
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" disabled>
                                <option>{{ auth()->user()->branch->name ?? 'Assigned Branch' }}</option>
                            </select>
                        </div>
                    @else
                        <div class="col-md-3">
                            <select wire:model.live="selected_branch_id" class="form-select form-select-sm">
                                <option value="">All Branches</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="col-md-4">
                        @if($activeTab === 'counts')
                            <select wire:model.live="filterStatus" class="form-select form-select-sm">
                                <option value="">All Statuses</option>
                                <option value="Draft">Draft</option>
                                <option value="Approved">Approved</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Nav Tabs: Physical Counts vs Adjustments Log --}}
        <ul class="nav nav-tabs border-bottom mb-3" role="tablist">
            <li class="nav-item">
                <button class="nav-link {{ $activeTab === 'counts' ? 'active fw-bold' : 'text-secondary' }}"
                    type="button" wire:click="$set('activeTab', 'counts')">
                    <span class="fas fa-clipboard-list me-2"></span>Physical Stock Takes
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link {{ $activeTab === 'adjustments' ? 'active fw-bold' : 'text-secondary' }}"
                    type="button" wire:click="$set('activeTab', 'adjustments')">
                    <span class="fas fa-sliders-h me-2"></span>Adjustments & Opening Stock Log
                </button>
            </li>
        </ul>

        {{-- Tab 1: Physical Counts --}}
        @if($activeTab === 'counts')
            <div class="card border border-200">
                <div class="card-body p-0">
                    <div class="table-responsive scrollbar">
                        <table class="table table-sm table-striped fs-10 mb-0 align-middle">
                            <thead class="bg-200 text-900">
                                <tr>
                                    <th class="px-3" style="width: 140px;">Stock Take #</th>
                                    <th>Count Date</th>
                                    <th>Branch</th>
                                    <th>Status</th>
                                    <th>Created By</th>
                                    <th>Approved By</th>
                                    <th class="text-end px-3" style="width: 100px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($stockTakes as $st)
                                    <tr>
                                        <td class="px-3 font-monospace fw-bold text-primary">{{ $st->stock_take_number }}</td>
                                        <td>{{ $st->count_date->format('Y-m-d') }}</td>
                                        <td><span class="badge bg-light text-dark">{{ $st->branch->name ?? '—' }}</span></td>
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
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <span class="fas fa-clipboard-list fa-2x mb-2 d-block"></span>
                                            No physical stock count records found.
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

        {{-- Tab 2: Adjustments & Opening Stock Log --}}
        @else
            <div class="card border border-200">
                <div class="card-body p-0">
                    <div class="table-responsive scrollbar">
                        <table class="table table-sm table-striped fs-10 mb-0 align-middle">
                            <thead class="bg-200 text-900">
                                <tr>
                                    <th class="px-3" style="width: 95px;">Date</th>
                                    <th>Item</th>
                                    <th>Branch</th>
                                    <th>Type</th>
                                    <th class="text-center" style="width: 70px;">Direction</th>
                                    <th class="text-end" style="width: 90px;">Quantity</th>
                                    <th class="text-end" style="width: 90px;">Unit Cost</th>
                                    <th class="text-end" style="width: 100px;">Total Cost</th>
                                    <th>Reference / Reason</th>
                                    <th>Logged By</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($adjustments as $adj)
                                    @php
                                        $typeBadge = match($adj->transaction_type) {
                                            'Opening'    => 'info',
                                            'Adjustment' => 'primary',
                                            'Wastage'    => 'warning',
                                            'Damage'     => 'danger',
                                            'Expiry'     => 'secondary',
                                            default      => 'dark',
                                        };
                                        $isIncoming = (float)$adj->qty_in > 0;
                                    @endphp
                                    <tr>
                                        <td class="px-3 font-monospace text-muted">{{ $adj->transaction_date ? \Carbon\Carbon::parse($adj->transaction_date)->format('Y-m-d') : '—' }}</td>
                                        <td>
                                            <div class="fw-semi-bold">{{ $adj->item_name }}</div>
                                            <span class="badge badge-subtle-secondary fs-11 font-monospace">{{ $adj->item_code }}</span>
                                        </td>
                                        <td><span class="badge bg-light text-dark">{{ $adj->branch_name ?? '—' }}</span></td>
                                        <td>
                                            <span class="badge badge-subtle-{{ $typeBadge }} rounded-pill">{{ $adj->transaction_type }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if($isIncoming)
                                                <span class="badge badge-subtle-success rounded-pill fs-11">IN</span>
                                            @else
                                                <span class="badge badge-subtle-danger rounded-pill fs-11">OUT</span>
                                            @endif
                                        </td>
                                        <td class="text-end font-monospace fw-bold {{ $isIncoming ? 'text-success' : 'text-danger' }}">
                                            {{ $isIncoming ? '+' : '-' }}{{ number_format($isIncoming ? $adj->qty_in : $adj->qty_out, 2) }}
                                        </td>
                                        <td class="text-end font-monospace">PKR {{ number_format($adj->unit_price, 2) }}</td>
                                        <td class="text-end font-monospace fw-bold">PKR {{ number_format($adj->total_cost, 2) }}</td>
                                        <td class="text-muted fs-11">
                                            {{ $adj->reference_type ?? ($adj->reason ?? '—') }}
                                        </td>
                                        <td>{{ $adj->creator->name ?? 'System' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center py-5 text-muted">
                                            <span class="fas fa-sliders-h fa-2x mb-2 d-block"></span>
                                            No adjustment records found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($adjustments->hasPages())
                    <div class="card-footer bg-light d-flex align-items-center justify-content-center p-2">
                        {{ $adjustments->links() }}
                    </div>
                @endif
            </div>
        @endif

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
                    @if(auth()->user()->branch_id && !auth()->user()->isSuperAdmin())
                        <div class="col-md-3">
                            <label class="form-label fw-semi-bold">Branch</label>
                            <input type="text" class="form-control form-control-sm" value="{{ auth()->user()->branch->name ?? 'Assigned Branch' }}" readonly disabled>
                        </div>
                    @else
                        <div class="col-md-3">
                            <label class="form-label fw-semi-bold">Branch <span class="text-danger">*</span></label>
                            <select wire:model.live="selected_branch_id" class="form-select form-select-sm">
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="col-md-3">
                        <label class="form-label fw-semi-bold">Adjustment Type</label>
                        <select wire:model.live="adj_type" class="form-select form-select-sm">
                            <option value="Opening">Opening Stock (IN)</option>
                            <option value="Adjustment">Manual Adjustment</option>
                            <option value="Wastage">Wastage (OUT)</option>
                            <option value="Damage">Damage (OUT)</option>
                            <option value="Expiry">Expiry (OUT)</option>
                        </select>
                        @error('adj_type') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                    </div>

                    @if($adj_type === 'Adjustment')
                        <div class="col-md-2">
                            <label class="form-label fw-semi-bold">Direction</label>
                            <select wire:model.live="adj_direction" class="form-select form-select-sm">
                                <option value="in">↑ Stock IN</option>
                                <option value="out">↓ Stock OUT</option>
                            </select>
                        </div>
                    @else
                        <div class="col-md-2 d-flex flex-column justify-content-end pb-1">
                            <div>
                                <span class="badge badge-subtle-{{ $adj_direction === 'in' ? 'success' : 'danger' }} rounded-pill fs-11">
                                    {{ $adj_direction === 'in' ? '↑ Stock IN' : '↓ Stock OUT' }}
                                </span>
                            </div>
                        </div>
                    @endif

                    <div class="col-md-4 position-relative"
                         x-data="{
                            open: false,
                            search: '',
                            activeIndex: -1,
                            items: {{ json_encode($catalogItemsData ?? []) }},
                            get filteredItems() {
                                if (!this.search || !this.search.trim()) return this.items;
                                const q = this.search.toLowerCase().trim();
                                return this.items.filter(i => 
                                    (i.item_code && i.item_code.toLowerCase().includes(q)) || 
                                    (i.name && i.name.toLowerCase().includes(q)) ||
                                    (i.category && i.category.toLowerCase().includes(q))
                                );
                            },
                            init() {
                                this.syncFromWire();
                                this.$watch('$wire.adj_item_id', () => {
                                    this.syncFromWire();
                                });
                            },
                            syncFromWire() {
                                const currentId = $wire.adj_item_id;
                                if (!currentId) {
                                    this.search = '';
                                    this.activeIndex = -1;
                                } else {
                                    const found = this.items.find(i => i.id == currentId);
                                    if (found) {
                                        this.search = found.name + (found.item_code ? ' [' + found.item_code + ']' : '');
                                    }
                                }
                            },
                            selectItem(item) {
                                this.search = item.name + (item.item_code ? ' [' + item.item_code + ']' : '');
                                this.open = false;
                                this.activeIndex = -1;
                                $wire.selectAdjustmentItem(item.id);
                            },
                            clearSelection() {
                                this.search = '';
                                this.open = true;
                                this.activeIndex = -1;
                                $wire.selectAdjustmentItem('');
                                this.$nextTick(() => {
                                    this.$refs.itemSearchInput.focus();
                                });
                            },
                            handleKeydown(e) {
                                const count = this.filteredItems.length;
                                if (!this.open) {
                                    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                                        e.preventDefault();
                                        this.open = true;
                                        this.activeIndex = 0;
                                        this.scrollToActive();
                                        return;
                                    }
                                    if (e.key === 'Enter') {
                                        e.preventDefault();
                                        this.open = true;
                                        return;
                                    }
                                    return;
                                }
                                if (e.key === 'ArrowDown') {
                                    e.preventDefault();
                                    if (count > 0) {
                                        this.activeIndex = (this.activeIndex + 1) % count;
                                        this.scrollToActive();
                                    }
                                } else if (e.key === 'ArrowUp') {
                                    e.preventDefault();
                                    if (count > 0) {
                                        this.activeIndex = (this.activeIndex - 1 + count) % count;
                                        this.scrollToActive();
                                    }
                                } else if (e.key === 'Enter') {
                                    e.preventDefault();
                                    if (this.activeIndex >= 0 && this.activeIndex < count) {
                                        this.selectItem(this.filteredItems[this.activeIndex]);
                                    } else if (count === 1) {
                                        this.selectItem(this.filteredItems[0]);
                                    }
                                } else if (e.key === 'Escape') {
                                    e.preventDefault();
                                    this.open = false;
                                    this.activeIndex = -1;
                                } else if (e.key === 'Tab') {
                                    this.open = false;
                                }
                            },
                            scrollToActive() {
                                this.$nextTick(() => {
                                    if (this.$refs.dropdownList) {
                                        const el = this.$refs.dropdownList.querySelector(`[data-index=\'${this.activeIndex}\']`);
                                        if (el) el.scrollIntoView({ block: 'nearest' });
                                    }
                                });
                            }
                         }"
                         @click.outside="
                            if ($wire.adj_item_id) {
                                const found = items.find(i => i.id == $wire.adj_item_id);
                                if (found) search = found.name + (found.item_code ? ' [' + found.item_code + ']' : '');
                            } else {
                                search = '';
                            }
                            open = false;
                            activeIndex = -1;
                         ">
                        <label class="form-label fw-semi-bold d-flex justify-content-between align-items-center mb-1">
                            <span>Item <span class="text-danger">*</span></span>
                            <span class="text-muted fs-11"><i class="fas fa-search me-1"></i>Searchable</span>
                        </label>

                        <input type="hidden" wire:model="adj_item_id" />

                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0 px-2 text-muted">
                                <span class="fas fa-search fs-12"></span>
                            </span>
                            <input x-ref="itemSearchInput"
                                   type="text"
                                   class="form-control form-control-sm border-start-0 border-end-0 @error('adj_item_id') is-invalid @enderror"
                                   placeholder="Search item by name or code (use ↑ / ↓)..."
                                   autocomplete="off"
                                   x-model="search"
                                   @focus="open = true; $refs.itemSearchInput.select()"
                                   @click="open = true"
                                   @keydown="handleKeydown($event)" />
                            <template x-if="search">
                                <button type="button" 
                                        class="btn btn-outline-secondary btn-sm border-start-0 border-end-0 px-2 text-muted" 
                                        @click="clearSelection()"
                                        title="Clear selection">
                                    <span class="fas fa-times fs-12"></span>
                                </button>
                            </template>
                            <button type="button"
                                    class="btn btn-outline-secondary btn-sm px-2 text-muted"
                                    @click="open = !open; if(open) $nextTick(() => $refs.itemSearchInput.focus());"
                                    title="Toggle item list">
                                <span class="fas fa-chevron-down fs-11" :class="{ 'fa-rotate-180': open }"></span>
                            </button>
                        </div>

                        @error('adj_item_id') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror

                        {{-- Dropdown options list --}}
                        <div x-show="open" 
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 transform scale-95"
                             x-transition:enter-end="opacity-100 transform scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 transform scale-100"
                             x-transition:leave-end="opacity-0 transform scale-95"
                             x-ref="dropdownList"
                             class="position-absolute bg-white border rounded shadow-lg start-0 end-0 z-3 mt-1 overflow-hidden" 
                             style="max-height: 250px; overflow-y: auto; z-index: 1050; display: none;">
                            <template x-for="(item, idx) in filteredItems" :key="item.id">
                                <div :data-index="idx"
                                     @mouseenter="activeIndex = idx"
                                     @click="selectItem(item)"
                                     :class="{
                                         'bg-primary text-white': activeIndex === idx,
                                         'bg-primary-subtle text-primary fw-bold': $wire.adj_item_id == item.id && activeIndex !== idx,
                                         'hover-bg-light text-900': $wire.adj_item_id != item.id && activeIndex !== idx
                                     }"
                                     class="d-flex justify-content-between align-items-center py-2 px-3 border-bottom border-translucent cursor-pointer text-start"
                                     style="user-select: none;">
                                    <div>
                                        <template x-if="item.item_code">
                                            <span class="font-monospace fw-bold me-1" :class="activeIndex === idx ? 'text-white' : 'text-primary'" x-text="'[' + item.item_code + ']'"></span>
                                        </template>
                                        <span class="fw-semi-bold" x-text="item.name"></span>
                                        <template x-if="item.category">
                                            <span class="badge ms-1 fs-11" :class="activeIndex === idx ? 'bg-white text-dark' : 'bg-secondary-subtle text-secondary'" x-text="item.category"></span>
                                        </template>
                                    </div>
                                    <div class="text-end ps-2 text-nowrap">
                                        <span class="badge" :class="activeIndex === idx ? 'bg-white text-primary' : 'bg-light text-dark'" x-text="item.unit"></span>
                                        <template x-if="item.rate > 0">
                                            <span class="font-monospace fs-11 ms-1" :class="activeIndex === idx ? 'text-white' : 'text-muted'" x-text="'PKR ' + Number(item.rate).toFixed(2)"></span>
                                        </template>
                                    </div>
                                </div>
                            </template>
                            <template x-if="filteredItems.length === 0">
                                <div class="text-center py-3 text-muted fs-11">
                                    <span class="fas fa-exclamation-circle me-1"></span>No items match \"<span class="fw-bold" x-text="search"></span>\"
                                </div>
                            </template>
                        </div>
                    </div>

                    @php
                        $curItem = !empty($adj_item_id) ? $inventoryItems->firstWhere('id', (int)$adj_item_id) : null;
                    @endphp

                    <div class="col-md-3">
                        <label class="form-label fw-semi-bold">Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control form-control-sm" wire:model="adj_date">
                        @error('adj_date') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semi-bold">
                            Quantity <span class="text-danger">*</span>
                            @if($curItem && $curItem->unit)
                                <span class="badge bg-light text-secondary ms-1 fs-11">({{ $curItem->unit->short_code ?? $curItem->unit->name }})</span>
                            @endif
                        </label>
                        <input type="number" step="0.01" min="0.01" class="form-control form-control-sm" wire:model="adj_quantity" placeholder="0.00">
                        @error('adj_quantity') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semi-bold">
                            Unit Cost <span class="text-danger">*</span>
                            @if($curItem && ($curItem->average_cost > 0 || $curItem->default_purchase_rate > 0))
                                <span class="text-muted fs-11 ms-1">(Auto-filled)</span>
                            @endif
                        </label>
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
                    <div class="col-md-3">
                        <label class="form-label fw-semi-bold">Stock Take Number</label>
                        <input type="text" class="form-control form-control-sm font-monospace fw-bold" wire:model="stock_take_number" readonly>
                    </div>
                    @if(auth()->user()->branch_id && !auth()->user()->isSuperAdmin())
                        <div class="col-md-3">
                            <label class="form-label fw-semi-bold">Branch</label>
                            <input type="text" class="form-control form-control-sm" value="{{ auth()->user()->branch->name ?? 'Assigned Branch' }}" readonly disabled>
                        </div>
                    @else
                        <div class="col-md-3">
                            <label class="form-label fw-semi-bold">Branch <span class="text-danger">*</span></label>
                            <select wire:model.live="selected_branch_id" class="form-select form-select-sm">
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="col-md-3">
                        <label class="form-label fw-semi-bold">Count Date</label>
                        <input type="date" class="form-control form-control-sm" wire:model="count_date">
                        @error('count_date') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semi-bold">Category Filter</label>
                        <select wire:model.live="categoryId" class="form-select form-select-sm">
                            <option value="">All Categories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semi-bold">Search Item</label>
                        <input type="text" wire:model.live.debounce.300ms="itemSearch" class="form-control form-control-sm" placeholder="Code or name...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semi-bold">Stock Scope</label>
                        <select wire:model.live="stockFilter" class="form-select form-select-sm">
                            <option value="all">All Items</option>
                            <option value="in_stock">Positive Stock Only (> 0)</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semi-bold">General Notes / Remarks</label>
                    <textarea class="form-control form-control-sm" rows="2" wire:model="notes" placeholder="e.g. Monthly stock take, audit discrepancies...">{{ $notes }}</textarea>
                </div>

                @php
                    $liveSysVal = 0;
                    $livePhysVal = 0;
                    $liveDiffVal = 0;
                    foreach ($formItems as $fi) {
                        $c = (float)($fi['unit_cost'] ?? 0);
                        $s = (float)($fi['system_qty'] ?? 0);
                        $p = (float)($fi['physical_qty'] ?? 0);
                        $liveSysVal += ($s * $c);
                        $livePhysVal += ($p * $c);
                        $liveDiffVal += (($p - $s) * $c);
                    }
                @endphp

                <!-- Live Valuation Summary -->
                <div class="row g-2 mb-3">
                    <div class="col-sm-3">
                        <div class="p-2 bg-light border rounded text-center">
                            <span class="text-muted fs-11 text-uppercase fw-bold d-block">Items in Count</span>
                            <strong class="fs-10 font-monospace">{{ count($formItems) }} items</strong>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="p-2 bg-light border rounded text-center">
                            <span class="text-muted fs-11 text-uppercase fw-bold d-block">System Book Value</span>
                            <strong class="fs-10 font-monospace text-primary">PKR {{ number_format($liveSysVal, 2) }}</strong>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="p-2 bg-light border rounded text-center">
                            <span class="text-muted fs-11 text-uppercase fw-bold d-block">Physical Value</span>
                            <strong class="fs-10 font-monospace text-success">PKR {{ number_format($livePhysVal, 2) }}</strong>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="p-2 border rounded text-center {{ $liveDiffVal < 0 ? 'bg-danger bg-opacity-10 border-danger' : ($liveDiffVal > 0 ? 'bg-success bg-opacity-10 border-success' : 'bg-light') }}">
                            <span class="text-muted fs-11 text-uppercase fw-bold d-block">Net Variance Value</span>
                            <strong class="fs-10 font-monospace {{ $liveDiffVal < 0 ? 'text-danger' : ($liveDiffVal > 0 ? 'text-success' : 'text-muted') }}">
                                {{ $liveDiffVal > 0 ? '+' : '' }}PKR {{ number_format($liveDiffVal, 2) }}
                            </strong>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0 text-700">Inventory Items Count List</h6>
                    <a href="{{ route('inventory.stock-takes.count-sheet.print', ['branch_id' => $selected_branch_id, 'category_id' => $categoryId]) }}" target="_blank" class="btn btn-outline-secondary btn-xs">
                        <span class="fas fa-print me-1"></span>Print Worksheet
                    </a>
                </div>

                <div class="table-responsive scrollbar mb-3">
                    <table class="table table-sm table-striped fs-10 align-middle mb-0">
                        <thead class="bg-light text-900">
                            <tr>
                                <th class="ps-3" style="width: 100px;">Code</th>
                                <th>Item Name</th>
                                <th style="width: 110px;">Category</th>
                                <th class="text-end" style="width: 85px;">Unit Cost</th>
                                <th class="text-end" style="width: 95px;">System Qty</th>
                                <th class="text-end" style="width: 130px;">Physical Qty</th>
                                <th class="text-end" style="width: 90px;">Difference</th>
                                <th class="text-end" style="width: 100px;">Variance Value</th>
                                <th>Variance Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($formItems as $index => $item)
                                @php
                                    $pQty = (float)($formItems[$index]['physical_qty'] ?? 0);
                                    $sQty = (float)$item['system_qty'];
                                    $diff = $pQty - $sQty;
                                    $unitCost = (float)($item['unit_cost'] ?? 0);
                                    $rowDiffVal = $diff * $unitCost;
                                @endphp
                                <tr>
                                    <td class="ps-3 font-monospace">{{ $item['item_code'] }}</td>
                                    <td class="fw-semi-bold">{{ $item['name'] }}</td>
                                    <td><span class="badge bg-light text-dark">{{ $item['category_name'] ?? '—' }}</span></td>
                                    <td class="text-end font-monospace text-muted">PKR {{ number_format($unitCost, 2) }}</td>
                                    <td class="text-end font-monospace text-muted">{{ number_format($item['system_qty'], 2) }}</td>
                                    <td class="text-end">
                                        <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end font-monospace py-0 fs-11 ms-auto" style="width: 110px;" wire:model.live.debounce.300ms="formItems.{{ $index }}.physical_qty">
                                        @error("formItems.{$index}.physical_qty") <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                                    </td>
                                    <td class="text-end font-monospace fw-bold {{ $diff == 0 ? 'text-muted' : ($diff > 0 ? 'text-success' : 'text-danger') }}">
                                        {{ $diff > 0 ? '+' : '' }}{{ number_format($diff, 2) }}
                                    </td>
                                    <td class="text-end font-monospace fw-bold {{ $rowDiffVal == 0 ? 'text-muted' : ($rowDiffVal > 0 ? 'text-success' : 'text-danger') }}">
                                        {{ $rowDiffVal > 0 ? '+' : '' }}PKR {{ number_format($rowDiffVal, 2) }}
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm py-0 fs-11" placeholder="e.g. spillage, damaged" wire:model="formItems.{{ $index }}.reason">
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">No items match filters.</td>
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
                                <div class="col-sm-3">
                                    <span class="text-muted fs-11 d-block mb-1">Status</span>
                                    @if($viewStockTake->status === 'Draft')
                                        <span class="badge badge-subtle-warning rounded-pill">Draft</span>
                                    @elseif($viewStockTake->status === 'Approved')
                                        <span class="badge badge-subtle-success rounded-pill">Approved</span>
                                    @else
                                        <span class="badge badge-subtle-secondary rounded-pill">Cancelled</span>
                                    @endif
                                </div>
                                <div class="col-sm-3">
                                    <span class="text-muted fs-11 d-block mb-1">Branch</span>
                                    <strong>{{ $viewStockTake->branch->name ?? '—' }}</strong>
                                </div>
                                <div class="col-sm-3">
                                    <span class="text-muted fs-11 d-block mb-1">Count Date</span>
                                    <strong>{{ $viewStockTake->count_date->format('Y-m-d') }}</strong>
                                </div>
                                <div class="col-sm-3">
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
                    <div class="modal-footer bg-light d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-success" wire:click="exportStockTakeExcel({{ $viewStockTake->id }})">
                                    <span class="fas fa-file-excel me-1"></span>Excel
                                </button>
                                <a href="{{ route('inventory.stock-takes.pdf', $viewStockTake->id) }}" target="_blank" class="btn btn-outline-danger">
                                    <span class="fas fa-file-pdf me-1"></span>PDF
                                </a>
                                <a href="{{ route('inventory.stock-takes.print', $viewStockTake->id) }}" target="_blank" class="btn btn-outline-secondary">
                                    <span class="fas fa-print me-1"></span>Print
                                </a>
                            </div>

                            @if($viewStockTake->status === 'Draft')
                                @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('inventory.adjust'))
                                    <button type="button"
                                        class="btn btn-success btn-sm ms-2"
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
