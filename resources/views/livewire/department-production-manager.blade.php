<div class="container-fluid p-0">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4 class="mb-0 text-secondary">
                <span class="fas fa-fire me-2 text-primary"></span>Kitchen Production Batches
            </h4>
            <button wire:click="openCreateForm" class="btn btn-primary btn-sm">
                <span class="fas fa-plus me-1"></span>Log Production
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
        <!-- Production Form Panel -->
        @if($isFormOpen)
            <div class="col-md-5">
                <div class="card border border-200">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">New Production Batch</h5>
                    </div>
                    <div class="card-body">
                        <form wire:submit.prevent="save">
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label mb-1">Batch No</label>
                                    <input type="text" wire:model="batch_number" class="form-control form-control-sm bg-200" readonly>
                                </div>
                                <div class="col-6">
                                    <label class="form-label mb-1">Production Date</label>
                                    <input type="date" wire:model="production_date" class="form-control form-control-sm @error('production_date') is-invalid @enderror">
                                    @error('production_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label mb-1">Kitchen Department</label>
                                <select wire:model.live="department_id" class="form-select form-select-sm @error('department_id') is-invalid @enderror">
                                    <option value="">Select Kitchen</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                                @error('department_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label mb-1">Linked Recipe</label>
                                    <select wire:model.live="recipe_id" class="form-select form-select-sm">
                                        <option value="">None</option>
                                        @foreach($recipes as $recipe)
                                            <option value="{{ $recipe->id }}">{{ $recipe->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label mb-1">Linked Booking</label>
                                    <select wire:model="booking_id" class="form-select form-select-sm">
                                        <option value="">None</option>
                                        @foreach($bookings as $booking)
                                            <option value="{{ $booking->id }}">{{ $booking->booking_reference }} — {{ $booking->customer->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            @if($recipe_id)
                                <div class="mb-2">
                                    <button type="button" wire:click="loadRecipeIngredients" class="btn btn-outline-info btn-sm w-100">
                                        <span class="fas fa-magic me-1"></span>Auto-fill from Recipe
                                    </button>
                                </div>
                            @endif

                            <div class="row g-2 mb-2">
                                <div class="col-4">
                                    <label class="form-label mb-1">Produced Qty</label>
                                    <input type="number" step="0.01" wire:model="produced_qty" class="form-control form-control-sm @error('produced_qty') is-invalid @enderror">
                                    @error('produced_qty') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-4">
                                    <label class="form-label mb-1">Wastage Qty</label>
                                    <input type="number" step="0.01" wire:model="wastage_qty" class="form-control form-control-sm @error('wastage_qty') is-invalid @enderror">
                                    @error('wastage_qty') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-4">
                                    <label class="form-label mb-1">Prod. Time</label>
                                    <input type="text" wire:model="production_time" class="form-control form-control-sm" placeholder="e.g. 02:30">
                                </div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label mb-1">Prepared By</label>
                                <select wire:model="prepared_by" class="form-select form-select-sm">
                                    <option value="">Select Staff</option>
                                    @foreach($employees as $emp)
                                        <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <hr class="my-2">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0 fw-semi-bold">Raw Materials Consumed</h6>
                                <button type="button" wire:click="addItemRow" class="btn btn-link btn-sm p-0 text-decoration-none">
                                    <span class="fas fa-plus-circle me-1"></span>Add
                                </button>
                            </div>

                            @error('formItems') <div class="alert alert-danger p-2 fs-11">{{ $message }}</div> @enderror

                            <div style="max-height: 200px; overflow-y: auto;">
                                @foreach($formItems as $idx => $formItem)
                                    <div class="row g-2 align-items-center mb-2">
                                        <div class="col-7">
                                            <select wire:model="formItems.{{ $idx }}.item_id" class="form-select form-select-sm @error('formItems.'.$idx.'.item_id') is-invalid @enderror">
                                                <option value="">Select Item</option>
                                                @foreach($inventoryItems as $item)
                                                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-3">
                                            <input type="number" step="0.01" wire:model="formItems.{{ $idx }}.quantity" class="form-control form-control-sm @error('formItems.'.$idx.'.quantity') is-invalid @enderror" placeholder="Qty">
                                            @error('formItems.'.$idx.'.quantity') <div class="invalid-feedback fs-11">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-2 text-end">
                                            <button type="button" wire:click="removeItemRow({{ $idx }})" class="btn btn-link btn-sm text-danger p-0">
                                                <span class="fas fa-minus-circle"></span>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mb-2 mt-2">
                                <textarea wire:model="notes" class="form-control form-control-sm" rows="2" placeholder="Production notes..."></textarea>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" wire:click="$set('isFormOpen', false)" class="btn btn-secondary btn-sm">Cancel</button>
                                <button type="submit" class="btn btn-primary btn-sm">Log Batch</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <!-- Production Batch Register -->
        <div class="{{ $isFormOpen ? 'col-md-7' : 'col-12' }}">
            <div class="card border border-200">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Production Batch Register</h5>
                    <select wire:model.live="filterDepartment" class="form-select form-select-sm" style="max-width: 160px;">
                        <option value="">All Kitchens</option>
                        @foreach($allDepartments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0 align-middle">
                            <thead class="bg-200">
                                <tr>
                                    <th class="px-3">Batch No</th>
                                    <th>Date</th>
                                    <th>Department</th>
                                    <th>Recipe</th>
                                    <th class="text-end">Produced</th>
                                    <th class="text-end">Wastage</th>
                                    <th class="text-end px-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($productions as $prod)
                                    <tr>
                                        <td class="px-3 font-monospace fw-bold">{{ $prod->batch_number }}</td>
                                        <td>{{ $prod->production_date->format('Y-m-d') }}</td>
                                        <td class="fw-semi-bold">{{ $prod->department->name }}</td>
                                        <td>{{ $prod->recipe->name ?? '—' }}</td>
                                        <td class="text-end font-monospace text-success fw-bold">{{ number_format($prod->produced_qty, 2) }}</td>
                                        <td class="text-end font-monospace text-danger">{{ number_format($prod->wastage_qty, 2) }}</td>
                                        <td class="text-end px-3">
                                            <button wire:click="viewDetails({{ $prod->id }})" class="btn btn-outline-info btn-xs">
                                                <span class="fas fa-eye"></span> View
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">No production batches logged.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($productions->hasPages())
                    <div class="card-footer bg-light p-2">
                        {{ $productions->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Production Details Modal -->
    @if($isViewModalOpen && $viewProduction)
        <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title">Production Batch Details: {{ $viewProduction->batch_number }}</h5>
                        <button type="button" class="btn-close" wire:click="$set('isViewModalOpen', false)"></button>
                    </div>
                    <div class="modal-body p-3">
                        <div class="row g-2 mb-3">
                            <div class="col-md-3">
                                <div class="text-500 fs-11 text-uppercase">Kitchen Department</div>
                                <div class="fw-bold text-dark">{{ $viewProduction->department->name ?? '—' }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-500 fs-11 text-uppercase">Production Date</div>
                                <div class="fw-bold text-dark">{{ $viewProduction->production_date->format('Y-m-d') }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-500 fs-11 text-uppercase">Recipe / Booking</div>
                                <div class="fw-bold text-dark">{{ $viewProduction->recipe->name ?? 'Custom Batch' }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-500 fs-11 text-uppercase">Prepared By</div>
                                <div class="fw-bold text-dark">{{ $viewProduction->prepStaff->name ?? 'System' }}</div>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-4">
                                <div class="p-2 bg-success-subtle rounded text-center">
                                    <span class="fs-11 text-uppercase text-success fw-bold d-block">Produced Qty</span>
                                    <span class="fs-4 font-monospace fw-bold text-success">{{ number_format($viewProduction->produced_qty, 2) }}</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-2 bg-danger-subtle rounded text-center">
                                    <span class="fs-11 text-uppercase text-danger fw-bold d-block">Wastage Qty</span>
                                    <span class="fs-4 font-monospace fw-bold text-danger">{{ number_format($viewProduction->wastage_qty, 2) }}</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-2 bg-light rounded text-center border">
                                    <span class="fs-11 text-uppercase text-muted fw-bold d-block">Production Time</span>
                                    <span class="fs-4 font-monospace fw-bold text-dark">{{ $viewProduction->production_time ?? '—' }}</span>
                                </div>
                            </div>
                        </div>

                        <h6 class="fw-semi-bold border-bottom pb-2">Raw Materials Consumed</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped mb-0 align-middle">
                                <thead class="bg-200">
                                    <tr>
                                        <th>Raw Material Item</th>
                                        <th>Code</th>
                                        <th class="text-end">Quantity Consumed</th>
                                        <th>Unit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($viewProduction->items as $item)
                                        <tr>
                                            <td class="fw-semi-bold">{{ $item->item->name ?? '—' }}</td>
                                            <td class="font-monospace fs-11">{{ $item->item->item_code ?? '—' }}</td>
                                            <td class="text-end font-monospace text-primary fw-bold">{{ number_format($item->quantity, 2) }}</td>
                                            <td>{{ $item->item->unit->short_code ?? 'Pcs' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($viewProduction->notes)
                            <div class="mt-3 bg-light p-2 rounded">
                                <small class="text-muted d-block fw-semi-bold">Production Notes:</small>
                                <span class="fs-10 text-800">{{ $viewProduction->notes }}</span>
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
