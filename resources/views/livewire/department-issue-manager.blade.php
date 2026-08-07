<div class="container-fluid p-0">
    <div class="row mb-3">
        <div class="col-12">
            <h4 class="mb-0 text-secondary"><span class="fas fa-shipping-fast me-2 text-primary"></span>Warehouse Stock Dispatch / Issue</h4>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-3">
        <!-- Requisitions pending dispatch -->
        <div class="{{ $isFormOpen ? 'col-md-6' : 'col-12' }}">
            <div class="card border border-200">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Pending Requisitions</h5>
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
                                        <td>{{ $req->requester->name ?? '—' }}</td>
                                        <td>
                                            <span class="badge badge-subtle-info">
                                                {{ $req->status }}
                                            </span>
                                        </td>
                                        <td class="text-end px-3">
                                            <button wire:click="selectRequestForIssue({{ $req->id }})" class="btn btn-primary btn-xs">
                                                <span class="fas fa-dolly me-1"></span>Issue Stock
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">No pending requisitions. All departments are fully stocked!</td>
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

        <!-- Issue Dispatch Form -->
        @if($isFormOpen)
            <div class="col-md-6">
                <div class="card border border-200">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Process Dispatch: {{ $selectedRequest->request_number }}</h5>
                        <span class="badge badge-subtle-secondary">{{ $selectedRequest->department->name }}</span>
                    </div>
                    <div class="card-body">
                        <form wire:submit.prevent="issueStock">
                            <div class="mb-3">
                                <label class="form-label mb-1">Received By (Dept Employee)</label>
                                <select wire:model="receiverEmployeeId" class="form-select form-select-sm @error('receiverEmployeeId') is-invalid @enderror">
                                    <option value="">Select Receiver</option>
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->id }}">{{ $employee->name }} ({{ $employee->designation ?? 'Staff' }})</option>
                                    @endforeach
                                </select>
                                @error('receiverEmployeeId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <hr class="my-3">
                            <h6 class="mb-3 fw-semi-bold"><span class="fas fa-boxes me-1 text-secondary"></span>Item quantities to dispatch</h6>

                            <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                                <table class="table table-sm table-striped mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Item Name</th>
                                            <th class="text-end" style="width: 70px;">Pending</th>
                                            <th class="text-end" style="width: 80px;">Central Stock</th>
                                            <th class="text-end" style="width: 100px;">Qty to Issue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($issueQuantities as $itemId => $data)
                                            <tr>
                                                <td class="fw-semi-bold">{{ $data['item_name'] }}</td>
                                                <td class="text-end font-monospace">{{ number_format($data['approved_qty'] - $data['already_issued'], 2) }}</td>
                                                <td class="text-end font-monospace text-muted">{{ number_format($data['central_stock'], 2) }}</td>
                                                <td>
                                                    <input type="number" step="0.01" wire:model="issueQuantities.{{ $itemId }}.quantity" class="form-control form-control-sm text-end py-0 @error('issueQuantities.'.$itemId.'.quantity') is-invalid @enderror">
                                                    @error('issueQuantities.'.$itemId.'.quantity') <div class="invalid-feedback fs-11">{{ $message }}</div> @enderror
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-end gap-2 mt-3">
                                <button type="button" wire:click="$set('isFormOpen', false)" class="btn btn-secondary btn-sm">Cancel</button>
                                <button type="submit" class="btn btn-primary btn-sm">Dispatch Stock</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
