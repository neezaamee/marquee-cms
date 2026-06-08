<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><span class="fas fa-edit me-2 text-primary"></span>Edit Booking #{{ $booking->booking_number }}</h5>
            <a class="btn btn-falcon-default btn-sm" href="{{ route('bookings.show', $booking->id) }}">
                <span class="fas fa-chevron-left me-1" data-fa-transform="shrink-4"></span> Back
            </a>
        </div>
        <div class="card-body">
            @if($errors->has('submission'))
                <div class="alert alert-danger border-2 d-flex align-items-center mb-3" role="alert">
                    <div class="bg-danger me-3 icon-item"><span class="fas fa-times-circle text-white fs-8"></span></div>
                    <p class="mb-0 flex-grow-1 text-danger-800">{{ $errors->first('submission') }}</p>
                </div>
            @endif

            <form wire:submit.prevent="save">
                <div class="row g-3">
                    
                    <!-- Customer Details -->
                    <div class="col-md-4">
                        <label class="form-label font-sans-serif fw-bold text-700" for="selectedCustomerId">Customer Profile *</label>
                        <select wire:model="selectedCustomerId" class="form-select @error('selectedCustomerId') is-invalid @enderror" id="selectedCustomerId">
                            @foreach($customersList as $cust)
                                <option value="{{ $cust->id }}">{{ $cust->full_name }} ({{ $cust->customer_code }})</option>
                            @endforeach
                        </select>
                        @error('selectedCustomerId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Event Type -->
                    <div class="col-md-4">
                        <label class="form-label font-sans-serif fw-bold text-700" for="selectedEventTypeId">Event Type *</label>
                        <select wire:model="selectedEventTypeId" class="form-select @error('selectedEventTypeId') is-invalid @enderror" id="selectedEventTypeId">
                            @foreach($eventTypesList as $ev)
                                <option value="{{ $ev->id }}">{{ $ev->event_type_name }}</option>
                            @endforeach
                        </select>
                        @error('selectedEventTypeId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Booking Date -->
                    <div class="col-md-4">
                        <label class="form-label font-sans-serif fw-bold text-700" for="selectedDate">Event Date *</label>
                        <input wire:model.live="selectedDate" class="form-control font-monospace @error('selectedDate') is-invalid @enderror" type="date" id="selectedDate" />
                        @error('selectedDate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Venue / Hall -->
                    <div class="col-md-4">
                        <label class="form-label font-sans-serif fw-bold text-700" for="selectedHallId">Hall Location *</label>
                        <select wire:model.live="selectedHallId" class="form-select @error('selectedHallId') is-invalid @enderror" id="selectedHallId">
                            @foreach($hallsList as $hall)
                                <option value="{{ $hall->id }}">{{ $hall->hall_name }}</option>
                            @endforeach
                        </select>
                        @error('selectedHallId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Shift Slots -->
                    <div class="col-md-4">
                        <label class="form-label font-sans-serif fw-bold text-700">Checking Method</label>
                        <div class="d-flex gap-3 mt-1">
                            <div class="form-check">
                                <input wire:model.live="checkType" class="form-check-input" type="radio" name="checkType" id="edit_check_slot" value="slot">
                                <label class="form-check-label cursor-pointer" for="edit_check_slot">Predefined Shift</label>
                            </div>
                            <div class="form-check">
                                <input wire:model.live="checkType" class="form-check-input" type="radio" name="checkType" id="edit_check_custom" value="custom">
                                <label class="form-check-label cursor-pointer" for="edit_check_custom">Custom Time</label>
                            </div>
                        </div>
                    </div>

                    <!-- Slot Selector or Custom times -->
                    @if($checkType === 'slot')
                        <div class="col-md-4">
                            <label class="form-label font-sans-serif fw-bold text-700" for="selectedSlotId">Shift Slot *</label>
                            <select wire:model.live="selectedSlotId" class="form-select" id="selectedSlotId">
                                <option value="">Choose a slot...</option>
                                @foreach($availableSlotsList as $sl)
                                    <option value="{{ $sl['id'] }}" {{ !$sl['is_available'] ? 'disabled' : '' }}>
                                        {{ $sl['name'] }} ({{ $sl['start'] }} - {{ $sl['end'] }}) {{ !$sl['is_available'] ? '[BOOKED]' : '[FREE]' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <div class="col-md-2">
                            <label class="form-label font-sans-serif fw-bold text-700" for="customStart">Start Time *</label>
                            <input wire:model.live="customStart" class="form-control font-monospace" type="time" id="customStart" />
                        </div>
                        <div class="col-md-2">
                            <label class="form-label font-sans-serif fw-bold text-700" for="customEnd">End Time *</label>
                            <input wire:model.live="customEnd" class="form-control font-monospace" type="time" id="customEnd" />
                        </div>
                    @endif

                    @if($availabilityChecked && !$isAvailable)
                        <div class="col-12">
                            <div class="alert alert-danger border-2 d-flex align-items-center mb-0" role="alert">
                                <div class="bg-danger me-2 icon-item"><span class="fas fa-times-circle text-white fs-11"></span></div>
                                <span class="mb-0 text-danger-800 fs-11 fw-bold">Time slot conflict detected. Choose a free slot or adjust times.</span>
                            </div>
                        </div>
                    @endif

                    <div class="row navbar-vertical-label-wrapper mt-4 mb-2">
                        <div class="col-auto navbar-vertical-label text-primary">Invoicing & Financial Configuration</div>
                        <div class="col ps-0"><hr class="mb-0 navbar-vertical-divider" /></div>
                    </div>

                    <div class="col-lg-8">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label font-sans-serif fw-bold text-700" for="selectedPackageId">Select Package *</label>
                                <select wire:model.live="selectedPackageId" class="form-select @error('selectedPackageId') is-invalid @enderror" id="selectedPackageId">
                                    @foreach($packagesList as $pkg)
                                        <option value="{{ $pkg->id }}">{{ $pkg->package_name }}</option>
                                    @endforeach
                                </select>
                                @error('selectedPackageId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label font-sans-serif fw-bold text-700" for="guestCount">Guest Count *</label>
                                <input wire:model.live="guestCount" class="form-control" type="number" id="guestCount" min="1" />
                                @error('guestCount') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label font-sans-serif fw-bold text-700" for="perPlatePrice">Per Plate Rate *</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">Rs.</span>
                                    <input wire:model.live="perPlatePrice" class="form-control" type="number" id="perPlatePrice" step="0.01" />
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label font-sans-serif fw-bold text-700" for="hallCharges">Hall Rent Charges</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">Rs.</span>
                                    <input wire:model.live="hallCharges" class="form-control" type="number" id="hallCharges" step="0.01" />
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label font-sans-serif fw-bold text-700" for="extraCharges">Extra / Setup Charges</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">Rs.</span>
                                    <input wire:model.live="extraCharges" class="form-control" type="number" id="extraCharges" step="0.01" />
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label font-sans-serif fw-bold text-700" for="securityDeposit">Refundable Security Deposit</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">Rs.</span>
                                    <input wire:model.live="securityDeposit" class="form-control" type="number" id="securityDeposit" step="0.01" />
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label font-sans-serif fw-bold text-700" for="discountAmount">Discount Amount</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">Rs.</span>
                                    <input wire:model.live="discountAmount" class="form-control" type="number" id="discountAmount" step="0.01" />
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label font-sans-serif fw-bold text-700" for="taxRate">Tax Rate (%)</label>
                                <div class="input-group input-group-sm">
                                    <input wire:model.live="taxRate" class="form-control" type="number" id="taxRate" step="0.01" />
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Invoicing summary panel -->
                    <div class="col-lg-4">
                        <div class="card border border-primary bg-light">
                            <div class="card-header bg-primary py-2 text-white">
                                <h6 class="mb-0 text-white"><span class="fas fa-calculator me-1"></span>Invoice Math Preview</h6>
                            </div>
                            <div class="card-body fs-11">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Base Package Amount:</span>
                                    <span class="font-monospace">Rs. {{ number_format($packageAmount, 2) }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Hall Rent:</span>
                                    <span class="font-monospace">Rs. {{ number_format($hallCharges, 2) }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Extra Setup Fee:</span>
                                    <span class="font-monospace">Rs. {{ number_format($extraCharges, 2) }}</span>
                                </div>
                                <div class="d-flex justify-content-between text-danger mb-2 border-bottom pb-1">
                                    <span>Discount Allowed:</span>
                                    <span class="font-monospace">- Rs. {{ number_format($discountAmount, 2) }}</span>
                                </div>
                                <div class="d-flex justify-content-between fw-bold mb-1">
                                    <span>Subtotal:</span>
                                    <span class="font-monospace">Rs. {{ number_format($subtotal, 2) }}</span>
                                </div>
                                <div class="d-flex justify-content-between text-secondary mb-1 border-bottom pb-1">
                                    <span>Tax ({{ $taxRate }}%):</span>
                                    <span class="font-monospace">Rs. {{ number_format($taxAmount, 2) }}</span>
                                </div>
                                <div class="d-flex justify-content-between text-info mb-2 border-bottom pb-1">
                                    <span>Security Deposit:</span>
                                    <span class="font-monospace">Rs. {{ number_format($securityDeposit, 2) }}</span>
                                </div>
                                <div class="d-flex justify-content-between fw-black text-primary fs-9 mt-3">
                                    <span>Grand Total:</span>
                                    <span class="font-monospace">Rs. {{ number_format($grandTotal, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row navbar-vertical-label-wrapper mt-4 mb-2">
                        <div class="col-auto navbar-vertical-label text-primary">Metadata & Statuses</div>
                        <div class="col ps-0"><hr class="mb-0 navbar-vertical-divider" /></div>
                    </div>

                    <!-- Status Settings -->
                    <div class="col-md-4">
                        <label class="form-label font-sans-serif fw-bold text-700" for="bookingStatus">Booking Status *</label>
                        <select wire:model="bookingStatus" class="form-select" id="bookingStatus">
                            <option value="Draft">Draft</option>
                            <option value="Reserved">Reserved</option>
                            <option value="Confirmed">Confirmed</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                            <option value="Rejected">Rejected</option>
                        </select>
                        @error('bookingStatus') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label font-sans-serif fw-bold text-700" for="paymentStatus">Payment Status *</label>
                        <select wire:model="paymentStatus" class="form-select" id="paymentStatus">
                            <option value="Unpaid">Unpaid</option>
                            <option value="Partially Paid">Partially Paid</option>
                            <option value="Paid">Paid</option>
                            <option value="Refunded">Refunded</option>
                        </select>
                        @error('paymentStatus') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label font-sans-serif fw-bold text-700" for="specialInstructions">Special Instructions</label>
                        <textarea wire:model="specialInstructions" class="form-control" id="specialInstructions" rows="3" placeholder="Enter special setup, catering notes..."></textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex justify-content-end gap-2">
                    <a class="btn btn-falcon-default btn-sm" href="{{ route('bookings.show', $booking->id) }}">Cancel</a>
                    <button class="btn btn-primary btn-sm px-4" type="submit">
                        <span wire:loading class="spinner-border spinner-border-sm me-1" role="status"></span>
                        Update Booking <span class="fas fa-check ms-1"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
