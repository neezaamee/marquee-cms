<div>
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h6 class="fw-bold mb-0"><i class="fas fa-shopping-cart text-primary me-2"></i>Vendor Sales & Commission Journal</h6>
            <div class="text-secondary fs-11">Sales transactions for contracted event vendors and commission revenue snapshots.</div>
        </div>
        <button wire:click="openCreateModal" class="btn btn-success btn-xs"><i class="fas fa-plus me-1"></i> Record Vendor Sale</button>
    </div>

    @if(session('success'))
        <div class="alert alert-success py-2 fs-12 mb-3">{{ session('success') }}</div>
    @endif

    <!-- Search & Filters -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-2">
            <div class="row g-2">
                <div class="col-md-6">
                    <input wire:model.live.debounce.300ms="search" type="text" class="form-control form-control-sm" placeholder="Search by sale #, vendor name, booking #...">
                </div>
                @if(!$vendor)
                    <div class="col-md-4">
                        <select wire:model.live="filterVendorId" class="form-select form-select-sm">
                            <option value="">-- All Vendors --</option>
                            @foreach($vendors as $v)
                                <option value="{{ $v->id }}">{{ $v->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-2">
                    <button wire:click="$set('search', ''); $set('filterVendorId', '');" class="btn btn-outline-secondary btn-sm w-100"><i class="fas fa-undo me-1"></i> Reset</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Sales Table -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 fs-12">
                <thead class="bg-200">
                    <tr>
                        <th>Sale #</th>
                        @if(!$vendor) <th>Vendor</th> @endif
                        <th>Booking / Customer</th>
                        <th>Event Date</th>
                        <th>Sale Amount</th>
                        <th>Commission Income</th>
                        <th>Net Vendor Payable</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $sale)
                        <tr>
                            <td class="fw-bold font-monospace text-primary">{{ $sale->vendor_sale_number }}</td>
                            @if(!$vendor)
                                <td class="fw-semibold">{{ $sale->vendor->name ?? '—' }}</td>
                            @endif
                            <td>
                                @if($sale->booking)
                                    <div class="fw-bold text-dark"><i class="fas fa-calendar-check me-1 text-info"></i>{{ $sale->booking->booking_number }}</div>
                                    <div class="text-secondary fs-11">{{ $sale->booking->customer->full_name ?? '' }}</div>
                                @else
                                    <span class="text-muted">Direct Vendor Sale</span>
                                @endif
                            </td>
                            <td>{{ $sale->event_date->format('d-M-Y') }}</td>
                            <td class="fw-bold text-dark">Rs. {{ number_format($sale->sale_amount) }}</td>
                            <td class="fw-bold text-success">
                                Rs. {{ number_format($sale->commission_amount) }}
                                <span class="badge badge-subtle-success fs-10 me-1">{{ $sale->commission_rate }}%</span>
                            </td>
                            <td class="fw-bold text-primary">Rs. {{ number_format($sale->vendor_net_amount) }}</td>
                            <td>
                                @if($sale->status === 'confirmed')
                                    <span class="badge badge-subtle-success">Confirmed</span>
                                @elseif($sale->status === 'settled')
                                    <span class="badge badge-subtle-info">Settled</span>
                                @else
                                    <span class="badge badge-subtle-secondary">{{ ucfirst($sale->status) }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $vendor ? 7 : 8 }}" class="text-center py-4 text-muted">No vendor sales recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($sales->hasPages())
            <div class="card-footer bg-light py-2">
                {{ $sales->links() }}
            </div>
        @endif
    </div>

    <!-- Record Sale Modal -->
    @if($showSaleModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" role="dialog">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-success text-white py-2">
                        <h6 class="modal-title fw-bold fs-13"><i class="fas fa-shopping-cart me-2"></i>Record New Vendor Sale</h6>
                        <button wire:click="$set('showSaleModal', false)" type="button" class="btn-close btn-close-white" aria-label="Close"></button>
                    </div>
                    <form wire:submit.prevent="saveSale">
                        <div class="modal-body p-3 fs-12">
                            <div class="row g-2">
                                @if(!$vendor)
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label fw-bold">Select Vendor <span class="text-danger">*</span></label>
                                        <select wire:model.live="vendor_id" class="form-select form-select-sm">
                                            <option value="">-- Choose Vendor --</option>
                                            @foreach($vendors as $v)
                                                <option value="{{ $v->id }}">{{ $v->name }} ({{ $v->vendor_type }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-bold">Vendor Service (Optional)</label>
                                    <select wire:model="vendor_service_id" class="form-select form-select-sm">
                                        <option value="">-- Custom / Direct Vendor Sale --</option>
                                        @foreach($vendorServices as $vs)
                                            <option value="{{ $vs->id }}">{{ $vs->service_name }} (Rs. {{ number_format($vs->default_sale_price) }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-bold">Link Event Booking (Optional)</label>
                                    <select wire:model.live="booking_id" class="form-select form-select-sm">
                                        <option value="">-- Direct Sale (Unlinked) --</option>
                                        @foreach($bookings as $b)
                                            <option value="{{ $b->id }}">{{ $b->booking_number }} — {{ $b->customer->full_name ?? '' }} ({{ $b->booking_date->format('d-M-Y') }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label class="form-label fw-bold">Sale Date <span class="text-danger">*</span></label>
                                    <input type="date" wire:model="sale_date" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label class="form-label fw-bold">Event Date <span class="text-danger">*</span></label>
                                    <input type="date" wire:model="event_date" class="form-control form-control-sm">
                                </div>

                                <div class="col-md-4 mb-2">
                                    <label class="form-label fw-bold">Total Vendor Sale Amount (Rs.) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" wire:model="sale_amount" class="form-control form-control-sm @error('sale_amount') is-invalid @enderror" placeholder="80000">
                                    @error('sale_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label fw-bold">Custom Commission % (Optional Override)</label>
                                    <input type="number" step="0.01" wire:model="commission_rate" class="form-control form-control-sm" placeholder="Auto-resolved from agreement if blank">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label fw-bold">Quantity / Unit</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="0.01" wire:model="quantity" class="form-control">
                                        <input type="text" wire:model="unit" class="form-control" placeholder="Event">
                                    </div>
                                </div>

                                <div class="col-12 mb-2">
                                    <label class="form-label fw-bold">Notes / Special Instructions</label>
                                    <textarea wire:model="notes" class="form-control form-control-sm" rows="2" placeholder="Sale details, contract terms, or override reason..."></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-light py-2">
                            <button wire:click="$set('showSaleModal', false)" type="button" class="btn btn-secondary btn-sm px-3">Cancel</button>
                            <button type="submit" class="btn btn-success btn-sm px-4"><i class="fas fa-check-circle me-1"></i> Post Vendor Sale</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
