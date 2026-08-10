<div>
    @if(session('success'))
        <div class="alert alert-success border-2 d-flex align-items-center mb-3" role="alert">
            <i class="fas fa-check-circle me-2 fs-6"></i>
            <div class="flex-grow-1">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($showAgreementModal)
        <!-- Falcon Card Form for Add / Edit Agreement -->
        <div class="card mb-3">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <span class="fas fa-file-contract me-2 text-primary"></span>
                    {{ $agreementId ? 'Edit Agreement Details' : 'New Commission Agreement' }}
                </h6>
                <button class="btn btn-falcon-default btn-sm" wire:click="$set('showAgreementModal', false)">
                    <span class="fas fa-chevron-left me-1" data-fa-transform="shrink-4"></span> Back
                </button>
            </div>

            <div class="card-body bg-light">
                <form wire:submit.prevent="saveAgreement">
                    <div class="row g-3">
                        @if(!$vendor)
                            <div class="col-md-6">
                                <label class="form-label fw-semi-bold" for="selectedVendorId">Select Service Provider <span class="text-danger">*</span></label>
                                <select id="selectedVendorId" wire:model.live="selectedVendorId" class="form-select @error('selectedVendorId') is-invalid @enderror">
                                    <option value="">-- Choose Service Provider --</option>
                                    @foreach($vendors as $v)
                                        <option value="{{ $v->id }}">{{ $v->name }} ({{ $v->vendor_type }})</option>
                                    @endforeach
                                </select>
                                @error('selectedVendorId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        @endif

                        <div class="col-md-6">
                            <label class="form-label fw-semi-bold" for="vendor_service_id">Service Scope (Optional)</label>
                            <select id="vendor_service_id" wire:model="vendor_service_id" class="form-select">
                                <option value="">-- All Provider Services (Default Rate) --</option>
                                @foreach($services as $s)
                                    <option value="{{ $s->id }}">{{ $s->service_name }} ({{ $s->service_code }})</option>
                                @endforeach
                            </select>
                            @error('vendor_service_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semi-bold" for="commission_type">Commission Model / Type <span class="text-danger">*</span></label>
                            <select id="commission_type" wire:model.live="commission_type" class="form-select">
                                <option value="percentage">1. Percentage (Sale × Commission %)</option>
                                <option value="fixed_per_event">2. Fixed Amount Per Event</option>
                                <option value="fixed_monthly">3. Fixed Monthly Fee</option>
                                <option value="hybrid">4. Hybrid (Percentage + Min/Max Caps)</option>
                            </select>
                            @error('commission_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        @if(in_array($commission_type, ['percentage', 'hybrid']))
                            <div class="col-md-6">
                                <label class="form-label fw-semi-bold" for="commission_percentage">Commission Percentage (%) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" id="commission_percentage" wire:model="commission_percentage" class="form-control @error('commission_percentage') is-invalid @enderror" placeholder="15.00">
                                @error('commission_percentage') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        @endif

                        @if(in_array($commission_type, ['fixed_per_event', 'hybrid']))
                            <div class="col-md-6">
                                <label class="form-label fw-semi-bold" for="fixed_commission_amount">Fixed Commission Per Event (Rs.)</label>
                                <input type="number" step="0.01" id="fixed_commission_amount" wire:model="fixed_commission_amount" class="form-control @error('fixed_commission_amount') is-invalid @enderror">
                                @error('fixed_commission_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        @endif

                        @if($commission_type === 'fixed_monthly')
                            <div class="col-md-6">
                                <label class="form-label fw-semi-bold" for="monthly_fixed_amount">Monthly Fixed Commission (Rs.)</label>
                                <input type="number" step="0.01" id="monthly_fixed_amount" wire:model="monthly_fixed_amount" class="form-control @error('monthly_fixed_amount') is-invalid @enderror">
                                @error('monthly_fixed_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        @endif

                        @if($commission_type === 'hybrid')
                            <div class="col-md-6">
                                <label class="form-label fw-semi-bold" for="minimum_commission">Minimum Commission Floor (Rs.)</label>
                                <input type="number" step="0.01" id="minimum_commission" wire:model="minimum_commission" class="form-control @error('minimum_commission') is-invalid @enderror">
                                @error('minimum_commission') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semi-bold" for="maximum_commission">Maximum Commission Ceiling (Rs.)</label>
                                <input type="number" step="0.01" id="maximum_commission" wire:model="maximum_commission" class="form-control @error('maximum_commission') is-invalid @enderror">
                                @error('maximum_commission') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        @endif

                        <div class="col-md-6">
                            <label class="form-label fw-semi-bold" for="effective_from">Effective From Date <span class="text-danger">*</span></label>
                            <input type="date" id="effective_from" wire:model="effective_from" class="form-control @error('effective_from') is-invalid @enderror">
                            @error('effective_from') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semi-bold" for="effective_to">Effective To Date (Optional)</label>
                            <input type="date" id="effective_to" wire:model="effective_to" class="form-control @error('effective_to') is-invalid @enderror">
                            @error('effective_to') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semi-bold" for="status">Agreement Status <span class="text-danger">*</span></label>
                            <select id="status" wire:model="status" class="form-select">
                                <option value="active">Active</option>
                                <option value="expired">Expired</option>
                                <option value="draft">Draft</option>
                                <option value="terminated">Terminated</option>
                            </select>
                            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semi-bold" for="settlement_terms">Settlement Terms</label>
                            <input type="text" id="settlement_terms" wire:model="settlement_terms" class="form-control @error('settlement_terms') is-invalid @enderror" placeholder="Net 30 days after event">
                            @error('settlement_terms') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semi-bold" for="notes">Special Notes / Contract Terms</label>
                            <textarea id="notes" wire:model="notes" class="form-control @error('notes') is-invalid @enderror" rows="3" placeholder="Internal remarks or contract provisions..."></textarea>
                            @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 mt-4 text-end">
                            <button type="button" wire:click="$set('showAgreementModal', false)" class="btn btn-falcon-default btn-sm me-2">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm px-4"><span class="fas fa-save me-1"></span> Save Agreement</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @else
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h6 class="fw-bold mb-0"><i class="fas fa-file-contract text-primary me-2"></i>Commission Agreements & Contracts</h6>
                <div class="text-secondary fs-11">Historical and active commission rate arrangements.</div>
            </div>
            <button wire:click="openCreateModal" class="btn btn-primary btn-xs"><i class="fas fa-plus me-1"></i> Create Agreement</button>
        </div>

        <!-- Agreements Table -->
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 fs-12">
                    <thead class="bg-200">
                        <tr>
                            <th>Agreement #</th>
                            @if(!$vendor) <th>Service Provider</th> @endif
                            <th>Service Scope</th>
                            <th>Commission Type</th>
                            <th>Terms & Rates</th>
                            <th>Effective Dates</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($agreements as $agr)
                            <tr>
                                <td class="fw-bold font-monospace text-primary">{{ $agr->agreement_number }}</td>
                                @if(!$vendor)
                                    <td class="fw-semibold">{{ $agr->vendor->name ?? '—' }}</td>
                                @endif
                                <td>
                                    @if($agr->service)
                                        <span class="badge badge-subtle-info"><i class="fas fa-tag me-1"></i>{{ $agr->service->service_name }}</span>
                                    @else
                                        <span class="badge badge-subtle-secondary"><i class="fas fa-globe me-1"></i>All Provider Services</span>
                                    @endif
                                </td>
                                <td><span class="badge badge-subtle-primary">{{ strtoupper(str_replace('_', ' ', $agr->commission_type)) }}</span></td>
                                <td class="fw-bold text-dark">
                                    @if($agr->commission_type === 'percentage')
                                        {{ $agr->commission_percentage }}%
                                    @elseif($agr->commission_type === 'fixed_per_event')
                                        Rs. {{ number_format($agr->fixed_commission_amount) }} / Event
                                    @elseif($agr->commission_type === 'fixed_monthly')
                                        Rs. {{ number_format($agr->monthly_fixed_amount) }} / Month
                                    @elseif($agr->commission_type === 'hybrid')
                                        {{ $agr->commission_percentage }}% + Rs. {{ number_format($agr->fixed_commission_amount) }}
                                    @endif
                                </td>
                                <td class="fs-11">
                                    {{ $agr->effective_from->format('d-M-Y') }} → {{ $agr->effective_to ? $agr->effective_to->format('d-M-Y') : 'Ongoing' }}
                                </td>
                                <td>
                                    @if($agr->status === 'active')
                                        <span class="badge badge-subtle-success">Active</span>
                                    @elseif($agr->status === 'expired')
                                        <span class="badge badge-subtle-warning">Expired</span>
                                    @else
                                        <span class="badge badge-subtle-secondary">{{ ucfirst($agr->status) }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <button wire:click="editAgreement({{ $agr->id }})" class="btn btn-falcon-default btn-xs"><i class="fas fa-edit text-primary"></i> Edit</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $vendor ? 7 : 8 }}" class="text-center py-4 text-muted">No commission agreements configured yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
