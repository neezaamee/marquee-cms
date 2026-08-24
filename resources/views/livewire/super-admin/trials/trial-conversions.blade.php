<div>
    <!-- Page Header Section -->
    <div class="row align-items-center mb-4">
        <div class="col-md-8">
            <h2 class="mb-1 text-primary fw-bold">Trial Conversions</h2>
            <p class="text-secondary fs-12 mb-0">Review list of trial users who successfully converted to a paid subscription plan.</p>
        </div>
    </div>

    <div class="card shadow-sm border">
        <!-- Card Header Search -->
        <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center">
            <div style="max-width: 300px; width: 100%;">
                <input wire:model.live="search" class="form-control form-control-sm" type="search" placeholder="Search conversions..." />
            </div>
            <span class="badge badge-subtle-success fs-11">Total: {{ $conversions->total() }} Converted</span>
        </div>

        <!-- Table Listing -->
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle fs-12 mb-0">
                    <thead class="bg-light text-secondary">
                        <tr>
                            <th class="ps-3">Business Owner</th>
                            <th>Plan Name</th>
                            <th class="text-center">Trial Ends At</th>
                            <th class="text-center">Converted Ends At</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($conversions as $account)
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-bold text-dark">{{ $account->name }}</div>
                                    <div class="text-muted fs-11">{{ $account->email }}</div>
                                </td>
                                <td>{{ $account->subscriptionPlan->name }}</td>
                                <td class="text-center">{{ $account->subscription_trial_ends_at ? $account->subscription_trial_ends_at->format('d/m/Y') : '—' }}</td>
                                <td class="text-center">{{ $account->subscription_ends_at->format('d/m/Y') }}</td>
                                <td class="text-center">
                                    @if($account->subscription_ends_at->isPast())
                                        <span class="badge badge-subtle-danger">Expired Paid Plan</span>
                                    @else
                                        <span class="badge badge-subtle-success">Active Paid Plan</span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <a class="btn btn-falcon-default btn-xs" href="{{ route('super-admin.business-owners.show', $account->id) }}">
                                        <i class="fas fa-eye me-1"></i>View Details
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <span class="fas fa-folder-open fa-2x d-block mb-2 text-secondary"></span>
                                    No converted trial accounts found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($conversions->hasPages())
            <div class="card-footer bg-light py-2">
                {{ $conversions->links() }}
            </div>
        @endif
    </div>
</div>
