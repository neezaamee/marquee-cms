<div>
    @if(session('success'))
        <div class="alert alert-success border-2 d-flex align-items-center mb-3" role="alert">
            <i class="fas fa-check-circle me-2 fs-6"></i>
            <div class="flex-grow-1">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($showSaleModal)
        <!-- Falcon Card Form for Record Sale -->
        <div class="card mb-3">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <span class="fas fa-shopping-cart me-2 text-success"></span>
                    Record New Sale
                </h6>
                <button class="btn btn-falcon-default btn-sm" wire:click="$set('showSaleModal', false)">
                    <span class="fas fa-chevron-left me-1" data-fa-transform="shrink-4"></span> Back
                </button>
            </div>

            <div class="card-body bg-light">
                <form wire:submit.prevent="saveSale">
                    <div class="row g-3">
                        @if(!$vendor)
                            <div class="col-md-6">
                                <label class="form-label fw-semi-bold" for="vendor_id">Select Service Provider <span class="text-danger">*</span></label>
                                <select id="vendor_id" wire:model.live="vendor_id" class="form-select @error('vendor_id') is-invalid @enderror">
                                    <option value="">-- Choose Service Provider --</option>
                                    @foreach($vendors as $v)
                                        <option value="{{ $v->id }}">{{ $v->name }} ({{ $v->vendor_type }})</option>
                                    @endforeach
                                </select>
                                @error('vendor_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        @endif

                        <div class="col-md-6">
                            <label class="form-label fw-semi-bold" for="vendor_service_id">Service (Optional)</label>
                            <select id="vendor_service_id" wire:model="vendor_service_id" class="form-select">
                                <option value="">-- Custom / Direct Sale --</option>
                                @foreach($vendorServices as $vs)
                                    <option value="{{ $vs->id }}">{{ $vs->service_name }} (Rs. {{ number_format($vs->default_sale_price) }})</option>
                                @endforeach
                            </select>
                            @error('vendor_service_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semi-bold" for="booking_id">Link Event Booking (Optional)</label>
                            <select id="booking_id" wire:model.live="booking_id" class="form-select">
                                <option value="">-- Direct Sale (Unlinked) --</option>
                                @foreach($bookings as $b)
                                    <option value="{{ $b->id }}">{{ $b->booking_number }} — {{ $b->customer->full_name ?? '' }} ({{ $b->booking_date->format('d-M-Y') }})</option>
                                @endforeach
                            </select>
                            @error('booking_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semi-bold" for="sale_date">Sale Date <span class="text-danger">*</span></label>
                            <input type="date" id="sale_date" wire:model="sale_date" class="form-control @error('sale_date') is-invalid @enderror" required>
                            @error('sale_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semi-bold" for="event_date">Event Date <span class="text-danger">*</span></label>
                            <input type="date" id="event_date" wire:model="event_date" class="form-control @error('event_date') is-invalid @enderror" required>
                            @error('event_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semi-bold" for="sale_amount">Total Sale Amount (Rs.) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" id="sale_amount" wire:model="sale_amount" class="form-control @error('sale_amount') is-invalid @enderror" placeholder="80000" required>
                            @error('sale_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semi-bold" for="commission_rate">Custom Commission % (Optional Override)</label>
                            <input type="number" step="0.01" id="commission_rate" wire:model="commission_rate" class="form-control @error('commission_rate') is-invalid @enderror" placeholder="Auto-resolved from agreement if blank">
                            @error('commission_rate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semi-bold">Quantity / Unit</label>
                            <div class="input-group">
                                <input type="number" step="0.01" wire:model="quantity" class="form-control">
                                <input type="text" wire:model="unit" class="form-control" placeholder="Event">
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semi-bold" for="notes">Notes / Special Instructions</label>
                            <textarea id="notes" wire:model="notes" class="form-control @error('notes') is-invalid @enderror" rows="3" placeholder="Sale details, contract terms, or override reason..."></textarea>
                            @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 mt-4 text-end">
                            <button type="button" wire:click="$set('showSaleModal', false)" class="btn btn-falcon-default btn-sm me-2">Cancel</button>
                            <button type="submit" class="btn btn-success btn-sm px-4"><span class="fas fa-check-circle me-1"></span> Post Sale</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @else
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h6 class="fw-bold mb-0"><i class="fas fa-shopping-cart text-primary me-2"></i>Service Provider Sales & Commission Journal</h6>
                <div class="text-secondary fs-11">Sales transactions for contracted event service providers and commission revenue snapshots.</div>
            </div>
            <button wire:click="openCreateModal" class="btn btn-success btn-xs"><i class="fas fa-plus me-1"></i> Record Sale</button>
        </div>

        <!-- Search & Filters -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-2">
                <div class="row g-2">
                    <div class="col-md-6">
                        <input wire:model.live.debounce.300ms="search" type="text" class="form-control form-control-sm" placeholder="Search by sale #, provider name, booking #...">
                    </div>
                    @if(!$vendor)
                        <div class="col-md-4">
                            <select wire:model.live="filterVendorId" class="form-select form-select-sm">
                                <option value="">-- All Service Providers --</option>
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
                            @if(!$vendor) <th>Service Provider</th> @endif
                            <th>Booking / Customer</th>
                            <th>Event Date</th>
                            <th>Sale Amount</th>
                            <th>Commission Income</th>
                            <th>Net Provider Payable</th>
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
                                        <span class="text-muted">Direct Sale</span>
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
                                <td colspan="{{ $vendor ? 7 : 8 }}" class="text-center py-4 text-muted">No sales recorded.</td>
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
    @endif
</div>
