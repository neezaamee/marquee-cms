<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">Billing Cycles</h5>
            <div class="d-flex align-items-center gap-2">
                <div class="input-group input-group-sm">
                    <input wire:model.live.debounce.300ms="search" class="form-control" type="search" placeholder="Search cycles..." />
                    <span class="input-group-text"><span class="fas fa-search"></span></span>
                </div>
                <a class="btn btn-falcon-primary btn-sm" href="{{ route('billing-cycles.create') }}">
                    <span class="fas fa-plus me-1" data-fa-transform="shrink-3"></span> Add Billing Cycle
                </a>
            </div>
        </div>

        <div class="card-body p-0">
            @if(session('success'))
                <div class="alert alert-success border-2 d-flex align-items-center m-3" role="alert">
                    <div class="bg-success me-3 icon-item"><span class="fas fa-check-circle text-white fs-8"></span></div>
                    <p class="mb-0 flex-1">{{ session('success') }}</p>
                    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="table-responsive scrollbar">
                <table class="table table-sm table-striped fs-10 mb-0">
                    <thead class="bg-200 text-900">
                        <tr>
                            <th class="align-middle px-3">Cycle Name</th>
                            <th class="align-middle">Duration (Months)</th>
                            <th class="align-middle text-center">Discount Percentage</th>
                            <th class="align-middle text-center">Status</th>
                            <th class="align-middle text-end px-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cycles as $cycle)
                            <tr>
                                <td class="align-middle px-3 fw-semi-bold">{{ $cycle->cycle_name }}</td>
                                <td class="align-middle">{{ $cycle->duration_in_months }} months</td>
                                <td class="align-middle text-center">
                                    @if($cycle->discount_percentage > 0)
                                        <span class="badge rounded-pill bg-success">-{{ $cycle->discount_percentage }}%</span>
                                    @else
                                        <span class="text-muted">None</span>
                                    @endif
                                </td>
                                <td class="align-middle text-center">
                                    <span class="badge badge-subtle-{{ $cycle->status === 'Active' ? 'success' : 'secondary' }} rounded-pill">
                                        {{ $cycle->status }}
                                    </span>
                                </td>
                                <td class="align-middle text-end px-3">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a class="btn btn-link p-0" href="{{ route('billing-cycles.edit', $cycle->id) }}" data-bs-toggle="tooltip" title="Edit Cycle">
                                            <span class="text-primary fas fa-edit"></span>
                                        </a>
                                        <button class="btn btn-link p-0" type="button" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal" wire:click="confirmDeletion({{ $cycle->id }})" title="Delete Cycle">
                                            <span class="text-danger fas fa-trash-alt"></span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No billing cycles defined.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($cycles->hasPages())
            <div class="card-footer d-flex align-items-center justify-content-center bg-light">
                {{ $cycles->links() }}
            </div>
        @endif
    </div>

    <!-- Delete Confirmation Modal -->
    <div wire:ignore.self class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title text-white" id="deleteConfirmModalLabel">
                        <span class="fas fa-exclamation-triangle me-2"></span>Confirm Deletion
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-start">
                    <p class="mb-0 text-900">Are you sure you want to delete this billing cycle? Plans mapped to this cycle will lose the relationship mapping.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-falcon-default btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button wire:click="deleteRecord" type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">
                        <span class="fas fa-trash-alt me-1"></span>Delete Permanently
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
