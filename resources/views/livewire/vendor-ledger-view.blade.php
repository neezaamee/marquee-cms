<div>
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h6 class="fw-bold mb-0"><i class="fas fa-book text-primary me-2"></i>Service Provider Financial Ledger</h6>
            <div class="text-secondary fs-11">Running financial transaction history, credits, commission debits, and payouts.</div>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-2">
            <div class="row g-2">
                @if(!$vendor)
                    <div class="col-md-5">
                        <select wire:model.live="filterVendorId" class="form-select form-select-sm">
                            <option value="">-- All Service Providers --</option>
                            @foreach($vendors as $v)
                                <option value="{{ $v->id }}">{{ $v->name }} ({{ $v->vendor_code }})</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-3">
                    <input type="date" wire:model.live="dateFrom" class="form-control form-control-sm" placeholder="From Date">
                </div>
                <div class="col-md-3">
                    <input type="date" wire:model.live="dateTo" class="form-control form-control-sm" placeholder="To Date">
                </div>
                <div class="col-md-1">
                    <button wire:click="$set('filterVendorId', ''); $set('dateFrom', ''); $set('dateTo', '');" class="btn btn-outline-secondary btn-sm w-100"><i class="fas fa-undo"></i></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Ledger Table -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 fs-12">
                <thead class="bg-200">
                    <tr>
                        <th>Date</th>
                        <th>Reference #</th>
                        @if(!$vendor) <th>Service Provider</th> @endif
                        <th>Booking #</th>
                        <th>Transaction Description</th>
                        <th>Sale Amount</th>
                        <th>Commission</th>
                        <th>Payout Amount</th>
                        <th>Running Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ledgers as $entry)
                        <tr>
                            <td>{{ $entry->transaction_date ? $entry->transaction_date->format('d-M-Y') : '—' }}</td>
                            <td class="fw-bold font-monospace text-primary">{{ $entry->reference_number }}</td>
                            @if(!$vendor)
                                <td class="fw-semibold">
                                    @if($entry->vendor)
                                        <a href="{{ route('vendors.show', $entry->vendor_id) }}" class="text-dark">{{ $entry->vendor->name }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                            @endif
                            <td>
                                @if($entry->booking)
                                    <a href="{{ route('bookings.show', $entry->booking_id) }}" class="badge badge-subtle-info text-decoration-none"><i class="fas fa-calendar-check me-1"></i>{{ $entry->booking->booking_number }}</a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $entry->description }}</td>
                            <td class="fw-bold text-dark">
                                {{ $entry->sale_amount > 0 ? 'Rs. ' . number_format($entry->sale_amount) : '—' }}
                            </td>
                            <td class="fw-bold text-success">
                                {{ $entry->commission_amount > 0 ? 'Rs. ' . number_format($entry->commission_amount) : '—' }}
                            </td>
                            <td class="fw-bold text-primary">
                                {{ $entry->payment_amount > 0 ? 'Rs. ' . number_format($entry->payment_amount) : '—' }}
                            </td>
                            <td class="fw-bold {{ $entry->running_balance > 0 ? 'text-danger' : 'text-success' }}">
                                Rs. {{ number_format($entry->running_balance) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $vendor ? 8 : 9 }}" class="text-center py-4 text-muted">No financial ledger records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
