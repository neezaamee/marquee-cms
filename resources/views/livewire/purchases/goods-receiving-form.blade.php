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

    <!-- GRN Form Card -->
    <div class="card border border-200 shadow-none">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-0">
                    <span class="fas fa-truck-loading me-2 text-primary"></span>
                    {{ $editId ? "Goods Receiving Note #{$grn_number}" : 'Log Goods Receiving Note (GRN)' }}
                </h5>
            </div>
            <div>
                <a href="{{ route('goods-receipts.index') }}" class="btn btn-falcon-default btn-sm">
                    <span class="fas fa-chevron-left me-1"></span>Back to List
                </a>
            </div>
        </div>

        <div class="card-body">
            <div class="row g-3 mb-4">
                @if(!$editId)
                    <div class="col-md-4">
                        <label class="form-label" for="po-select">Reference Purchase Order *</label>
                        <select wire:model.live="purchase_order_id" class="form-select form-select-sm" id="po-select">
                            <option value="">Select Approved PO</option>
                            @foreach($pendingPOs as $po)
                                <option value="{{ $po->id }}">{{ $po->po_number }} ({{ $po->supplier->name }})</option>
                            @endforeach
                        </select>
                        @error('purchase_order_id') <div class="text-danger fs-12 mt-1">{{ $message }}</div> @enderror
                    </div>
                @else
                    <div class="col-md-4">
                        <label class="form-label">Reference PO</label>
                        <input type="text" class="form-control form-control-sm" value="{{ $grn->purchaseOrder->po_number }} ({{ $grn->supplier->name }})" disabled />
                    </div>
                @endif

                <div class="col-md-4">
                    <label class="form-label" for="grn-date">Received Date *</label>
                    <input wire:model="received_date" type="date" class="form-control form-control-sm" id="grn-date" {{ $editId ? 'disabled' : '' }} />
                    @error('received_date') <div class="text-danger fs-12 mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Receiving Branch</label>
                    <input type="text" class="form-control form-control-sm" value="{{ $editId ? $grn->branch->name : auth()->user()->branch->name ?? 'Head Office' }}" disabled />
                </div>

                <div class="col-12">
                    <label class="form-label" for="grn-notes">Receiving Notes / Remarks</label>
                    <textarea wire:model="notes" class="form-control form-control-sm" id="grn-notes" rows="2" placeholder="Delivery notes..." {{ $editId ? 'disabled' : '' }}></textarea>
                </div>
            </div>

            <!-- Items Table -->
            @if(count($items) > 0)
                <div class="table-responsive border rounded mb-3">
                    <table class="table table-sm table-striped fs-10 mb-0 align-middle">
                        <thead class="bg-200 text-900">
                            <tr>
                                <th class="ps-3" style="width: 50px;">#</th>
                                <th style="width: 150px;">Item Code</th>
                                <th>Item Name</th>
                                <th>Unit</th>
                                <th class="text-end" style="width: 130px;">Ordered Qty</th>
                                @if(!$editId)
                                    <th class="text-end" style="width: 130px;">Already Received</th>
                                    <th class="text-end" style="width: 130px;">Remaining Qty</th>
                                @endif
                                <th class="text-end" style="width: 180px;">Received Qty (In this GRN)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $idx => $line)
                                <tr>
                                    <td class="ps-3">{{ $idx + 1 }}</td>
                                    <td class="font-monospace fw-semi-bold">{{ $line['item_code'] }}</td>
                                    <td class="fw-bold">{{ $line['item_name'] }}</td>
                                    <td><span class="badge bg-light text-dark">{{ $line['unit'] }}</span></td>
                                    <td class="text-end font-monospace">{{ number_format($line['ordered_qty'], 2) }}</td>
                                    @if(!$editId)
                                        <td class="text-end font-monospace text-muted">{{ number_format($line['already_received'], 2) }}</td>
                                        <td class="text-end font-monospace text-primary fw-semi-bold">{{ number_format($line['remaining'], 2) }}</td>
                                    @endif
                                    <td class="text-end">
                                        @if(!$editId)
                                            <input wire:model="items.{{ $idx }}.received_qty" type="number" step="0.01" class="form-control form-control-sm font-monospace text-end float-end" style="max-width: 130px;" />
                                            @error("items.{$idx}.received_qty") <div class="text-danger fs-12 mt-1">{{ $message }}</div> @enderror
                                        @else
                                            <span class="font-monospace fw-bold text-success">{{ number_format($line['received_qty'], 2) }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if(!$editId && count($items) > 0)
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('goods-receipts.index') }}" class="btn btn-falcon-default btn-sm">Discard</a>
                    <button type="button" wire:click="save" class="btn btn-falcon-success btn-sm">
                        <span class="fas fa-check-circle me-1"></span>Log Goods Received
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>
