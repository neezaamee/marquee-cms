<div class="container-fluid p-0">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4 class="mb-0 text-secondary"><span class="fas fa-undo-alt me-2 text-primary"></span>Department Returns</h4>
            <button wire:click="openCreateForm" class="btn btn-primary btn-sm">
                <span class="fas fa-plus me-1"></span>Log Return
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
        <!-- Return Form Panel -->
        @if($isFormOpen)
            <div class="col-md-5">
                <div class="card border border-200">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Create Stock Return</h5>
                    </div>
                    <div class="card-body">
                        <form wire:submit.prevent="save">
                            <div class="mb-2">
                                <label class="form-label mb-1">Source Department</label>
                                <select wire:model.live="department_id" class="form-select form-select-sm @error('department_id') is-invalid @enderror">
                                    <option value="">Select Department</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                                @error('department_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-2">
                                <label class="form-label mb-1">Returned By (Employee)</label>
                                <select wire:model="returned_by" class="form-select form-select-sm @error('returned_by') is-invalid @enderror" @if(!$department_id) disabled @endif>
                                    <option value="">Select Employee</option>
                                    @foreach($employees as $emp)
                                        <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                                    @endforeach
                                </select>
                                @error('returned_by') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <hr class="my-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0 fw-semi-bold">Return Items</h6>
                                <button type="button" wire:click="addItemRow" class="btn btn-link btn-sm p-0 text-decoration-none">
                                    <span class="fas fa-plus-circle me-1"></span>Add Item
                                </button>
                            </div>

                            @error('formItems') <div class="alert alert-danger p-2 fs-11">{{ $message }}</div> @enderror

                            <div style="max-height: 250px; overflow-y: auto;">
                                @foreach($formItems as $idx => $formItem)
                                    <div class="row g-2 align-items-center mb-2">
                                        <div class="col-5">
                                            <select wire:model="formItems.{{ $idx }}.item_id" class="form-select form-select-sm @error('formItems.'.$idx.'.item_id') is-invalid @enderror">
                                                <option value="">Select Item</option>
                                                @foreach($inventoryItems as $item)
                                                    <option value="{{ $item->id }}">{{ $item->name }} ({{ $item->item_code }})</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-3">
                                            <input type="number" step="0.01" wire:model="formItems.{{ $idx }}.quantity" class="form-control form-control-sm @error('formItems.'.$idx.'.quantity') is-invalid @enderror" placeholder="Qty">
                                            @error('formItems.'.$idx.'.quantity') <div class="invalid-feedback fs-11">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-3">
                                            <select wire:model="formItems.{{ $idx }}.status" class="form-select form-select-sm">
                                                <option value="Good">Good</option>
                                                <option value="Damaged">Damaged</option>
                                                <option value="Wastage">Wastage</option>
                                            </select>
                                        </div>
                                        <div class="col-1 text-end">
                                            <button type="button" wire:click="removeItemRow({{ $idx }})" class="btn btn-link btn-sm text-danger p-0">
                                                <span class="fas fa-minus-circle"></span>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mb-3 mt-2">
                                <label class="form-label mb-1">Remarks / Reason</label>
                                <textarea wire:model="remarks" class="form-control form-control-sm" rows="2" placeholder="e.g. leftover banquet materials"></textarea>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" wire:click="$set('isFormOpen', false)" class="btn btn-secondary btn-sm">Cancel</button>
                                <button type="submit"
                                    wire:loading.attr="disabled"
                                    class="btn btn-primary btn-sm">
                                    <span wire:loading.remove>Submit Return</span>
                                    <span wire:loading><span class="fas fa-spinner fa-spin me-1"></span>Submitting...</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <!-- Returns Register List -->
        <div class="{{ $isFormOpen ? 'col-md-7' : 'col-12' }}">
            <div class="card border border-200">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Returns Register</h5>
                    <select wire:model.live="filterDepartment" class="form-select form-select-sm" style="max-width: 150px;">
                        <option value="">All Departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0 align-middle">
                            <thead class="bg-200">
                                <tr>
                                    <th class="px-3">Ret No</th>
                                    <th>Date</th>
                                    <th>Department</th>
                                    <th>Returned By</th>
                                    <th>Status</th>
                                    <th class="text-end px-3">Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($returns as $ret)
                                    <tr>
                                        <td class="px-3 font-monospace fw-bold">{{ $ret->return_number }}</td>
                                        <td>{{ $ret->return_date->format('Y-m-d') }}</td>
                                        <td class="fw-semi-bold">{{ $ret->department->name }}</td>
                                        <td>{{ $ret->returner->name ?? '—' }}</td>
                                        <td>
                                            <span class="badge badge-subtle-success">
                                                {{ $ret->status }}
                                            </span>
                                        </td>
                                        <td class="text-end px-3 text-muted fs-11">{{ $ret->remarks ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">No returns logged yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($returns->hasPages())
                    <div class="card-footer bg-light p-2">
                        {{ $returns->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
