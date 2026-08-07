<div class="container-fluid p-0">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4 class="mb-0 text-secondary"><span class="fas fa-file-invoice me-2 text-primary"></span>Department Requisitions</h4>
            <button wire:click="openCreateForm" class="btn btn-primary btn-sm">
                <span class="fas fa-plus me-1"></span>New Request
            </button>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-3">
        <!-- Form Panel (Conditional) -->
        @if($isFormOpen)
            <div class="col-md-5">
                <div class="card border border-200">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Create Stock Requisition</h5>
                    </div>
                    <div class="card-body">
                        <form wire:submit.prevent="save">
                            <div class="row g-2">
                                <div class="col-6 mb-2">
                                    <label class="form-label mb-1">Request No</label>
                                    <input type="text" wire:model="request_number" class="form-control form-control-sm bg-200" readonly>
                                </div>
                                <div class="col-6 mb-2">
                                    <label class="form-label mb-1">Request Date</label>
                                    <input type="date" wire:model="request_date" class="form-control form-control-sm @error('request_date') is-invalid @enderror">
                                    @error('request_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label mb-1">Department</label>
                                <select wire:model="department_id" class="form-select form-select-sm @error('department_id') is-invalid @enderror">
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                                @error('department_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <hr class="my-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0 fw-semi-bold">Request Items</h6>
                                <button type="button" wire:click="addItemRow" class="btn btn-link btn-sm p-0 text-decoration-none">
                                    <span class="fas fa-plus-circle me-1"></span>Add Item
                                </button>
                            </div>

                            @error('formItems') <div class="alert alert-danger p-2 fs-11">{{ $message }}</div> @enderror

                            <div style="max-height: 250px; overflow-y: auto;">
                                @foreach($formItems as $idx => $formItem)
                                    <div class="row g-2 align-items-center mb-2">
                                        <div class="col-7">
                                            <select wire:model="formItems.{{ $idx }}.item_id" class="form-select form-select-sm @error('formItems.'.$idx.'.item_id') is-invalid @enderror">
                                                <option value="">Select Item</option>
                                                @foreach($inventoryItems as $item)
                                                    <option value="{{ $item->id }}">{{ $item->name }} ({{ $item->item_code }})</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-3">
                                            <input type="number" step="0.01" wire:model="formItems.{{ $idx }}.requested_qty" class="form-control form-control-sm @error('formItems.'.$idx.'.requested_qty') is-invalid @enderror" placeholder="Qty">
                                        </div>
                                        <div class="col-2 text-end">
                                            <button type="button" wire:click="removeItemRow({{ $idx }})" class="btn btn-link btn-sm text-danger p-0">
                                                <span class="fas fa-minus-circle"></span>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mb-3 mt-2">
                                <label class="form-label mb-1">Remarks</label>
                                <textarea wire:model="remarks" class="form-control form-control-sm" rows="2" placeholder="e.g. urgent requirement for upcoming event"></textarea>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" wire:click="$set('isFormOpen', false)" class="btn btn-secondary btn-sm">Cancel</button>
                                <button type="submit" class="btn btn-primary btn-sm">Submit Request</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <!-- Requisitions Grid Panel -->
        <div class="{{ $isFormOpen ? 'col-md-7' : 'col-12' }}">
            <div class="card border border-200">
                <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0">Requisitions Register</h5>
                    <div class="d-flex gap-2">
                        <select wire:model.live="filterDepartment" class="form-select form-select-sm" style="max-width: 150px;">
                            <option value="">All Departments</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                        <select wire:model.live="filterStatus" class="form-select form-select-sm" style="max-width: 120px;">
                            <option value="">All Statuses</option>
                            <option value="Submitted">Submitted</option>
                            <option value="Approved">Approved</option>
                            <option value="Partially Issued">Partially Issued</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0 align-middle">
                            <thead class="bg-200">
                                <tr>
                                    <th class="px-3">Req No</th>
                                    <th>Date</th>
                                    <th>Department</th>
                                    <th>Requested By</th>
                                    <th>Status</th>
                                    <th class="text-end px-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($requests as $req)
                                    <tr>
                                        <td class="px-3 font-monospace fw-bold">{{ $req->request_number }}</td>
                                        <td>{{ $req->request_date->format('Y-m-d') }}</td>
                                        <td class="fw-semi-bold">{{ $req->department->name }}</td>
                                        <td>{{ $req->requester->name ?? 'System' }}</td>
                                        <td>
                                            <span class="badge badge-subtle-{{ $req->status === 'Completed' ? 'success' : ($req->status === 'Submitted' ? 'info' : ($req->status === 'Cancelled' ? 'secondary' : 'warning')) }}">
                                                {{ $req->status }}
                                            </span>
                                        </td>
                                        <td class="text-end px-3">
                                            <button wire:click="viewDetails({{ $req->id }})" class="btn btn-outline-info btn-xs me-1">
                                                <span class="fas fa-eye"></span> View
                                            </button>
                                            @if($req->status === 'Submitted')
                                                <button wire:click="cancelRequest({{ $req->id }})" class="btn btn-outline-danger btn-xs">
                                                    Cancel
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">No requisitions found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($requests->hasPages())
                    <div class="card-footer bg-light p-2">
                        {{ $requests->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- View Details Modal -->
    @if($isViewModalOpen && $viewRequest)
        <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title">Requisition Details: {{ $viewRequest->request_number }}</h5>
                        <button type="button" class="btn-close" wire:click="$set('isViewModalOpen', false)"></button>
                    </div>
                    <div class="modal-body p-3">
                        <div class="row g-2 mb-3">
                            <div class="col-md-3">
                                <div class="text-500 fs-11 text-uppercase">Department</div>
                                <div class="fw-bold text-dark">{{ $viewRequest->department->name ?? '—' }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-500 fs-11 text-uppercase">Requested By</div>
                                <div class="fw-bold text-dark">{{ $viewRequest->requester->name ?? 'System' }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-500 fs-11 text-uppercase">Request Date</div>
                                <div class="fw-bold text-dark">{{ $viewRequest->request_date->format('Y-m-d') }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-500 fs-11 text-uppercase">Status</div>
                                <span class="badge bg-primary fs-11">{{ $viewRequest->status }}</span>
                            </div>
                        </div>

                        <h6 class="fw-semi-bold border-bottom pb-2">Requested Items</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped mb-0 align-middle">
                                <thead class="bg-200">
                                    <tr>
                                        <th>Item Name</th>
                                        <th>Code</th>
                                        <th class="text-end">Requested Qty</th>
                                        <th class="text-end">Approved Qty</th>
                                        <th>Unit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($viewRequest->items as $item)
                                        <tr>
                                            <td class="fw-semi-bold">{{ $item->inventoryItem->name ?? '—' }}</td>
                                            <td class="font-monospace fs-11">{{ $item->inventoryItem->item_code ?? '—' }}</td>
                                            <td class="text-end font-monospace text-primary fw-bold">{{ number_format($item->requested_qty, 2) }}</td>
                                            <td class="text-end font-monospace text-success fw-bold">{{ number_format($item->approved_qty, 2) }}</td>
                                            <td>{{ $item->inventoryItem->unit->short_code ?? 'Pcs' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($viewRequest->remarks)
                            <div class="mt-3 bg-light p-2 rounded">
                                <small class="text-muted d-block fw-semi-bold">Remarks:</small>
                                <span class="fs-10 text-800">{{ $viewRequest->remarks }}</span>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer bg-light p-2">
                        <button type="button" class="btn btn-secondary btn-sm" wire:click="$set('isViewModalOpen', false)">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
