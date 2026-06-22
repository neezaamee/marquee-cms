<div>
    <!-- Dashboard Header & Filters -->
    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
            <div>
                <h5 class="mb-0 text-primary fw-bold"><span class="fas fa-coins me-2"></span>Financial Revenue Dashboard</h5>
                <p class="fs-12 text-600 mb-0">Track bookings direct earnings, payments, and security deposit ledgers.</p>
            </div>
            <div>
                <select wire:model.live="filterRange" class="form-select form-select-sm fs-12" style="width: 180px;">
                    <option value="30">Last 30 Days</option>
                    <option value="90">Last 90 Days</option>
                    <option value="365">Last Year</option>
                    <option value="all">All-Time Statistics</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Main KPIs Row -->
    <div class="row g-3 mb-3">
        <!-- Direct Event Revenue -->
        <div class="col-md-4">
            <div class="card overflow-hidden h-100">
                <div class="bg-holder bg-card" style="background-image:url({{ asset('assets/img/icons/spot-illustrations/corner-1.png') }});"></div>
                <div class="card-body position-relative">
                    <h6 class="text-uppercase text-600 fw-bold fs-11 text-success">Direct Event Revenue</h6>
                    <div class="display-4 fs-4 mb-1 fw-black text-success font-monospace">Rs. {{ number_format($totalDirectRevenue, 2) }}</div>
                    <p class="fs-12 text-500 mb-0">Event Rental + Catering + Custom Addons (Excludes Deposits)</p>
                </div>
            </div>
        </div>

        <!-- Payments Collected -->
        <div class="col-md-4">
            <div class="card overflow-hidden h-100">
                <div class="bg-holder bg-card" style="background-image:url({{ asset('assets/img/icons/spot-illustrations/corner-2.png') }});"></div>
                <div class="card-body position-relative">
                    <h6 class="text-uppercase text-600 fw-bold fs-11 text-primary">Total Payments Collected</h6>
                    <div class="display-4 fs-4 mb-1 fw-black text-primary font-monospace">Rs. {{ number_format($totalPaymentsCollected, 2) }}</div>
                    <p class="fs-12 text-500 mb-0">Sum of all customer installments & advances received</p>
                </div>
            </div>
        </div>

        <!-- Outstanding Balance -->
        <div class="col-md-4">
            <div class="card overflow-hidden h-100">
                <div class="bg-holder bg-card" style="background-image:url({{ asset('assets/img/icons/spot-illustrations/corner-3.png') }});"></div>
                <div class="card-body position-relative">
                    <h6 class="text-uppercase text-600 fw-bold fs-11 text-warning">Outstanding Balance Due</h6>
                    <div class="display-4 fs-4 mb-1 fw-black text-warning font-monospace">Rs. {{ number_format($totalOutstanding, 2) }}</div>
                    <p class="fs-12 text-500 mb-0">Total remaining balances to be collected from clients</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Security Deposits Ledger Section (Separated!) -->
    <div class="card mb-3 border border-info">
        <div class="card-header bg-info-subtle py-2">
            <h6 class="mb-0 text-info-900 fw-bold"><span class="fas fa-hand-holding-usd me-2"></span>Refundable Security Deposits Ledger (Separated Liability)</h6>
        </div>
        <div class="card-body py-3">
            <div class="row g-3">
                <!-- Held Deposits -->
                <div class="col-sm-4 border-end border-translucent">
                    <div class="text-center">
                        <span class="fs-11 fw-semi-bold text-uppercase text-600 d-block">Active Deposits Held</span>
                        <span class="fs-6 fw-black text-info font-monospace d-block my-1">Rs. {{ number_format($securityDepositsHeld, 2) }}</span>
                        <span class="badge badge-subtle-info rounded-pill">Active Liability</span>
                    </div>
                </div>

                <!-- Refunded Deposits -->
                <div class="col-sm-4 border-end border-translucent">
                    <div class="text-center">
                        <span class="fs-11 fw-semi-bold text-uppercase text-600 d-block">Deposits Released / Refunded</span>
                        <span class="fs-6 fw-black text-success font-monospace d-block my-1">Rs. {{ number_format($securityDepositsRefunded, 2) }}</span>
                        <span class="badge badge-subtle-success rounded-pill">Returned to Hosts</span>
                    </div>
                </div>

                <!-- Deducted Deposits -->
                <div class="col-sm-4">
                    <div class="text-center">
                        <span class="fs-11 fw-semi-bold text-uppercase text-600 d-block">Deposits Deducted (Damages/Loss)</span>
                        <span class="fs-6 fw-black text-danger font-monospace d-block my-1">Rs. {{ number_format($securityDepositsDeducted, 2) }}</span>
                        <span class="badge badge-subtle-danger rounded-pill">Auxiliary Income Posted</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Details Grid -->
    <div class="row g-3">
        <!-- Recent Payments Table -->
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><span class="fas fa-history me-2 text-primary"></span>Recent Payments Activity</h6>
                    <a href="{{ route('finance.payments') }}" class="btn btn-link btn-sm p-0 fs-12">View Ledger</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped border-0 fs-11 mb-0 align-middle">
                            <thead class="bg-light text-800">
                                <tr>
                                    <th class="ps-3">Date</th>
                                    <th>Booking Ref</th>
                                    <th>Customer / Host</th>
                                    <th>Method</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-center" style="width: 80px;">Receipt</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentPayments as $payment)
                                    <tr>
                                        <td class="ps-3 fw-bold">{{ $payment->payment_date->format('M d, Y') }}</td>
                                        <td>
                                            <a href="{{ route('bookings.show', $payment->booking_id) }}" class="fw-bold font-monospace">
                                                #{{ $payment->booking->booking_number }}
                                            </a>
                                        </td>
                                        <td>{{ $payment->booking->customer->full_name ?? '—' }}</td>
                                        <td><span class="badge badge-subtle-primary">{{ $payment->payment_method }}</span></td>
                                        <td class="text-end font-monospace fw-bold text-success">Rs. {{ number_format($payment->amount, 2) }}</td>
                                        <td class="text-center">
                                            <a href="{{ route('bookings.payment-receipt', $payment->id) }}" target="_blank" class="btn btn-falcon-default btn-xs" title="Print Receipt">
                                                <span class="fas fa-print text-secondary"></span>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4 fs-12">No payments collected in this range.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Method Breakdown -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h6 class="mb-0 fw-bold"><span class="fas fa-chart-pie me-2 text-primary"></span>Payment Methods Breakdown</h6>
                </div>
                <div class="card-body">
                    @if($paymentMethodsBreakdown->isNotEmpty())
                        <div class="d-flex flex-column gap-3">
                            @foreach($paymentMethodsBreakdown as $method)
                                @php
                                    $pct = $totalPaymentsCollected > 0 ? ($method->total / $totalPaymentsCollected) * 100 : 0;
                                @endphp
                                <div>
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fs-12 fw-bold text-800">{{ $method->payment_method }}</span>
                                        <span class="fs-11 font-monospace text-700">Rs. {{ number_format($method->total, 2) }} ({{ round($pct) }}%)</span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $pct }}%" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5 text-muted fs-12">
                            <span class="fas fa-info-circle me-1"></span>No data available for payment methods.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
