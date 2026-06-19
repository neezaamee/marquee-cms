<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ $isEditMode ? 'Edit Plan: ' . $name : 'Create New Subscription Plan' }}</h5>
            <a href="{{ route('subscription-plans.index') }}" class="btn btn-falcon-default btn-sm">
                <span class="fas fa-arrow-left me-1"></span> Cancel
            </a>
        </div>
        <div class="card-body">
            <form wire:submit.prevent="save">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="name">Plan Name <span class="text-danger">*</span></label>
                        <input wire:model.blur="name" class="form-control @error('name') is-invalid @enderror" id="name" type="text" placeholder="e.g. Premium Tier" />
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="slug">Plan Slug <span class="text-danger">*</span></label>
                        <input wire:model="slug" class="form-control @error('slug') is-invalid @enderror" id="slug" type="text" placeholder="e.g. premium-tier" />
                        @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="description">Description</label>
                        <textarea wire:model="description" class="form-control @error('description') is-invalid @enderror" id="description" rows="3" placeholder="Describe the plan features..."></textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <h5 class="mt-4 mb-2 text-primary">Pricing & Intervals</h5>

                    <div class="col-md-3">
                        <label class="form-label" for="monthly_price">Monthly Price <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input wire:model="monthly_price" class="form-control @error('monthly_price') is-invalid @enderror" id="monthly_price" type="number" step="0.01" min="0" />
                            <span class="input-group-text">{{ $currency }}</span>
                        </div>
                        @error('monthly_price') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="quarterly_price">Quarterly Price <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input wire:model="quarterly_price" class="form-control @error('quarterly_price') is-invalid @enderror" id="quarterly_price" type="number" step="0.01" min="0" />
                            <span class="input-group-text">{{ $currency }}</span>
                        </div>
                        @error('quarterly_price') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="semi_annual_price">Semi-Annual Price <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input wire:model="semi_annual_price" class="form-control @error('semi_annual_price') is-invalid @enderror" id="semi_annual_price" type="number" step="0.01" min="0" />
                            <span class="input-group-text">{{ $currency }}</span>
                        </div>
                        @error('semi_annual_price') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="annual_price">Annual Price <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input wire:model="annual_price" class="form-control @error('annual_price') is-invalid @enderror" id="annual_price" type="number" step="0.01" min="0" />
                            <span class="input-group-text">{{ $currency }}</span>
                        </div>
                        @error('annual_price') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="currency">Currency <span class="text-danger">*</span></label>
                        <select wire:model="currency" class="form-select @error('currency') is-invalid @enderror" id="currency">
                            <option value="PKR">PKR (Pakistani Rupee)</option>
                            <option value="USD">USD (US Dollar)</option>
                            <option value="AED">AED (UAE Dirham)</option>
                        </select>
                        @error('currency') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="trial_days">Trial Days <span class="text-danger">*</span></label>
                        <input wire:model="trial_days" class="form-control @error('trial_days') is-invalid @enderror" id="trial_days" type="number" min="0" />
                        @error('trial_days') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="max_storage">Max Storage (MB) <span class="text-danger">*</span></label>
                        <input wire:model="max_storage" class="form-control @error('max_storage') is-invalid @enderror" id="max_storage" type="number" min="0" />
                        @error('max_storage') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <h5 class="mt-4 mb-2 text-primary">Status & Mappings</h5>

                    <div class="col-md-4">
                        <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
                        <select wire:model="status" class="form-select @error('status') is-invalid @enderror" id="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="sort_order">Sort Order <span class="text-danger">*</span></label>
                        <input wire:model="sort_order" class="form-control @error('sort_order') is-invalid @enderror" id="sort_order" type="number" />
                        @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4 d-flex align-items-center mt-5">
                        <div class="form-check form-switch">
                            <input wire:model="is_popular" class="form-check-input" id="is_popular" type="checkbox" />
                            <label class="form-check-label fw-semi-bold mb-0" for="is_popular">Mark as Popular Plan</label>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label d-block fw-semi-bold">Applicable Billing Cycles</label>
                        <div class="row">
                            @forelse($availableBillingCycles as $cycle)
                                <div class="col-md-4 col-sm-6 mb-2">
                                    <div class="form-check">
                                        <input wire:model="selectedBillingCycles" class="form-check-input" type="checkbox" value="{{ $cycle->id }}" id="cycle_{{ $cycle->id }}">
                                        <label class="form-check-label" for="cycle_{{ $cycle->id }}">
                                            {{ $cycle->cycle_name }} ({{ $cycle->duration_in_months }} months @if($cycle->discount_percentage > 0) -{{ $cycle->discount_percentage }}% off @endif)
                                        </label>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12">
                                    <p class="text-muted fs-10">No active billing cycles defined. Define them in <a href="{{ route('billing-cycles.index') }}" target="_blank">Billing Cycles</a> first.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="col-12 mt-4">
                        <button class="btn btn-primary btn-sm px-4 me-2" type="submit">
                            <span class="fas fa-save me-1"></span> Save Plan
                        </button>
                        <a href="{{ route('subscription-plans.index') }}" class="btn btn-falcon-default btn-sm px-3">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
