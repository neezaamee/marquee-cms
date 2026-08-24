<div>
    <!-- Page Header Section -->
    <div class="row align-items-center mb-4">
        <div class="col-md-8">
            <h2 class="mb-1 text-primary fw-bold">Active & Expired Trials</h2>
            <p class="text-secondary fs-12 mb-0">Monitor active trial accounts, extend their trial durations, or convert them into paid subscribers.</p>
        </div>
    </div>

    <!-- Alert Success System -->
    @if(session()->has('success'))
        <div class="alert alert-success border-2 d-flex align-items-center mb-3" role="alert">
            <span class="fas fa-check-circle me-2 fs-8"></span>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    <div class="card shadow-sm border">
        <!-- Card Header Search -->
        <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center">
            <div style="max-width: 300px; width: 100%;">
                <input wire:model.live="search" class="form-control form-control-sm" type="search" placeholder="Search trial accounts..." />
            </div>
            <span class="badge badge-subtle-primary fs-11">Total: {{ $trialAccounts->total() }} Accounts</span>
        </div>

        <!-- Table Listing -->
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle fs-12 mb-0">
                    <thead class="bg-light text-secondary">
                        <tr>
                            <th class="ps-3">Business Owner</th>
                            <th>Plan Name</th>
                            <th class="text-center">Trial Start Date</th>
                            <th class="text-center">Trial Ends At</th>
                            <th class="text-center">Remaining Days</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($trialAccounts as $account)
                            @php
                                $daysRemaining = now()->diffInDays($account->subscription_trial_ends_at, false);
                                $isExpired = $account->subscription_trial_ends_at->isPast();
                            @endphp
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-bold text-dark">{{ $account->name }}</div>
                                    <div class="text-muted fs-11">{{ $account->email }}</div>
                                </td>
                                <td>{{ $account->subscriptionPlan->name ?? 'Default Trial' }}</td>
                                <td class="text-center">{{ $account->created_at->format('d/m/Y') }}</td>
                                <td class="text-center">{{ $account->subscription_trial_ends_at->format('d/m/Y') }}</td>
                                <td class="text-center">
                                    @if($isExpired)
                                        <span class="text-danger fw-bold">Expired</span>
                                    @else
                                        <span class="text-success fw-bold">{{ max(0, $daysRemaining) }} Days</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($isExpired)
                                        <span class="badge badge-subtle-danger">Expired</span>
                                    @else
                                        <span class="badge badge-subtle-info">Active</span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <div class="dropdown">
                                        <button class="btn btn-falcon-default btn-xs dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            Manage
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end py-1">
                                            <a class="dropdown-item" href="{{ route('super-admin.business-owners.show', $account->id) }}">
                                                <i class="fas fa-eye me-2 text-primary"></i>View Details
                                            </a>
                                            <button wire:click="selectUserForExtend({{ $account->id }})" class="dropdown-item">
                                                <i class="fas fa-calendar-plus me-2 text-warning"></i>Extend Trial
                                            </button>
                                            <button wire:click="selectUserForConvert({{ $account->id }})" class="dropdown-item">
                                                <i class="fas fa-check-double me-2 text-success"></i>Convert to Paid
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <span class="fas fa-folder-open fa-2x d-block mb-2 text-secondary"></span>
                                    No active or expired trial accounts found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($trialAccounts->hasPages())
            <div class="card-footer bg-light py-2">
                {{ $trialAccounts->links() }}
            </div>
        @endif
    </div>

    <!-- Extend Trial Modal -->
    @if($showExtendModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Extend Trial for {{ $selectedUser->name }}</h5>
                        <button type="button" class="btn-close" wire:click="closeModals"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label" for="new_trial_ends_at">New Trial Ends At</label>
                            <input wire:model="new_trial_ends_at" type="date" class="form-control" id="new_trial_ends_at" />
                            @error('new_trial_ends_at') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" wire:click="closeModals">Cancel</button>
                        <button type="button" class="btn btn-primary btn-sm" wire:click="extendTrial">Extend Trial</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Convert to Paid Modal -->
    @if($showConvertModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Convert {{ $selectedUser->name }} to Paid</h5>
                        <button type="button" class="btn-close" wire:click="closeModals"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label" for="plan_id">Select Plan</label>
                            <select wire:model="plan_id" class="form-select" id="plan_id">
                                <option value="">-- Select Subscription Plan --</option>
                                @foreach($plans as $plan)
                                    <option value="{{ $plan->id }}">{{ $plan->name }} (PKR {{ number_format($plan->price, 0) }})</option>
                                @endforeach
                            </select>
                            @error('plan_id') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="billing_cycle_id">Select Billing Cycle</label>
                            <select wire:model="billing_cycle_id" class="form-select" id="billing_cycle_id">
                                <option value="">-- Select Billing Term --</option>
                                @foreach($cycles as $cycle)
                                    <option value="{{ $cycle->id }}">{{ $cycle->cycle_name }} ({{ $cycle->duration_in_months }} months @if($cycle->discount_percentage > 0) -{{ $cycle->discount_percentage }}% off @endif)</option>
                                @endforeach
                            </select>
                            @error('billing_cycle_id') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-check mb-3">
                            <input wire:model="mark_as_paid" type="checkbox" class="form-check-input" id="mark_as_paid" />
                            <label class="form-check-label" for="mark_as_paid">Mark invoice as Paid immediately (records cash payment and posts ledger)</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" wire:click="closeModals">Cancel</button>
                        <button type="button" class="btn btn-success btn-sm" wire:click="convertToPaid">Convert to Paid</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
