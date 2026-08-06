<div>
    <div class="row g-3">
        <!-- Recipe Editor -->
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0 text-primary"><i class="fas fa-utensils me-2"></i>Dish Recipe Mappings</h5>
                    <p class="mb-0 text-muted small">Define raw material counts consumed per plate for billing and stock management.</p>
                </div>
                <div class="card-body">
                    @if (session()->has('success'))
                        <div class="alert alert-success border-2 d-flex align-items-center" role="alert">
                            <div class="bg-success me-3 icon-item"><span class="fas fa-check-circle text-white fs-3"></span></div>
                            <p class="mb-0 flex-1">{{ session('success') }}</p>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Dish / Menu Item</label>
                        <select wire:model.live="selectedMenuItemId" class="form-select">
                            <option value="">-- Choose Menu Item --</option>
                            @foreach($menuItems as $item)
                                <option value="{{ $item->id }}">{{ $item->item_name }} ({{ $item->item_code }})</option>
                            @endforeach
                        </select>
                    </div>

                    @if($selectedMenuItemId)
                        <form wire:submit.prevent="saveRecipe">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Cooking Instructions / Description</label>
                                <textarea wire:model="recipeDescription" class="form-control" rows="2" placeholder="Describe recipe preparation..."></textarea>
                            </div>

                            <label class="form-label fw-semibold d-flex justify-content-between align-items-center">
                                <span>Recipe Ingredients (per head)</span>
                                <button type="button" wire:click="addDetailRow" class="btn btn-link btn-sm p-0 text-primary"><i class="fas fa-plus-circle me-1"></i>Add Row</button>
                            </label>

                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th>Raw Material (Inventory Item)</th>
                                            <th style="width: 150px;">Qty per Head</th>
                                            <th style="width: 50px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recipeDetails as $index => $row)
                                            <tr>
                                                <td>
                                                    <select wire:model="recipeDetails.{{ $index }}.inventory_item_id" class="form-select form-select-sm">
                                                        <option value="">-- Select Material --</option>
                                                        @foreach($inventoryItems as $invItem)
                                                            <option value="{{ $invItem->id }}">{{ $invItem->name }} ({{ $invItem->unit ? $invItem->unit->short_code : 'Pcs' }})</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="number" step="0.0001" wire:model="recipeDetails.{{ $index }}.quantity_per_head" class="form-control form-control-sm" />
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" wire:click="removeDetailRow({{ $index }})" class="btn btn-link link-danger p-0"><i class="fas fa-trash"></i></button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-end mt-3">
                                <button type="submit" class="btn btn-primary btn-sm px-4">
                                    <i class="fas fa-save me-2"></i>Save Recipe Configuration
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="text-center py-4 text-muted">Please select a menu item to configure its recipe.</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recipe calculator -->
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0 text-primary"><i class="fas fa-calculator me-2"></i>Ingredient Calculator</h5>
                    <p class="mb-0 text-muted small">Calculate exact raw stock weights required for a booking based on plate recipes.</p>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Dish / Menu Item</label>
                        <select wire:model.live="calcMenuItemId" class="form-select">
                            <option value="">-- Choose Menu Item --</option>
                            @foreach($menuItems as $item)
                                <option value="{{ $item->id }}">{{ $item->item_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Estimated Guest Count</label>
                        <input type="number" wire:model.live="calcGuestCount" class="form-control" min="1" />
                    </div>

                    <div class="border-top pt-3">
                        <h6 class="fw-semibold text-900 mb-3"><i class="fas fa-boxes text-secondary me-2"></i>Required Quantities Matrix</h6>
                        @if(count($calcResults) > 0)
                            <div class="list-group list-group-flush fs--1">
                                @foreach($calcResults as $result)
                                    <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                        <div class="fw-medium text-800">{{ $result['name'] }}</div>
                                        <span class="badge badge-subtle-success fs-0">{{ number_format($result['required_qty'], 2) }} {{ $result['unit'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4 text-muted small">No ingredient outputs found. Ensure a recipe is mapped to the selected dish.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
