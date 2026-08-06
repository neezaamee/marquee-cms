<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><span class="fas fa-users-cog me-2 text-primary"></span>Customer Referral Analytics</h5>
        </div>

        <div class="card-body bg-light border-top border-bottom py-3">
            <div class="row g-3 align-items-center">
                <div class="col-md-6">
                    <div class="position-relative">
                        <input wire:model.live.debounce.300ms="search" type="text" class="form-control form-control-sm ps-4" placeholder="Search by referrer name...">
                        <span class="position-absolute start-0 top-50 translate-middle-y ms-2 text-400 fas fa-search" style="font-size: 11px;"></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <!-- Analytics Summary Widgets -->
            <div class="row g-3 p-3">
                <div class="col-md-4">
                    <div class="border rounded-2 p-3 bg-white shadow-none">
                        <h6 class="text-600 mb-1 fs-11 text-uppercase fw-semi-bold">Total Referred Customers</h6>
                        <div class="fs-4 fw-normal text-warning font-sans-serif">{{ number_format($totalReferredCustomers) }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded-2 p-3 bg-white shadow-none">
                        <h6 class="text-600 mb-1 fs-11 text-uppercase fw-semi-bold">Total Bookings Placed</h6>
                        <div class="fs-4 fw-normal text-info font-sans-serif">{{ number_format($totalBookings) }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded-2 p-3 bg-white shadow-none">
                        <h6 class="text-600 mb-1 fs-11 text-uppercase fw-semi-bold">Total Revenue Generated</h6>
                        <div class="fs-4 fw-normal text-success font-sans-serif">Rs. {{ number_format($totalRevenue, 2) }}</div>
                    </div>
                </div>
            </div>

            <div class="table-responsive scrollbar">
                <table class="table table-sm table-striped fs-10 mb-0 align-middle table-hover">
                    <thead class="bg-200 text-900">
                        <tr>
                            <th class="px-3" style="width: 40%;">Referrer / Source Name</th>
                            <th class="text-center" style="width: 20%;">Referred Customers</th>
                            <th class="text-center" style="width: 20%;">Total Bookings</th>
                            <th class="text-end px-3" style="width: 20%;">Revenue Generated</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($referrals as $r)
                            <tr>
                                <td class="px-3 fw-bold text-primary">{{ $r->referrer_name }}</td>
                                <td class="text-center fw-semi-bold font-monospace">{{ number_format($r->referred_customers_count) }}</td>
                                <td class="text-center fw-semi-bold font-monospace text-info">{{ number_format($r->bookings_count) }}</td>
                                <td class="text-end px-3 font-monospace text-success fw-bold">
                                    Rs. {{ number_format($r->total_revenue, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">
                                    No customer referral records found matching the query.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
