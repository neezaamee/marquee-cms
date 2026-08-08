<div>
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h6 class="fw-bold mb-0"><i class="fas fa-file-contract text-primary me-2"></i>Commission Agreements & Contracts</h6>
            <div class="text-secondary fs-11">Historical and active commission rate arrangements.</div>
        </div>
        <button wire:click="openCreateModal" class="btn btn-primary btn-xs"><i class="fas fa-plus me-1"></i> Create Agreement</button>
    </div>

    @if(session('success'))
        <div class="alert alert-success py-2 fs-12 mb-3">{{ session('success') }}</div>
    @endif

    <!-- Agreements Table -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 fs-12">
                <thead class="bg-200">
                    <tr>
                        <th>Agreement #</th>
                        @if(!$vendor) <th>Vendor</th> @endif
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
                                    <span class="badge badge-subtle-secondary"><i class="fas fa-globe me-1"></i>All Vendor Services</span>
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

    <!-- Agreement Modal -->
    @if($showAgreementModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" role="dialog">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-primary text-white py-2">
                        <h6 class="modal-title fw-bold fs-13"><i class="fas fa-file-contract me-2"></i>{{ $agreementId ? 'Edit Agreement' : 'New Commission Agreement' }}</h6>
                        <button wire:click="$set('showAgreementModal', false)" type="button" class="btn-close btn-close-white" aria-label="Close"></button>
                    </div>
                    <form wire:submit.prevent="saveAgreement">
                        <div class="modal-body p-3 fs-12">
                            <div class="row g-2">
                                @if(!$vendor)
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label fw-bold">Select Vendor <span class="text-danger">*</span></label>
                                        <select wire:model.live="selectedVendorId" class="form-select form-select-sm">
                                            <option value="">-- Choose Vendor --</option>
                                            @foreach($vendors as $v)
                                                <option value="{{ $v->id }}">{{ $v->name }} ({{ $v->vendor_type }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-bold">Service Scope (Optional)</label>
                                    <select wire:model="vendor_service_id" class="form-select form-select-sm">
                                        <option value="">-- All Vendor Services (Default Vendor Rate) --</option>
                                        @foreach($services as $s)
                                            <option value="{{ $s->id }}">{{ $s->service_name }} ({{ $s->service_code }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-bold">Commission Model / Type <span class="text-danger">*</span></label>
                                    <select wire:model.live="commission_type" class="form-select form-select-sm">
                                        <option value="percentage">1. Percentage (Sale × Commission %)</option>
                                        <option value="fixed_per_event">2. Fixed Amount Per Event</option>
                                        <option value="fixed_monthly">3. Fixed Monthly Fee</option>
                                        <option value="hybrid">4. Hybrid (Percentage + Min/Max Caps)</option>
                                    </select>
                                </div>

                                @if(in_array($commission_type, ['percentage', 'hybrid']))
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label fw-bold">Commission Percentage (%) <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" wire:model="commission_percentage" class="form-control form-control-sm" placeholder="15.00">
                                    </div>
                                @endif

                                @if(in_array($commission_type, ['fixed_per_event', 'hybrid']))
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label fw-bold">Fixed Commission Per Event (Rs.)</label>
                                        <input type="number" step="0.01" wire:model="fixed_commission_amount" class="form-control form-control-sm">
                                    </div>
                                @endif

                                @if($commission_type === 'fixed_monthly')
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label fw-bold">Monthly Fixed Commission (Rs.)</label>
                                        <input type="number" step="0.01" wire:model="monthly_fixed_amount" class="form-control form-control-sm">
                                    </div>
                                @endif

                                @if($commission_type === 'hybrid')
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label fw-bold">Minimum Commission Floor (Rs.)</label>
                                        <input type="number" step="0.01" wire:model="minimum_commission" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label fw-bold">Maximum Commission Ceiling (Rs.)</label>
                                        <input type="number" step="0.01" wire:model="maximum_commission" class="form-control form-control-sm">
                                    </div>
                                @endif

                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-bold">Effective From Date <span class="text-danger">*</span></label>
                                    <input type="date" wire:model="effective_from" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-bold">Effective To Date (Optional)</label>
                                    <input type="date" wire:model="effective_to" class="form-control form-control-sm">
                                </div>

                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-bold">Agreement Status <span class="text-danger">*</span></label>
                                    <select wire:model="status" class="form-select form-select-sm">
                                        <option value="active">Active</option>
                                        <option value="expired">Expired</option>
                                        <option value="draft">Draft</option>
                                        <option value="terminated">Terminated</option>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-bold">Settlement Terms</label>
                                    <input type="text" wire:model="settlement_terms" class="form-control form-control-sm" placeholder="Net 30 days after event">
                                </div>

                                <div class="col-12 mb-2">
                                    <label class="form-label fw-bold">Special Notes / Contract Terms</label>
                                    <textarea wire:model="notes" class="form-control form-control-sm" rows="2" placeholder="Internal remarks or contract provisions..."></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-light py-2">
                            <button wire:click="$set('showAgreementModal', false)" type="button" class="btn btn-secondary btn-sm px-3">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm px-4"><i class="fas fa-save me-1"></i> Save Agreement</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
