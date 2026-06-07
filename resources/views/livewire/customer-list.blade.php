<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><span class="fas fa-users me-2 text-primary"></span>Customer Management</h5>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <!-- Search -->
                <div class="input-group input-group-sm" style="max-width: 200px;">
                    <input wire:model.live.debounce.300ms="search" class="form-control" type="search" placeholder="Search customers..." />
                    <span class="input-group-text"><span class="fas fa-search"></span></span>
                </div>

                <!-- Type Filter -->
                <select wire:model.live="filterType" class="form-select form-select-sm" style="min-width:130px">
                    <option value="">All Types</option>
                    <option value="Individual">Individual</option>
                    <option value="Corporate">Corporate</option>
                </select>

                <!-- Status Filter -->
                <select wire:model.live="filterStatus" class="form-select form-select-sm" style="min-width:130px">
                    <option value="">All Statuses</option>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                    <option value="Blocked">Blocked</option>
                </select>

                @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('create_bookings'))
                    <a class="btn btn-falcon-primary btn-sm" href="{{ route('customers.create') }}">
                        <span class="fas fa-plus me-1" data-fa-transform="shrink-3"></span> Add Customer
                    </a>
                @endif
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

            @if(session('error'))
                <div class="alert alert-danger border-2 d-flex align-items-center m-3" role="alert">
                    <div class="bg-danger me-3 icon-item"><span class="fas fa-times-circle text-white fs-8"></span></div>
                    <p class="mb-0 flex-1">{{ session('error') }}</p>
                    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="table-responsive scrollbar">
                <table class="table table-sm table-striped fs-10 mb-0 align-middle">
                    <thead class="bg-200 text-900">
                        <tr>
                            <th class="px-3" style="width:50px">Photo</th>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Company</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>City</th>
                            <th class="text-center">Status</th>
                            <th class="text-end px-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                            <tr>
                                <td class="px-3">
                                    @if($customer->profile_photo)
                                        <img src="{{ asset('storage/' . $customer->profile_photo) }}" alt="{{ $customer->full_name }}" class="rounded-circle" width="36" height="36" style="object-fit:cover;">
                                    @else
                                        <div class="avatar avatar-xl" style="width:36px;height:36px;background:var(--falcon-200);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                                            <span class="fas fa-user text-500"></span>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-subtle-secondary fs-11 font-monospace">{{ $customer->customer_code }}</span>
                                </td>
                                <td class="fw-semi-bold">
                                    <a href="{{ route('customers.show', $customer->id) }}">{{ $customer->full_name }}</a>
                                </td>
                                <td>
                                    <span class="badge badge-subtle-{{ $customer->customer_type === 'Individual' ? 'info' : 'warning' }}">{{ $customer->customer_type }}</span>
                                </td>
                                <td>{{ $customer->company_name ?? '—' }}</td>
                                <td>{{ $customer->email ?? '—' }}</td>
                                <td>{{ $customer->phone_number }}</td>
                                <td>{{ $customer->city ?? '—' }}</td>
                                <td class="text-center">
                                    @php
                                        $statusColors = ['Active' => 'success', 'Inactive' => 'secondary', 'Blocked' => 'danger'];
                                        $sc = $statusColors[$customer->status] ?? 'secondary';
                                    @endphp
                                    <span class="badge badge-subtle-{{ $sc }} rounded-pill">{{ $customer->status }}</span>
                                </td>
                                <td class="text-end px-3">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a class="btn btn-link p-0" href="{{ route('customers.show', $customer->id) }}" data-bs-toggle="tooltip" title="View Profile">
                                            <span class="text-info fas fa-eye"></span>
                                        </a>
                                        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('edit_bookings'))
                                            <a class="btn btn-link p-0" href="{{ route('customers.edit', $customer->id) }}" data-bs-toggle="tooltip" title="Edit">
                                                <span class="text-primary fas fa-edit"></span>
                                            </a>
                                        @endif
                                        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('delete_bookings'))
                                            <button class="btn btn-link p-0" type="button" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal" wire:click="confirmDeletion({{ $customer->id }})" title="Delete Customer">
                                                <span class="text-danger fas fa-trash-alt"></span>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <span class="fas fa-users fa-2x mb-2 d-block"></span>
                                    No customers found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($customers->hasPages())
            <div class="card-footer d-flex align-items-center justify-content-center bg-light">
                {{ $customers->links() }}
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
                    <p class="mb-0 text-900">Are you sure you want to remove this customer profile? This action will soft-delete the record and is reversible, but they will be removed from all active lists.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-falcon-default btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button wire:click="deleteRecord" type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">
                        <span class="fas fa-trash-alt me-1"></span>Delete Profile
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
