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

                    <!-- Branch / Facility Location -->
                    <div class="col-12">
                        <label class="form-label font-sans-serif fw-bold text-700" for="editBranchId">Branch / Facility Location *</label>
                        @if($canChangeBranch)
                            <select wire:model.live="selectedBranchId" class="form-select @error('selectedBranchId') is-invalid @enderror" id="editBranchId">
                                <option value="">Choose Branch...</option>
                                @foreach($branchesList as $b)
                                    <option value="{{ $b->id }}">
                                        {{ $b->name }} @if($b->is_head_office) (Head Office) @endif — {{ $b->city }}
                                    </option>
                                @endforeach
                            </select>
                            @error('selectedBranchId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text fs-12 text-600 mt-1">
                                <span class="fas fa-info-circle me-1 text-primary"></span>Changing branch resets hall selection and updates availability for the new branch.
                            </div>
                        @else
                            @php $currBranch = \App\Models\Branch::find($selectedBranchId) ?? $booking->branch; @endphp
                            <div class="p-2 border rounded bg-light d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-l me-2 bg-primary-subtle rounded-circle p-2 d-flex align-items-center justify-content-center">
                                        <span class="fas fa-building text-primary"></span>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-800 fs-12">
                                            {{ $currBranch?->name ?? 'Main Branch' }}
                                            @if($currBranch?->is_head_office)
                                                <span class="badge badge-subtle-primary ms-1 fs-12">Head Office</span>
                                            @endif
                                        </div>
                                        <div class="fs-11 text-600">
                                            <span class="fas fa-map-marker-alt me-1"></span>{{ $currBranch?->address ?? '' }}, {{ $currBranch?->city ?? '' }}
                                            @if($currBranch?->phone) | <span class="fas fa-phone me-1"></span>{{ $currBranch?->phone }} @endif
                                        </div>
                                    </div>
                                </div>
                                <span class="badge badge-subtle-secondary fs-12"><span class="fas fa-lock me-1"></span>Branch Locked</span>
                            </div>
                        @endif
                    </div>

                    <!-- Event Type (Searchable) -->
                    <div class="col-md-4">
                        <label class="form-label font-sans-serif fw-bold text-700">Event Type *</label>
                        <div class="position-relative" x-data="{ open: false }" @click.outside="open = false">
                            <input wire:model.live.debounce.250ms="eventTypeSearch" class="form-control" type="text" placeholder="Search event types..." @focus="open = true" @click="open = true" />
                            <div x-show="open" class="position-absolute bg-white border rounded shadow w-100 z-3 mt-1 overflow-hidden" style="display: none;">
                                @forelse($filteredEventTypes as $ev)
                                    <button wire:click="selectEventType({{ $ev['id'] }}, '{{ addslashes($ev['event_type_name']) }}'); open = false;" class="btn btn-link w-100 text-start text-decoration-none text-900 py-2 px-3 hover-bg-light border-bottom border-translucent" type="button">
                                        <span class="fw-bold">{{ $ev['event_type_name'] }}</span>
                                    </button>
                                @empty
                                    <div class="text-center py-2 fs-11 text-muted">No matching event types.</div>
                                @endforelse
                            </div>
                        </div>
                        @if($selectedEventTypeId)
                            <div class="mt-2">
                                <span class="badge badge-subtle-success fs-11">
                                    Selected: {{ \App\Models\EventType::find($selectedEventTypeId)->event_type_name ?? '' }}
                                    <span wire:click="$set('selectedEventTypeId', ''); $set('eventTypeSearch', '')" class="fas fa-times ms-2 cursor-pointer text-danger"></span>
                                </span>
                            </div>
                        @endif
                        @error('selectedEventTypeId') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                    </div>

                    <!-- Booking Date -->
                    <div class="col-md-4">
                        <label class="form-label font-sans-serif fw-bold text-700" for="selectedDate">Event Date (DD-MM-YYYY) <span class="text-danger">*</span></label>
                        <div wire:ignore x-data x-init="
                            flatpickr($refs.datePicker, {
                                dateFormat: 'd-m-Y',
                                allowInput: true,
                                defaultDate: '{{ $selectedDate }}',
                                onChange: function(selectedDates, dateStr) {
                                    $wire.set('selectedDate', dateStr);
                                }
                            });
                        ">
                            <input x-ref="datePicker" wire:model.live="selectedDate" class="form-control font-monospace @error('selectedDate') is-invalid @enderror" type="text" id="selectedDate" placeholder="DD-MM-YYYY" />
                        </div>
                        @error('selectedDate') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <!-- Venue / Hall (Searchable Multi-select) -->
                    <div class="col-md-4">
                        <label class="form-label font-sans-serif fw-bold text-700">Select Hall(s) *</label>
                        <div class="position-relative" x-data="{ open: false }" @click.outside="open = false">
                            <input wire:model.live.debounce.250ms="hallSearch" class="form-control" type="text" placeholder="Search and select halls..." @focus="open = true" @click="open = true" />
                            <div x-show="open" class="position-absolute bg-white border rounded shadow w-100 z-3 mt-1 overflow-hidden" style="max-height: 200px; overflow-y: auto; display: none;">
                                @forelse($filteredHalls as $hall)
                                    <button wire:click="toggleHall({{ $hall['id'] }})" class="btn btn-link w-100 text-start text-decoration-none text-900 py-2 px-3 hover-bg-light border-bottom border-translucent" type="button">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="fw-bold">{{ $hall['hall_name'] }}</span>
                                                <span class="badge badge-subtle-secondary ms-1 fs-12">(Cap: {{ $hall['capacity'] }})</span>
                                            </div>
                                            @if(in_array((string)$hall['id'], $selectedHallIds))
                                                <span class="fas fa-check text-success"></span>
                                            @endif
                                        </div>
                                    </button>
                                @empty
                                    <div class="text-center py-2 fs-11 text-muted">No matching halls.</div>
                                @endforelse
                            </div>
                        </div>
                        <div class="mt-2 d-flex flex-wrap gap-2">
                            @forelse($selectedHallIds as $hId)
                                @php $hModel = \App\Models\Hall::find($hId); @endphp
                                @if($hModel)
                                    <span class="badge badge-subtle-primary fs-11 p-2">
                                        {{ $hModel->hall_name }} (Cap: {{ $hModel->capacity }})
                                        <span wire:click="toggleHall({{ $hId }})" class="fas fa-times ms-2 cursor-pointer text-danger" title="Remove Hall"></span>
                                    </span>
                                @endif
                            @empty
                                <span class="text-muted fs-11">No halls selected yet.</span>
                            @endforelse
                        </div>
                        @error('selectedHallIds') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
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
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="edit_noFood" wire:model.live="noFood" />
                                    <label class="form-check-label fw-bold cursor-pointer" for="edit_noFood">Sitting Plan Only (No Food Catering)</label>
                                </div>
                            </div>

                            @if(!$noFood)
                                <div class="col-md-6">
                                    <label class="form-label font-sans-serif fw-bold text-700" for="selectedPackageId">Select Package *</label>
                                    <select wire:model.live="selectedPackageId" class="form-select @error('selectedPackageId') is-invalid @enderror" id="selectedPackageId">
                                        <option value="">Choose Package...</option>
                                        @foreach($packagesList as $pkg)
                                            <option value="{{ $pkg->id }}">{{ $pkg->package_name }}</option>
                                        @endforeach
                                    </select>
                                    @error('selectedPackageId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            @endif

                            <div class="col-md-3">
                                <label class="form-label font-sans-serif fw-bold text-700" for="tentativeGuests">Tentative Guests *</label>
                                <input wire:model.live="tentativeGuests" class="form-control" type="number" id="tentativeGuests" min="1" placeholder="Initial estimate" />
                                @error('tentativeGuests') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label font-sans-serif fw-bold text-700" for="confirmedGuests">Confirmed Guests</label>
                                <input wire:model.live="confirmedGuests" class="form-control" type="number" id="confirmedGuests" min="0" placeholder="Confirmed count" />
                                @error('confirmedGuests') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6 d-flex align-items-center">
                                <div class="p-2 bg-light rounded border w-100 mt-3 mt-md-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fs-11 text-muted fw-bold">EFFECTIVE HEADCOUNT:</span>
                                        <span class="font-monospace fw-bold text-primary fs-9">{{ number_format($guestCount) }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-1">
                                        <span class="fs-11 text-muted">Status:</span>
                                        @if($guestStatus === 'Confirmed')
                                            <span class="badge bg-success-subtle text-success fs-12">Confirmed</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning fs-12">Tentative</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            @if(!$noFood)
                                <div class="col-md-6">
                                    <label class="form-label font-sans-serif fw-bold text-700" for="perPlatePrice">Per Plate Rate *</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">Rs.</span>
                                        <input wire:model.live.debounce.350ms="perPlatePrice" class="form-control" type="number" id="perPlatePrice" step="0.01" />
                                    </div>
                                </div>
                            @endif

                            <div class="col-md-6">
                                <label class="form-label font-sans-serif fw-bold text-700" for="hallCharges">Hall Rent Charges</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">Rs.</span>
                                    <input wire:model.live.debounce.350ms="hallCharges" class="form-control" type="number" id="hallCharges" step="0.01" />
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label font-sans-serif fw-bold text-700" for="extraCharges">Extra / Setup Charges (Sum of Add-ons)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">Rs.</span>
                                    <input wire:model.live="extraCharges" class="form-control" type="number" id="extraCharges" step="0.01" disabled />
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label font-sans-serif fw-bold text-700" for="securityDeposit">Refundable Security Deposit</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">Rs.</span>
                                    <input wire:model.live.debounce.350ms="securityDeposit" class="form-control" type="number" id="securityDeposit" step="0.01" />
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label font-sans-serif fw-bold text-700" for="discountAmount">Discount Amount</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">Rs.</span>
                                    <input wire:model.live.debounce.350ms="discountAmount" class="form-control" type="number" id="discountAmount" step="0.01" />
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label font-sans-serif fw-bold text-700" for="taxRate">Tax Rate (%)</label>
                                <div class="input-group input-group-sm">
                                    <input wire:model.live.debounce.350ms="taxRate" class="form-control" type="number" id="taxRate" step="0.01" />
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>

                            <!-- Privacy / Partition -->
                            <div class="col-12 mt-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="edit_privacyRequired" wire:model.live="privacyRequired" />
                                    <label class="form-check-label fw-bold cursor-pointer" for="edit_privacyRequired">Privacy / Partition Required?</label>
                                </div>
                            </div>

                            @if($privacyRequired)
                                <div class="col-md-6">
                                    <label class="form-label font-sans-serif fw-bold text-700" for="privacyLadiesPercentage">Ladies Percentage (%) *</label>
                                    <div class="input-group input-group-sm">
                                        <input wire:model.live="privacyLadiesPercentage" class="form-control @error('privacyLadiesPercentage') is-invalid @enderror" type="number" id="privacyLadiesPercentage" min="0" max="100" />
                                        <span class="input-group-text">%</span>
                                    </div>
                                    @error('privacyLadiesPercentage') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label font-sans-serif fw-bold text-700" for="privacyGentsPercentage">Gents Percentage (%) *</label>
                                    <div class="input-group input-group-sm">
                                        <input wire:model.live="privacyGentsPercentage" class="form-control @error('privacyGentsPercentage') is-invalid @enderror" type="number" id="privacyGentsPercentage" min="0" max="100" />
                                        <span class="input-group-text">%</span>
                                    </div>
                                    @error('privacyGentsPercentage') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                                </div>
                            @endif

                            <!-- Add-ons / Services Section -->
                            <div class="col-12 mt-4">
                                <h6 class="border-bottom pb-2 mb-2 text-primary fw-bold fs-9">
                                    <span class="fas fa-cubes me-2"></span>Select Extra Add-ons & Services
                                </h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped border fs-11 mb-0">
                                        <thead>
                                            <tr class="bg-light">
                                                <th class="text-center" style="width: 50px;">Select</th>
                                                <th>Service Name</th>
                                                <th style="width: 150px;">Price (PKR)</th>
                                                <th style="width: 100px;">Qty</th>
                                                <th style="width: 120px;" class="text-end">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($addonsList as $addon)
                                                <tr>
                                                    <td class="align-middle text-center">
                                                        <input class="form-check-input" type="checkbox" wire:model.live="selectedAddons.{{ $addon->id }}.selected" />
                                                    </td>
                                                    <td class="align-middle fw-semi-bold">{{ $addon->service_name }}</td>
                                                    <td class="align-middle">
                                                        <input class="form-control form-control-sm text-end" type="number" step="0.01" wire:model.live="selectedAddons.{{ $addon->id }}.price" {{ empty($selectedAddons[$addon->id]['selected']) ? 'disabled' : '' }} />
                                                    </td>
                                                    <td class="align-middle">
                                                        <input class="form-control form-control-sm text-center" type="number" min="1" wire:model.live="selectedAddons.{{ $addon->id }}.quantity" {{ empty($selectedAddons[$addon->id]['selected']) ? 'disabled' : '' }} />
                                                    </td>
                                                    <td class="align-middle text-end font-monospace fw-bold text-700">
                                                        Rs. {{ number_format(floatval($selectedAddons[$addon->id]['price'] ?? 0) * intval($selectedAddons[$addon->id]['quantity'] ?? 1), 2) }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Menu Customization Section -->
                            <div class="col-12 mt-4">
                                <h6 class="border-bottom pb-2 mb-2 text-primary fw-bold fs-9">
                                    <span class="fas fa-utensils me-2"></span>Customize Event Menu Items
                                </h6>
                                
                                <div class="card bg-light border p-3">
                                    <div class="row g-2 align-items-center mb-3">
                                        <div class="col-12">
                                            <div class="position-relative" x-data="{ open: false }" @click.outside="open = false">
                                                <input wire:model.live.debounce.250ms="menuItemSearch" class="form-control form-control-sm" type="text" placeholder="Search and add a dish..." @focus="open = true" @click="open = true" />
                                                <div x-show="open" class="position-absolute bg-white border rounded shadow w-100 z-3 mt-1 overflow-hidden" style="max-height: 200px; overflow-y: auto; display: none;">
                                                    @forelse($menuItemsAutocomplete as $mi)
                                                        <button wire:click="selectMenuItem({{ $mi->id }}); open = false;" class="btn btn-link w-100 text-start text-decoration-none text-900 py-1.5 px-3 hover-bg-light border-bottom border-translucent" type="button">
                                                            <span class="fw-bold">{{ $mi->item_name }}</span>
                                                            @if($mi->urdu_name)
                                                                <span class="text-muted fs-11 ms-1">({{ $mi->urdu_name }})</span>
                                                            @endif
                                                            <span class="badge badge-subtle-secondary ms-1 fs-12">{{ $mi->category->category_name ?? 'N/A' }}</span>
                                                        </button>
                                                    @empty
                                                        <div class="text-center py-2 fs-11 text-muted">No matching dishes.</div>
                                                    @endforelse
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    @if(count($bookingMenuItems) > 0)
                                        <div class="table-responsive">
                                            <table class="table table-sm bg-white border fs-11 mb-0">
                                                <thead>
                                                    <tr class="bg-200">
                                                        <th>Dish Name</th>
                                                        <th>Custom Instructions (e.g. extra spicy, double serving)</th>
                                                        <th class="text-center" style="width: 150px;">Managed by Host</th>
                                                        <th class="text-center" style="width: 120px;">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($bookingMenuItems as $idx => $item)
                                                        <tr>
                                                            <td class="align-middle fw-bold text-700">
                                                                {{ $item['item_name'] }}
                                                                @if(!empty($item['urdu_name']))
                                                                    <span class="text-muted fs-11 d-block">{{ $item['urdu_name'] }}</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                <input type="text" class="form-control form-control-sm" placeholder="No instructions" wire:model="bookingMenuItems.{{ $idx }}.custom_note" />
                                                            </td>
                                                            <td class="text-center align-middle">
                                                                <input class="form-check-input" type="checkbox" wire:model.live="bookingMenuItems.{{ $idx }}.managed_by_host" id="edit_managed_by_host_{{ $idx }}" {{ $noFood ? 'disabled checked' : '' }} />
                                                            </td>
                                                            <td class="align-middle text-center">
                                                                <div class="d-flex justify-content-center gap-2">
                                                                    <button type="button" class="btn btn-sm btn-link text-secondary p-0" wire:click="moveMenuItemUp({{ $idx }})" {{ $idx === 0 ? 'disabled' : '' }} title="Move Up">
                                                                        <span class="fas fa-arrow-up fs-11"></span>
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-link text-secondary p-0" wire:click="moveMenuItemDown({{ $idx }})" {{ $idx === count($bookingMenuItems) - 1 ? 'disabled' : '' }} title="Move Down">
                                                                        <span class="fas fa-arrow-down fs-11"></span>
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-link text-danger p-0" wire:click="removeMenuItem({{ $idx }})" title="Remove">
                                                                        <span class="fas fa-trash-alt fs-11"></span>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="text-center text-muted py-3">
                                            <span class="fas fa-info-circle me-1"></span>No dishes currently selected. Add custom dishes or choose a package.
                                        </div>
                                    @endif
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
                                @if(!$noFood)
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Base Package Amount:</span>
                                        <span class="font-monospace">Rs. {{ number_format($packageAmount, 2) }}</span>
                                    </div>
                                @else
                                    <div class="d-flex justify-content-between mb-1 text-secondary fw-bold">
                                        <span>Catering Plan:</span>
                                        <span>Sitting Plan Only (No Food)</span>
                                    </div>
                                @endif
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
