<div>
    <div class="row g-3">
        <!-- Vendor Registration & List -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0 text-primary"><i class="fas fa-handshake me-2"></i>Event Vendors Registry</h5>
                </div>
                <div class="card-body">
                    @if (session()->has('success'))
                        <div class="alert alert-success border-2 d-flex align-items-center" role="alert">
                            <div class="bg-success me-3 icon-item"><span class="fas fa-check-circle text-white fs-3"></span></div>
                            <p class="mb-0 flex-1">{{ session('success') }}</p>
                        </div>
                    @endif

                    <!-- Add Vendor Form -->
                    <form wire:submit.prevent="saveVendor" class="row g-2 mb-4 bg-light p-3 rounded">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Vendor Name</label>
                            <input type="text" wire:model="name" class="form-control form-control-sm" required />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Vendor Type</label>
                            <select wire:model="vendor_type" class="form-select form-select-sm">
                                <option value="Florist">Florist</option>
                                <option value="Decorator">Decorator</option>
                                <option value="DJ / Sound">DJ / Sound</option>
                                <option value="Photographer">Photographer</option>
                                <option value="Valet Services">Valet Services</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Contact Person</label>
                            <input type="text" wire:model="contact_person" class="form-control form-control-sm" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Phone Number</label>
                            <input type="text" wire:model="phone" class="form-control form-control-sm" required />
                        </div>
                        <div class="col-12 text-end mt-2">
                            <button type="submit" class="btn btn-primary btn-sm px-3"><i class="fas fa-plus me-1"></i>Add Vendor</button>
                        </div>
                    </form>

                    <!-- Vendors List -->
                    <div class="table-responsive">
                        <table class="table table-sm table-striped fs--1">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Contact</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($vendors as $vendor)
                                    <tr>
                                        <td><strong>{{ $vendor->name }}</strong></td>
                                        <td><span class="badge badge-subtle-primary">{{ $vendor->vendor_type }}</span></td>
                                        <td>{{ $vendor->contact_person ?: '-' }}</td>
                                        <td>{{ $vendor->phone }}</td>
                                        <td><span class="badge bg-success rounded">{{ $vendor->status }}</span></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">No event vendors registered.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vendor Commission Ledger -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0 text-primary"><i class="fas fa-file-invoice-dollar me-2"></i>Vendor Booking Commissions</h5>
                </div>
                <div class="card-body">
                    @if (session()->has('success_booking'))
                        <div class="alert alert-success border-2 d-flex align-items-center" role="alert">
                            <div class="bg-success me-3 icon-item"><span class="fas fa-check-circle text-white fs-3"></span></div>
                            <p class="mb-0 flex-1">{{ session('success_booking') }}</p>
                        </div>
                    @endif

                    <!-- Map Vendor to Booking -->
                    <form wire:submit.prevent="saveVendorBooking" class="row g-2 mb-4 bg-light p-3 rounded">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Select Vendor</label>
                            <select wire:model="selectedVendorId" class="form-select form-select-sm" required>
                                <option value="">-- Choose Vendor --</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}">{{ $vendor->name }} ({{ $vendor->vendor_type }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Select Booking</label>
                            <select wire:model="booking_id" class="form-select form-select-sm" required>
                                <option value="">-- Choose Booking --</option>
                                @foreach($bookings as $booking)
                                    <option value="{{ $booking->id }}">
                                        {{ $booking->booking_date->format('d M') }} - {{ $booking->customer ? ($booking->customer->first_name . ' ' . $booking->customer->last_name) : 'Walk-in' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Agreed Price (PKR)</label>
                            <input type="number" wire:model="agreed_price" class="form-control form-control-sm" required />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Commission Rate (%)</label>
                            <input type="number" step="0.1" wire:model="commission_rate" class="form-control form-control-sm" required />
                        </div>
                        <div class="col-12 text-end mt-2">
                            <button type="submit" class="btn btn-primary btn-sm px-3"><i class="fas fa-save me-1"></i>Map Commission</button>
                        </div>
                    </form>

                    <!-- Commissions List -->
                    <div class="table-responsive">
                        <table class="table table-sm table-striped fs--1">
                            <thead>
                                <tr>
                                    <th>Vendor</th>
                                    <th>Client / Event Date</th>
                                    <th>Agreed Price</th>
                                    <th>Comm. (Amt)</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($vendorBookings as $vb)
                                    <tr>
                                        <td><strong>{{ $vb->vendor->name }}</strong></td>
                                        <td>
                                            {{ $vb->booking->booking_date->format('d M, Y') }}<br/>
                                            <span class="text-muted small">{{ $vb->booking->customer ? ($vb->booking->customer->first_name . ' ' . $vb->booking->customer->last_name) : 'Walk-in' }}</span>
                                        </td>
                                        <td>{{ number_format($vb->agreed_price, 2) }}</td>
                                        <td>
                                            {{ number_format($vb->commission_amount, 2) }}<br/>
                                            <span class="text-muted small">({{ $vb->commission_rate }}%)</span>
                                        </td>
                                        <td><span class="badge badge-subtle-warning">{{ $vb->payment_status }}</span></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">No mapped vendor bookings found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
