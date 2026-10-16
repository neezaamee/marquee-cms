<div>
    <!-- Session alerts -->
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

    <!-- PO Form Card -->
    <div class="card border border-200 shadow-none">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-0">
                    <span class="fas fa-file-invoice me-2 text-primary"></span>
                    {{ $editId ? "Purchase Order #{$po_number}" : 'Create New Purchase Order' }}
                </h5>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('purchase-orders.index') }}" class="btn btn-falcon-default btn-sm">
                    <span class="fas fa-chevron-left me-1"></span>Back to List
                </a>

                @if($status === 'Draft' && $editId && (auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_inventory')))
                    <button wire:click="approve" class="btn btn-success btn-sm">
                        <span class="fas fa-check-circle me-1"></span>Approve PO
                    </button>
                @endif
                
                @if($status === 'Approved' && (auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_inventory')))
                    <a href="{{ route('goods-receipts.create', ['po' => $editId]) }}" class="btn btn-falcon-success btn-sm">
                        <span class="fas fa-truck-loading me-1"></span>Record Goods Receipt (GRN)
                    </a>
                @endif
            </div>
        </div>

        <div class="card-body">
            <div class="row g-3 mb-4">
                <!-- Header Fields Section -->
                <div class="col-md-3">
                    <label class="form-label" for="supplier">Supplier *</label>
                    <select wire:model="supplier_id" class="form-select form-select-sm" id="supplier" {{ $status !== 'Draft' ? 'disabled' : '' }}>
                        <option value="">Select Supplier</option>
                        @foreach($suppliers as $supp)
                            <option value="{{ $supp->id }}">
                                {{ $supp->name }}{{ $supp->categories->isNotEmpty() ? ' [' . $supp->categories->pluck('name')->join(', ') . ']' : '' }} (Bal: Rs. {{ number_format($supp->current_balance) }})
                            </option>
                        @endforeach
                    </select>
                    @error('supplier_id') <div class="text-danger fs-12 mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="branch">Destination Branch *</label>
                    <select wire:model="branch_id" class="form-select form-select-sm" id="branch" {{ $status !== 'Draft' ? 'disabled' : '' }}>
                        <option value="">Select Branch</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                    @error('branch_id') <div class="text-danger fs-12 mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="order-date">Order Date *</label>
                    <input wire:model="order_date" type="date" class="form-control form-control-sm" id="order-date" {{ $status !== 'Draft' ? 'disabled' : '' }} />
                    @error('order_date') <div class="text-danger fs-12 mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="delivery-date">Expected Delivery</label>
                    <input wire:model="expected_delivery_date" type="date" class="form-control form-control-sm" id="delivery-date" {{ $status !== 'Draft' ? 'disabled' : '' }} />
                    @error('expected_delivery_date') <div class="text-danger fs-12 mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <label class="form-label" for="po-notes">PO Notes / Remarks</label>
                    <textarea wire:model="notes" class="form-control form-control-sm" id="po-notes" rows="2" placeholder="Purchase notes..." {{ $status !== 'Draft' ? 'disabled' : '' }}></textarea>
                </div>
            </div>

            <!-- Dynamic grid only active in Draft mode -->
            @if($status === 'Draft')
                <div class="bg-light p-3 border rounded mb-4">
                    <h6 class="fw-bold mb-3"><span class="fas fa-plus me-1 text-primary"></span>Add Item Row</h6>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4 position-relative" 
                             x-data="{
                                open: false,
                                search: '',
                                activeIndex: -1,
                                items: {{ json_encode($catalogItemsData ?? []) }},
                                get filteredItems() {
                                    if (!this.search.trim()) return this.items;
                                    const q = this.search.toLowerCase();
                                    return this.items.filter(i => 
                                        (i.item_code && i.item_code.toLowerCase().includes(q)) || 
                                        (i.name && i.name.toLowerCase().includes(q))
                                    );
                                },
                                init() {
                                    const selId = $wire.selectedItemId;
                                    if (selId) {
                                        const found = this.items.find(i => i.id == selId);
                                        if (found) {
                                            this.search = found.item_code + ' - ' + found.name;
                                        }
                                    }
                                    this.$watch('$wire.selectedItemId', val => {
                                        if (!val) {
                                            this.search = '';
                                            this.activeIndex = -1;
                                        } else {
                                            const found = this.items.find(i => i.id == val);
                                            if (found && !this.search.includes(found.name)) {
                                                this.search = found.item_code + ' - ' + found.name;
                                            }
                                        }
                                    });
                                },
                                selectItem(item) {
                                    this.search = item.item_code + ' - ' + item.name;
                                    this.open = false;
                                    this.activeIndex = -1;
                                    $wire.selectCatalogItem(item.id);
                                    this.$nextTick(() => {
                                        const qtyEl = document.getElementById('item-qty');
                                        if (qtyEl) {
                                            qtyEl.focus();
                                            qtyEl.select();
                                        }
                                    });
                                },
                                clearSelection() {
                                    this.search = '';
                                    this.open = true;
                                    this.activeIndex = -1;
                                    $wire.selectCatalogItem('');
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
                                            const el = this.$refs.dropdownList.querySelector(`[data-index='${this.activeIndex}']`);
                                            if (el) el.scrollIntoView({ block: 'nearest' });
                                        }
                                    });
                                }
                             }"
                             @item-catalog-reset.window="search = ''; open = false; activeIndex = -1;"
                             @click.outside="open = false; activeIndex = -1;">
                            <label class="form-label fs-11" for="item-search-input">Item Catalog *</label>
                            <div class="position-relative">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text px-2 bg-white border-end-0">
                                        <span class="fas fa-search text-400 fs-12"></span>
                                    </span>
                                    <input x-ref="itemSearchInput"
                                           id="item-search-input"
                                           type="text"
                                           class="form-control form-control-sm border-start-0 @error('selectedItemId') is-invalid @enderror"
                                           placeholder="Search code or name (use ↑ / ↓)..."
                                           autocomplete="off"
                                           x-model="search"
                                           @focus="open = true"
                                           @click="open = true"
                                           @keydown="handleKeydown($event)" />
                                    <template x-if="search">
                                        <button type="button" 
                                                class="btn btn-outline-secondary btn-sm border-start-0 px-2 text-muted" 
                                                @click="clearSelection()"
                                                title="Clear selection">
                                            <span class="fas fa-times fs-12"></span>
                                        </button>
                                    </template>
                                </div>

                                <!-- Hidden input for form fallback -->
                                <input type="hidden" wire:model="selectedItemId" />

                                <!-- Searchable items dropdown -->
                                <div x-show="open" 
                                     x-transition:enter="transition ease-out duration-100"
                                     x-transition:enter-start="opacity-0 transform scale-95"
                                     x-transition:enter-end="opacity-100 transform scale-100"
                                     x-transition:leave="transition ease-in duration-75"
                                     x-transition:leave-start="opacity-100 transform scale-100"
                                     x-transition:leave-end="opacity-0 transform scale-95"
                                     x-ref="dropdownList"
                                     class="position-absolute bg-white border rounded shadow-lg w-100 z-3 mt-1 overflow-hidden" 
                                     style="max-height: 250px; overflow-y: auto; z-index: 1050; display: none;">
                                    <template x-for="(item, idx) in filteredItems" :key="item.id">
                                        <div :data-index="idx"
                                             @mouseenter="activeIndex = idx"
                                             @click="selectItem(item)"
                                             :class="activeIndex === idx ? 'bg-primary text-white' : 'hover-bg-light text-900'"
                                             class="d-flex justify-content-between align-items-center py-2 px-3 border-bottom border-translucent cursor-pointer text-start"
                                             style="user-select: none;">
                                            <div>
                                                <span class="font-monospace fw-bold me-1" :class="activeIndex === idx ? 'text-white' : 'text-primary'" x-text="'[' + item.item_code + ']'"></span>
                                                <span class="fw-semi-bold" x-text="item.name"></span>
                                            </div>
                                            <div class="text-end ps-2 text-nowrap">
                                                <span class="badge" :class="activeIndex === idx ? 'bg-white text-primary' : 'bg-light text-dark'" x-text="item.unit"></span>
                                                <span class="font-monospace fs-11 ms-1" :class="activeIndex === idx ? 'text-white' : 'text-muted'" x-text="'Rs. ' + Number(item.rate).toFixed(2)"></span>
                                            </div>
                                        </div>
                                    </template>
                                    <template x-if="filteredItems.length === 0">
                                        <div class="text-center py-3 text-muted fs-11">
                                            <span class="fas fa-exclamation-circle me-1"></span>No items match "<span class="fw-bold" x-text="search"></span>"
                                        </div>
                                    </template>
                                </div>
                            </div>
                            @error('selectedItemId') <div class="text-danger fs-12 mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-2">
                            <label class="form-label fs-11" for="item-qty">Quantity *</label>
                            <input wire:model.live="selectedQty" type="number" step="0.01" class="form-control form-control-sm text-end" id="item-qty" />
                            @error('selectedQty') <div class="text-danger fs-12 mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-2">
                            <label class="form-label fs-11" for="item-rate">Unit Purchase Rate (Rs.) *</label>
                            <input wire:model.live="selectedRate" type="number" step="0.01" class="form-control form-control-sm text-end" id="item-rate" />
                            @error('selectedRate') <div class="text-danger fs-12 mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-2">
                            <label class="form-label fs-11 fw-bold text-primary" for="item-amount">Item Amount (Rs.)</label>
                            <input wire:model.live="selectedAmount" type="number" step="0.01" class="form-control form-control-sm text-end font-monospace fw-bold bg-white text-primary" id="item-amount" title="Quantity × Unit Rate = Item Amount" placeholder="0.00" />
                        </div>

                        <div class="col-md-2">
                            <button type="button" wire:click="addLine" class="btn btn-primary btn-sm w-100">
                                <span class="fas fa-plus-circle me-1"></span>Add Line
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            <!-- PO Items Grid -->
            <div class="table-responsive border rounded mb-3">
                <table class="table table-sm table-striped fs-10 mb-0 align-middle">
                    <thead class="bg-200 text-900">
                        <tr>
                            <th class="ps-3" style="width: 50px;">#</th>
                            <th style="width: 150px;">Item Code</th>
                            <th>Item Name</th>
                            <th>Unit</th>
                            <th class="text-end" style="width: 120px;">Quantity</th>
                            <th class="text-end" style="width: 150px;">Unit Rate</th>
                            <th class="text-end" style="width: 150px;">Total Amount</th>
                            @if($status !== 'Draft')
                                <th class="text-end" style="width: 150px;">Received Qty</th>
                            @endif
                            @if($status === 'Draft')
                                <th class="text-center" style="width: 80px;">Action</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $idx => $line)
                            <tr>
                                <td class="ps-3">{{ $idx + 1 }}</td>
                                <td class="font-monospace fw-semi-bold">{{ $line['item_code'] }}</td>
                                <td class="fw-bold">{{ $line['item_name'] }}</td>
                                <td><span class="badge bg-light text-dark">{{ $line['unit'] }}</span></td>
                                <td class="text-end font-monospace">{{ number_format($line['quantity'], 2) }}</td>
                                <td class="text-end font-monospace">Rs. {{ number_format($line['unit_price'], 2) }}</td>
                                <td class="text-end font-monospace fw-bold text-dark">Rs. {{ number_format($line['amount'], 2) }}</td>
                                @if($status !== 'Draft')
                                    <td class="text-end font-monospace text-success fw-bold">
                                        {{ number_format($po->details->firstWhere('item_id', $line['item_id'])->received_quantity ?? 0, 2) }}
                                    </td>
                                @endif
                                @if($status === 'Draft')
                                    <td class="text-center">
                                        <button type="button" wire:click="removeLine({{ $idx }})" class="btn btn-link text-danger p-0" title="Delete Row">
                                            <span class="fas fa-trash-alt"></span>
                                        </button>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">No items added to this PO.</td>
                            </tr>
                        @endforelse
                        <tr class="table-info fw-bold font-monospace fs-11">
                            <td colspan="6" class="text-end text-800">PO Grand Total:</td>
                            <td class="text-end text-dark fs-9">Rs. {{ number_format($totalSum, 2) }}</td>
                            @if($status !== 'Draft')
                                <td></td>
                            @endif
                            @if($status === 'Draft')
                                <td></td>
                            @endif
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Validation list -->
            @error('items') <div class="text-danger fs-11 mb-3 fw-bold">{{ $message }}</div> @enderror

            @if($status === 'Draft')
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('purchase-orders.index') }}" class="btn btn-falcon-default btn-sm">Discard</a>
                    <button type="button" wire:click="save" class="btn btn-falcon-primary btn-sm">
                        <span class="fas fa-save me-1"></span>Save PO
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>
