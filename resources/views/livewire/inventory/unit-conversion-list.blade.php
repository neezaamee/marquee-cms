<div>
    <div class="row g-3">
        <!-- Sidebar Form to Create/Edit Conversion -->
        @if($showForm)
            <div class="col-md-4">
                <div class="card border border-200">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">{{ $editId ? 'Edit Conversion' : 'Create Conversion' }}</h5>
                    </div>
                    <div class="card-body">
                        <form wire:submit.prevent="save">
                            <div class="mb-3">
                                <label class="form-label" for="from-unit">Source Unit (From) *</label>
                                <select wire:model="from_unit_id" class="form-select @error('from_unit_id') is-invalid @enderror" id="from-unit">
                                    <option value="">Select Unit...</option>
                                    @foreach($activeUnits as $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->short_code }})</option>
                                    @endforeach
                                </select>
                                @error('from_unit_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="to-unit">Target Unit (To) *</label>
                                <select wire:model="to_unit_id" class="form-select @error('to_unit_id') is-invalid @enderror" id="to-unit">
                                    <option value="">Select Unit...</option>
                                    @foreach($activeUnits as $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->short_code }})</option>
                                    @endforeach
                                </select>
                                @error('to_unit_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="item-override">Item Override (Optional)</label>
                                <select wire:model="inventory_item_id" class="form-select @error('inventory_item_id') is-invalid @enderror" id="item-override">
                                    <option value="">Global (All Items)</option>
                                    @foreach($activeItems as $item)
                                        <option value="{{ $item->id }}">[{{ $item->item_code }}] {{ $item->name }}</option>
                                    @endforeach
                                </select>
                                @error('inventory_item_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="factor">Conversion Factor *</label>
                                <input wire:model="factor" type="number" step="0.0001" class="form-control @error('factor') is-invalid @enderror" id="factor" placeholder="e.g. 1000.00" />
                                <div class="fs-11 text-muted mt-1">Multiplier to get from Source to Target (e.g. 1 Kg = 1000 g, Factor is 1000)</div>
                                @error('factor') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" wire:click="resetForm" class="btn btn-falcon-default btn-sm">Cancel</button>
                                <button type="submit" class="btn btn-falcon-primary btn-sm">
                                    <span class="fas fa-save me-1"></span>Save
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <!-- Main Conversions Listing Table -->
        <div class="{{ $showForm ? 'col-md-8' : 'col-md-12' }}">
            <div class="card border border-200">
                <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0"><span class="fas fa-exchange-alt me-2 text-primary"></span>UOM Conversion Rates</h5>
                    <div class="d-flex align-items-center gap-2">
                        <!-- Search -->
                        <div class="input-group input-group-sm">
                            <input wire:model.live.debounce.300ms="search" class="form-control" type="search" placeholder="Search conversions..." />
                            <span class="input-group-text"><span class="fas fa-search"></span></span>
                        </div>
                        @if(!$showForm && (auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_inventory')))
                            <button wire:click="create" class="btn btn-falcon-primary btn-sm text-nowrap">
                                <span class="fas fa-plus me-1"></span>Add Conversion Mapping
                            </button>
                        @endif
                    </div>
                </div>

                <div class="card-body p-0">
                    @if(session('success'))
                        <div class="alert alert-success border-2 d-flex align-items-center m-3 animate__animated animate__fadeIn" role="alert">
                            <div class="bg-success me-3 icon-item"><span class="fas fa-check-circle text-white fs-8"></span></div>
                            <p class="mb-0 flex-1">{{ session('success') }}</p>
                            <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger border-2 d-flex align-items-center m-3 animate__animated animate__fadeIn" role="alert">
                            <div class="bg-danger me-3 icon-item"><span class="fas fa-times-circle text-white fs-8"></span></div>
                            <p class="mb-0 flex-1">{{ session('error') }}</p>
                            <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="table-responsive scrollbar">
                        <table class="table table-sm table-striped fs-10 mb-0 align-middle">
                            <thead class="bg-200 text-900">
                                <tr>
                                    <th class="px-3">Scope / Item</th>
                                    <th>Source Unit (From)</th>
                                    <th>Target Unit (To)</th>
                                    <th class="text-end">Factor</th>
                                    <th class="text-end px-3" style="width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($conversions as $conv)
                                    <tr>
                                        <td class="px-3 fw-semi-bold">
                                            @if($conv->inventoryItem)
                                                <span class="badge badge-subtle-info fs-11">Item Override: {{ $conv->inventoryItem->name }} ({{ $conv->inventoryItem->item_code }})</span>
                                            @else
                                                <span class="badge badge-subtle-success fs-11">Global Default (All Items)</span>
                                            @endif
                                        </td>
                                        <td>{{ $conv->fromUnit->name ?? '—' }} ({{ $conv->fromUnit->short_code ?? '' }})</td>
                                        <td>{{ $conv->toUnit->name ?? '—' }} ({{ $conv->toUnit->short_code ?? '' }})</td>
                                        <td class="text-end font-monospace">{{ number_format($conv->factor, 4) }}</td>
                                        <td class="text-end px-3">
                                            <div class="d-flex justify-content-end gap-2">
                                                @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_inventory'))
                                                    <button wire:click="edit({{ $conv->id }})" class="btn btn-link p-0" title="Edit">
                                                        <span class="text-primary fas fa-edit"></span>
                                                    </button>
                                                    <button class="btn btn-link p-0" type="button" data-bs-toggle="modal" data-bs-target="#deleteConversionModal" wire:click="confirmDeletion({{ $conv->id }})" title="Delete">
                                                        <span class="text-danger fas fa-trash-alt"></span>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">No UOM conversions defined.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($conversions->hasPages())
                    <div class="card-footer d-flex align-items-center justify-content-center bg-light">
                        {{ $conversions->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div wire:ignore.self class="modal fade" id="deleteConversionModal" tabindex="-1" aria-labelledby="deleteConversionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title text-white" id="deleteConversionModalLabel">
                        <span class="fas fa-exclamation-triangle me-2"></span>Confirm Deletion
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-start">
                    <p class="mb-0 text-900">Are you sure you want to remove this UOM conversion mapping? Recipe calculations relying on this conversion will fail.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-falcon-default btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button wire:click="deleteRecord" type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">
                        <span class="fas fa-trash-alt me-1"></span>Delete Mapping
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
