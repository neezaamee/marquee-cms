<div class="container-fluid p-0">
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <i class="fas fa-check-circle me-1"></i>{{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($isFormOpen && $selectedRequest)
        <!-- ========================================== -->
        <!-- FULL PAGE: PROCESS STOCK DISPATCH          -->
        <!-- ========================================== -->
        <form wire:submit.prevent="issueStock">
            <!-- Top Action Header -->
            <div class="row mb-3">
                <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <button type="button" wire:click="closeDispatchForm" class="btn btn-outline-secondary btn-sm" title="Back to Requisitions">
                            <i class="fas fa-arrow-left me-1"></i>Back to List
                        </button>
                        <div class="ms-1">
                            <h4 class="mb-0 text-secondary d-flex align-items-center flex-wrap gap-2">
                                <span class="fas fa-shipping-fast text-primary"></span>
                                <span>Process Stock Dispatch</span>
                                <span class="badge bg-primary-subtle text-primary font-monospace fs-11">{{ $selectedRequest->request_number }}</span>
                                <span class="badge bg-secondary-subtle text-secondary fs-11">{{ $selectedRequest->department->name }}</span>
                            </h4>
                            <div class="text-muted fs-11 mt-1">Dispatch approved inventory from Central Warehouse to department</div>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" wire:click="closeDispatchForm" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-times me-1"></i>Cancel
                        </button>
                        <button type="submit" wire:click="issueStock" wire:loading.attr="disabled" class="btn btn-primary btn-sm">
                            <span wire:loading.remove wire:target="issueStock">
                                <i class="fas fa-shipping-fast me-1"></i>Submit Dispatch
                            </span>
                            <span wire:loading wire:target="issueStock">
                                <i class="fas fa-spinner fa-spin me-1"></i>Dispatching...
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Error Notification Banner -->
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                    <div class="d-flex align-items-center mb-1">
                        <i class="fas fa-exclamation-triangle me-2 fs-5"></i>
                        <strong class="fs-12">Please correct the following issues before submitting dispatch:</strong>
                    </div>
                    <ul class="mb-0 ps-3 fs-11">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- 1. REQUISITION & RECEIVER DETAILS -->
            <div class="card border border-200 shadow-sm mb-3">
                <div class="card-header bg-light py-2">
                    <h6 class="mb-0 text-700">
                        <i class="fas fa-info-circle me-2 text-primary"></i>Dispatch & Requisition Overview
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label mb-1 fw-semi-bold">Requisition No</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-200 text-muted"><i class="fas fa-hashtag"></i></span>
                                <input type="text" value="{{ $selectedRequest->request_number }}" class="form-control form-control-sm bg-200 font-monospace fw-bold" readonly>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label mb-1 fw-semi-bold">Request Date</label>
                            <input type="text" value="{{ $selectedRequest->request_date->format('Y-m-d') }}" class="form-control form-control-sm bg-200" readonly>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label mb-1 fw-semi-bold">Department</label>
                            <input type="text" value="{{ $selectedRequest->department->name }}" class="form-control form-control-sm bg-200 fw-semi-bold" readonly>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label mb-1 fw-semi-bold">
                                Received By (Dept Staff) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white text-muted"><i class="fas fa-user-check"></i></span>
                                <select wire:model="receiverEmployeeId" class="form-select form-select-sm @error('receiverEmployeeId') is-invalid @enderror">
                                    <option value="">-- Select Receiving Employee --</option>
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->id }}">
                                            {{ $employee->name }} ({{ $employee->designation ?? 'Staff' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @error('receiverEmployeeId') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    @if($selectedRequest->remarks)
                        <div class="mt-2 bg-light p-2 rounded border border-200 fs-11">
                            <span class="fw-semi-bold text-muted me-1"><i class="fas fa-comment-alt me-1"></i>Requisition Remarks:</span>
                            <span class="text-800">{{ $selectedRequest->remarks }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- 2. DISPATCH ITEMS TABLE -->
            <div class="card border border-200 shadow-sm mb-4">
                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 text-700">
                        <i class="fas fa-boxes me-2 text-primary"></i>Items to Dispatch / Issue
                        <span class="badge bg-primary-subtle text-primary ms-2">{{ count($issueQuantities) }} Items</span>
                    </h6>
                    <span class="text-muted fs-11">
                        <i class="fas fa-warehouse me-1"></i>Central warehouse balance will update automatically upon dispatch
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0 align-middle">
                            <thead class="bg-200 text-700 fs-11 text-uppercase">
                                <tr>
                                    <th class="ps-3" style="width: 40px;">#</th>
                                    <th>Inventory Item</th>
                                    <th class="text-center" style="width: 100px;">Unit</th>
                                    <th class="text-end" style="width: 110px;">Approved</th>
                                    <th class="text-end" style="width: 110px;">Already Issued</th>
                                    <th class="text-end" style="width: 110px;">Pending</th>
                                    <th class="text-end" style="width: 130px;">Central Stock</th>
                                    <th class="text-end" style="width: 140px;">Last Purchase Rate</th>
                                    <th class="text-end" style="width: 150px;">Qty to Dispatch <span class="text-danger">*</span></th>
                                    <th class="text-end pe-3" style="width: 140px;">Est. Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $currentDispatchTotal = 0; @endphp
                                @foreach($issueQuantities as $itemId => $data)
                                    @php
                                        $lineQty = (float) ($data['quantity'] ?? 0);
                                        $lineRate = (float) ($data['unit_price'] ?? 1);
                                        $lineTotal = $lineQty * $lineRate;
                                        $currentDispatchTotal += $lineTotal;
                                        $centralStock = (float) ($data['central_stock'] ?? 0);
                                        $pendingQty = (float) ($data['pending_qty'] ?? 0);
                                        $isLowStock = $centralStock < $pendingQty;
                                    @endphp
                                    <tr wire:key="dispatch-item-{{ $itemId }}">
                                        <td class="ps-3 fw-bold text-muted">{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="fw-semi-bold text-dark">{{ $data['item_name'] }}</div>
                                            @if(!empty($data['item_code']))
                                                <span class="badge badge-subtle-secondary fs-11 font-monospace">{{ $data['item_code'] }}</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary-subtle text-secondary fs-11">{{ $data['unit'] ?? 'Units' }}</span>
                                        </td>
                                        <td class="text-end font-monospace">{{ number_format($data['approved_qty'], 2) }}</td>
                                        <td class="text-end font-monospace text-muted">{{ number_format($data['already_issued'], 2) }}</td>
                                        <td class="text-end font-monospace fw-bold text-dark">{{ number_format($pendingQty, 2) }}</td>
                                        <td class="text-end font-monospace">
                                            <span class="badge {{ $centralStock <= 0 ? 'bg-danger-subtle text-danger' : ($isLowStock ? 'bg-warning-subtle text-warning' : 'bg-success-subtle text-success') }}">
                                                {{ number_format($centralStock, 2) }}
                                            </span>
                                        </td>
                                        <td class="text-end font-monospace fw-semi-bold text-dark">Rs. {{ number_format($lineRate, 2) }}</td>
                                        <td>
                                            <input type="number"
                                                   step="0.01"
                                                   min="0"
                                                   wire:model.live="issueQuantities.{{ $itemId }}.quantity"
                                                   class="form-control form-control-sm text-end font-monospace fw-bold @error('issueQuantities.'.$itemId.'.quantity') is-invalid @enderror"
                                                   placeholder="0.00">
                                            @error('issueQuantities.'.$itemId.'.quantity')
                                                <div class="invalid-feedback fs-11 text-end">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td class="text-end pe-3 font-monospace fw-bold text-primary">
                                            Rs. {{ number_format($lineTotal, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-light">
                                <tr>
                                    <th colspan="9" class="text-end text-uppercase fs-11 fw-bold py-2">
                                        Total Estimated Dispatch Value:
                                    </th>
                                    <th class="text-end pe-3 font-monospace fw-bold fs-9 text-primary py-2">
                                        Rs. {{ number_format($currentDispatchTotal, 2) }}
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-light d-flex justify-content-between align-items-center py-2">
                    <button type="button" wire:click="closeDispatchForm" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>Back to List
                    </button>
                    <div class="d-flex gap-2">
                        <button type="button" wire:click="closeDispatchForm" class="btn btn-secondary btn-sm">
                            Cancel
                        </button>
                        <button type="submit" wire:click="issueStock" wire:loading.attr="disabled" class="btn btn-primary btn-sm">
                            <span wire:loading.remove wire:target="issueStock">
                                <i class="fas fa-shipping-fast me-1"></i>Submit Dispatch
                            </span>
                            <span wire:loading wire:target="issueStock">
                                <i class="fas fa-spinner fa-spin me-1"></i>Dispatching...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </form>

    @else
        <!-- ========================================== -->
        <!-- FULL PAGE: PENDING REQUISITIONS (LIST)     -->
        <!-- ========================================== -->
        <div class="row mb-3 align-items-center justify-content-between">
            <div class="col-auto">
                <h4 class="mb-0 text-secondary">
                    <span class="fas fa-shipping-fast me-2 text-primary"></span>Warehouse Stock Dispatch / Issue
                </h4>
                <p class="text-600 fs-10 mb-0">Issue approved stock to departments and log central warehouse dispatch</p>
            </div>
            <div class="col-auto">
                <div class="d-flex align-items-center bg-white border border-200 rounded px-3 py-2 shadow-none">
                    <div class="avatar avatar-m bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center me-2">
                        <span class="fas fa-coins fs-9"></span>
                    </div>
                    <div>
                        <div class="fs-11 text-muted text-uppercase fw-semi-bold">Today's Total Stock Issued</div>
                        <div class="fs-9 font-monospace fw-bold text-primary">
                            Rs. {{ number_format($todayIssuedValue, 2) }}
                            <span class="text-muted fs-11 fw-normal">({{ number_format($todayIssuedQty, 2) }} units)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12">
                <div class="card border border-200 shadow-sm">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h5 class="mb-0 text-700">Requisitions Pending Dispatch</h5>
                        <div class="d-flex gap-2 flex-wrap">
                            <div class="input-group input-group-sm" style="min-width: 240px;">
                                <span class="input-group-text bg-white text-muted"><i class="fas fa-search"></i></span>
                                <input type="search"
                                       wire:model.live.debounce.300ms="search"
                                       class="form-control form-control-sm"
                                       placeholder="Search req #, dept, item...">
                            </div>
                            <select wire:model.live="filterDepartment" class="form-select form-select-sm" style="max-width: 180px;">
                                <option value="">All Departments</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
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
                                        <th>Items Requested</th>
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
                                            <td>
                                                <span class="badge badge-subtle-secondary mb-1">{{ $req->items->count() }} items</span>
                                                <div class="text-muted fs-11 text-truncate" style="max-width: 250px;" title="{{ $req->items->map(fn($i) => ($i->item->name ?? 'Item') . ' (' . $i->requested_qty . ')')->join(', ') }}">
                                                    {{ $req->items->map(fn($i) => $i->item->name ?? '')->filter()->join(', ') }}
                                                </div>
                                            </td>
                                            <td>{{ $req->requester->name ?? '—' }}</td>
                                            <td>
                                                <span class="badge badge-subtle-{{ $req->status === 'Partially Issued' ? 'warning' : 'info' }}">
                                                    {{ $req->status }}
                                                </span>
                                            </td>
                                            <td class="text-end px-3">
                                                <button wire:click="selectRequestForIssue({{ $req->id }})" class="btn btn-primary btn-sm">
                                                    <span class="fas fa-dolly me-1"></span>Issue Stock
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">
                                                <div class="py-3">
                                                    <i class="fas fa-check-circle text-success fs-2 mb-2"></i>
                                                    <div>No pending requisitions. All departments are fully stocked!</div>
                                                </div>
                                            </td>
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
    @endif
</div>
