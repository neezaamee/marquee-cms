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

                        <!-- Advance Payment Section -->
                        <div class="col-12">
                            <div class="card bg-light border p-3">
                                <div class="fw-bold text-uppercase fs-11 text-700 mb-2"><i class="fas fa-money-bill-wave text-success me-1"></i>Initial Vendor Advance Payout (Optional)</div>
                                <div class="row g-2">
                                    <div class="col-md-3">
                                        <label class="form-label fw-semi-bold fs-11">Advance Paid (PKR)</label>
                                        <input type="number" step="0.01" wire:model="advance_amount" class="form-control @error('advance_amount') is-invalid @enderror" placeholder="0.00">
                                        @error('advance_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semi-bold fs-11">Payment Method</label>
                                        <select wire:model="payment_method" class="form-select">
                                            <option value="Cash">Cash</option>
                                            <option value="Bank Transfer">Bank Transfer</option>
                                            <option value="Cheque">Cheque</option>
                                            <option value="Online">Online</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semi-bold fs-11">Disbursed From Account</label>
                                        <select wire:model="account_id" class="form-select">
                                            <option value="">-- Default Account --</option>
                                            @foreach($accounts as $acc)
                                                <option value="{{ $acc->id }}">{{ $acc->name }} ({{ $acc->account_code }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semi-bold fs-11">Reference #</label>
                                        <input type="text" wire:model="reference_number" class="form-control" placeholder="e.g. ADV-01">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch p-2 ps-5 border rounded bg-light">
                                <input class="form-check-input" type="checkbox" id="include_in_invoice" wire:model="include_in_invoice">
                                <label class="form-check-label fw-bold text-dark fs-12 mb-0" for="include_in_invoice">
                                    <span class="fas fa-file-invoice text-primary me-1"></span> Include in Customer Event Invoice (Customer pays Marquee)
                                </label>
                                <div class="text-muted fs-11 mt-1">
                                    Uncheck if customer will pay the service provider directly without Marquee invoice billing.
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semi-bold" for="notes">Notes / Special Instructions</label>
                            <textarea id="notes" wire:model="notes" class="form-control @error('notes') is-invalid @enderror" rows="2" placeholder="Sale details, contract terms, or override reason..."></textarea>
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
                            <th>Customer Charge</th>
                            <th>Vendor Cost</th>
                            <th>Advance Paid</th>
                            <th>Total Paid</th>
                            <th>Remaining Balance</th>
                            <th class="text-center">Payment Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sales as $sale)
                            @php
                                $statusBadge = match($sale->payment_status) {
                                    'fully_paid' => 'success',
                                    'partially_paid' => 'warning',
                                    default => 'danger'
                                };
                            @endphp
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
                                <td class="fw-bold text-dark font-monospace">Rs. {{ number_format($sale->sale_amount, 2) }}</td>
                                <td class="fw-bold text-primary font-monospace">Rs. {{ number_format($sale->vendor_net_amount, 2) }}</td>
                                <td class="text-muted font-monospace">Rs. {{ number_format($sale->advance_amount, 2) }}</td>
                                <td class="text-success font-monospace fw-bold">Rs. {{ number_format($sale->paid_amount, 2) }}</td>
                                <td class="fw-bold font-monospace {{ $sale->remaining_amount > 0 ? 'text-danger' : 'text-success' }}">
                                    Rs. {{ number_format($sale->remaining_amount, 2) }}
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-subtle-{{ $statusBadge }} rounded-pill text-uppercase">
                                        {{ str_replace('_', ' ', $sale->payment_status ?: 'unpaid') }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $vendor ? 9 : 10 }}" class="text-center py-4 text-muted">No sales recorded.</td>
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
