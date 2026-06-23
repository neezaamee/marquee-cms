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
                            <option value="{{ $supp->id }}">{{ $supp->name }} (Bal: Rs. {{ number_format($supp->current_balance) }})</option>
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
                        <div class="col-md-5">
                            <label class="form-label fs-11" for="item-select">Item Catalog *</label>
                            <select wire:model.live="selectedItemId" class="form-select form-select-sm" id="item-select">
                                <option value="">Select Item</option>
                                @foreach($catalogItems as $item)
                                    <option value="{{ $item->id }}">{{ $item->item_code }} - {{ $item->name }}</option>
                                @endforeach
                            </select>
                            @error('selectedItemId') <div class="text-danger fs-12 mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-2">
                            <label class="form-label fs-11" for="item-qty">Quantity *</label>
                            <input wire:model="selectedQty" type="number" step="0.01" class="form-control form-control-sm" id="item-qty" />
                            @error('selectedQty') <div class="text-danger fs-12 mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fs-11" for="item-rate">Unit Purchase Rate (Rs.) *</label>
                            <input wire:model="selectedRate" type="number" step="0.01" class="form-control form-control-sm" id="item-rate" />
                            @error('selectedRate') <div class="text-danger fs-12 mt-1">{{ $message }}</div> @enderror
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
