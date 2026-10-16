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

    <!-- Return Form Card -->
    <div class="card border border-200 shadow-none">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-0">
                    <span class="fas fa-undo me-2 text-primary"></span>
                    {{ $editId ? "Purchase Return #{$return_number}" : 'Record Purchase Return' }}
                </h5>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a href="{{ route('purchase-returns.index') }}" class="btn btn-falcon-default btn-sm">
                    <span class="fas fa-chevron-left me-1"></span>Back to List
                </a>

                @if($editId)
                    <!-- Print -->
                    <a href="{{ route('purchase-returns.print', $editId) }}" target="_blank" class="btn btn-falcon-default btn-sm">
                        <span class="fas fa-print me-1 text-primary"></span>Print
                    </a>

                    <!-- Download PDF -->
                    <a href="{{ route('purchase-returns.pdf', $editId) }}" target="_blank" class="btn btn-falcon-default btn-sm">
                        <span class="fas fa-file-pdf me-1 text-danger"></span>Download PDF
                    </a>

                    <!-- Share Dropdown -->
                    <div class="dropdown font-sans-serif d-inline-block">
                        <button class="btn btn-falcon-default btn-sm dropdown-toggle" type="button" id="shareFormRet" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="fas fa-share-alt me-1 text-info"></span>Share
                        </button>
                        <div class="dropdown-menu dropdown-menu-end py-2" aria-labelledby="shareFormRet">
                            @php
                                $retSupplier = $return->supplier ?? null;
                                $retShareText = urlencode("Purchase Return Debit Note #{$return_number}\nDate: {$return_date}\nAmount: Rs. " . number_format($net_amount, 2));
                                $retPhone = preg_replace('/[^0-9]/', '', $retSupplier->mobile_number ?? '');
                                $waRetUrl = $retPhone ? "https://wa.me/{$retPhone}?text={$retShareText}" : "https://api.whatsapp.com/send?text={$retShareText}";
                            @endphp
                            <a class="dropdown-item d-flex align-items-center" href="{{ $waRetUrl }}" target="_blank">
                                <span class="fab fa-whatsapp me-2 text-success"></span>Share via WhatsApp
                            </a>
                            <button class="dropdown-item d-flex align-items-center" type="button" onclick="navigator.clipboard.writeText('{{ route('purchase-returns.print', $editId) }}'); alert('Return Note link copied to clipboard!');">
                                <span class="fas fa-link me-2 text-primary"></span>Copy Link
                            </button>
                        </div>
                    </div>
                @endif

                @if($status === 'Draft' && $editId && (auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_accounting')))
                    <button wire:click="postToAccounts" class="btn btn-success btn-sm">
                        <span class="fas fa-file-export me-1"></span>Post to Accounts (JV)
                    </button>
                @endif
            </div>
        </div>

        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label" for="return-num">Return Number *</label>
                    <input wire:model="return_number" type="text" class="form-control form-control-sm font-monospace fw-bold" id="return-num" disabled />
                    @error('return_number') <div class="text-danger fs-12 mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="invoice-ref">Reference Purchase Invoice</label>
                    <select wire:model.live="purchase_invoice_id" class="form-select form-select-sm" id="invoice-ref" {{ $status !== 'Draft' ? 'disabled' : '' }}>
                        <option value="">-- Direct Return (No Invoice Ref) --</option>
                        @foreach($invoices as $inv)
                            <option value="{{ $inv->id }}">{{ $inv->invoice_number }} (Rs. {{ number_format($inv->net_amount, 2) }})</option>
                        @endforeach
                    </select>
                    @error('purchase_invoice_id') <div class="text-danger fs-12 mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="supplier">Supplier *</label>
                    <select wire:model="supplier_id" class="form-select form-select-sm" id="supplier" {{ ($status !== 'Draft' || $purchase_invoice_id) ? 'disabled' : '' }}>
                        <option value="">Select Supplier</option>
                        @foreach($suppliers as $supp)
                            <option value="{{ $supp->id }}">{{ $supp->name }}</option>
                        @endforeach
                    </select>
                    @error('supplier_id') <div class="text-danger fs-12 mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="branch">Posting Branch *</label>
                    <select wire:model="branch_id" class="form-select form-select-sm" id="branch" {{ ($status !== 'Draft' || $purchase_invoice_id) ? 'disabled' : '' }}>
                        <option value="">Select Branch</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                    @error('branch_id') <div class="text-danger fs-12 mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="return-date">Return Date *</label>
                    <input wire:model="return_date" type="date" class="form-control form-control-sm" id="return-date" {{ $status !== 'Draft' ? 'disabled' : '' }} />
                    @error('return_date') <div class="text-danger fs-12 mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="reason">Reason for Return</label>
                    <input wire:model="reason" type="text" class="form-control form-control-sm" id="reason" placeholder="e.g. Damaged goods, excess stock" {{ $status !== 'Draft' ? 'disabled' : '' }} />
                    @error('reason') <div class="text-danger fs-12 mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="notes">Notes / Remarks</label>
                    <input wire:model="notes" type="text" class="form-control form-control-sm" id="notes" placeholder="Additional details..." {{ $status !== 'Draft' ? 'disabled' : '' }} />
                    @error('notes') <div class="text-danger fs-12 mt-1">{{ $message }}</div> @enderror
                </div>
            </div>

            <!-- Mapped JV summary (if posted) -->
            @if($status === 'Posted' && $return && $return->journalVoucher)
                <div class="alert alert-info border-translucent d-flex align-items-center mb-4" role="alert">
                    <span class="fas fa-info-circle fs-8 me-3"></span>
                    <p class="mb-0 flex-grow-1 fs-12">
                        This purchase return has been posted to Chart of Accounts. Mapped Journal Voucher: 
                        <strong class="font-monospace text-primary">#{{ $return->journalVoucher->voucher_no }}</strong>
                        on date <strong class="text-dark">{{ $return->journalVoucher->voucher_date->format('Y-m-d') }}</strong>.
                    </p>
                </div>
            @endif

            <!-- Dynamic grid row entry (only active in Draft mode and when NOT referencing an invoice) -->
            @if($status === 'Draft')
                <div class="bg-light p-3 border rounded mb-4">
                    <h6 class="fw-bold mb-3">
                        <span class="fas fa-plus-circle me-1 text-primary"></span>
                        {{ $purchase_invoice_id ? 'Returned Items (Populated from invoice)' : 'Add Return Item Row' }}
                    </h6>
                    @if(!$purchase_invoice_id)
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
                                <label class="form-label fs-11" for="item-qty">Return Qty *</label>
                                <input wire:model.live="selectedQty" type="number" step="0.01" class="form-control form-control-sm text-end" id="item-qty" />
                                @error('selectedQty') <div class="text-danger fs-12 mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-2">
                                <label class="form-label fs-11" for="item-rate">Unit Cost (Rs.) *</label>
                                <input wire:model.live="selectedRate" type="number" step="0.01" class="form-control form-control-sm text-end" id="item-rate" />
                                @error('selectedRate') <div class="text-danger fs-12 mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-2">
                                <label class="form-label fs-11 fw-bold text-danger" for="item-amount">Item Amount (Rs.)</label>
                                <input wire:model.live="selectedAmount" type="number" step="0.01" class="form-control form-control-sm text-end font-monospace fw-bold bg-white text-danger" id="item-amount" title="Return Qty × Unit Cost = Item Amount" placeholder="0.00" />
                            </div>

                            <div class="col-md-2">
                                <button type="button" wire:click="addLine" class="btn btn-primary btn-sm w-100">
                                    <span class="fas fa-plus-circle me-1"></span>Add Line
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-subtle-info d-flex align-items-center mb-0 py-2" role="alert">
                            <span class="fas fa-info-circle me-2 fs-9"></span>
                            <span class="fs-12">Items loaded from selected Invoice. <strong>Return only what remains:</strong> you can adjust quantities for partially used items or click <span class="fas fa-trash-alt text-danger"></span> to exclude items that were completely used up.</span>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Return Item Grid -->
            <div class="table-responsive border rounded mb-4">
                <table class="table table-sm table-striped fs-10 mb-0 align-middle">
                    <thead class="bg-200 text-900">
                        <tr>
                            <th class="ps-3" style="width: 40px;">#</th>
                            <th style="width: 130px;">Item Code</th>
                            <th>Item Name</th>
                            <th style="width: 60px;">Unit</th>
                            @if($purchase_invoice_id)
                                <th class="text-end" style="width: 90px;" title="Original Invoiced Qty">Invoiced</th>
                                <th class="text-end" style="width: 90px;" title="Previously Returned Qty">Prev. Ret.</th>
                                <th class="text-end" style="width: 100px;" title="Remaining Available to Return">Available</th>
                            @endif
                            <th class="text-end" style="width: 110px;">Return Qty</th>
                            <th class="text-end" style="width: 130px;">Unit Cost</th>
                            <th class="text-end px-3" style="width: 140px;">Total Amount</th>
                            @if($status === 'Draft')
                                <th class="text-center" style="width: 70px;">Action</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $idx => $line)
                            <tr>
                                <td class="ps-3 font-mono">{{ $idx + 1 }}</td>
                                <td class="font-monospace fw-semi-bold">{{ $line['item_code'] }}</td>
                                <td class="fw-bold">{{ $line['item_name'] }}</td>
                                <td><span class="badge bg-light text-dark">{{ $line['unit'] }}</span></td>
                                @if($purchase_invoice_id)
                                    <td class="text-end font-monospace text-muted">{{ number_format($line['invoiced_qty'] ?? 0, 2) }}</td>
                                    <td class="text-end font-monospace text-muted">{{ number_format($line['already_returned'] ?? 0, 2) }}</td>
                                    <td class="text-end font-monospace fw-bold text-success">{{ number_format($line['remaining_qty'] ?? $line['quantity'], 2) }}</td>
                                @endif
                                <td class="text-end font-monospace">
                                    @if($status === 'Draft')
                                        <input type="number" step="0.01" min="0.01" @if(isset($line['remaining_qty'])) max="{{ $line['remaining_qty'] }}" @endif wire:model.live.blur="items.{{ $idx }}.quantity" wire:change="recalculateAmounts" class="form-control form-control-sm text-end font-monospace d-inline-block" style="max-width: 100px;" />
                                    @else
                                        {{ number_format($line['quantity'], 2) }}
                                    @endif
                                </td>
                                <td class="text-end font-monospace">
                                    @if($status === 'Draft')
                                        <input type="number" step="0.01" wire:model.live.blur="items.{{ $idx }}.unit_cost" wire:change="recalculateAmounts" class="form-control form-control-sm text-end font-monospace d-inline-block" style="max-width: 110px;" />
                                    @else
                                        Rs. {{ number_format($line['unit_cost'], 2) }}
                                    @endif
                                </td>
                                <td class="text-end px-3 font-monospace fw-bold text-dark">
                                    @php
                                        $amt = floatval($line['quantity']) * floatval($line['unit_cost']);
                                        $items[$idx]['amount'] = $amt;
                                    @endphp
                                    Rs. {{ number_format($amt, 2) }}
                                </td>
                                @if($status === 'Draft')
                                    <td class="text-center">
                                        <button type="button" wire:click="removeLine({{ $idx }})" class="btn btn-link text-danger p-0" title="Exclude this item (do not return)">
                                            <span class="fas fa-trash-alt"></span>
                                        </button>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $purchase_invoice_id ? 11 : 8 }}" class="text-center py-4 text-muted">No items selected for return.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Cost Math totals layout -->
            <div class="row justify-content-end mb-3">
                <div class="col-md-4">
                    <table class="table table-sm table-borderless fs-11">
                        <tr>
                            <td class="text-600 font-sans-serif fw-bold">Gross Subtotal:</td>
                            <td class="text-end font-monospace fw-bold text-dark">Rs. {{ number_format($gross_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="text-600 font-sans-serif fw-bold align-middle">Tax Returned/Adjusted (Rs.):</td>
                            <td class="text-end">
                                @if($status === 'Draft')
                                    <input wire:model.live.blur="tax" type="number" step="0.01" class="form-control form-control-sm font-monospace text-end float-end" style="max-width: 150px;" />
                                @else
                                    <span class="font-monospace fw-bold text-success">+ Rs. {{ number_format($tax, 2) }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr class="border-top table-light fw-black font-monospace fs-9">
                            <td class="text-800">Net Amount Refunded:</td>
                            <td class="text-end text-danger fs-8">Rs. {{ number_format($net_amount, 2) }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Validation list -->
            @error('items') <div class="text-danger fs-11 mb-3 fw-bold">{{ $message }}</div> @enderror

            @if($status === 'Draft')
                <div class="d-flex justify-content-end gap-2 border-top pt-3">
                    <a href="{{ route('purchase-returns.index') }}" class="btn btn-falcon-default btn-sm">Discard</a>
                    <button type="button" wire:click="save" class="btn btn-falcon-primary btn-sm">
                        <span class="fas fa-save me-1"></span>Save Draft Return
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>
