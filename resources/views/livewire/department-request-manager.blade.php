@php
    $departmentsJson = $departments->map(fn($d) => [
        'id' => $d->id,
        'name' => $d->name,
        'code' => $d->department_code ?? '',
    ])->values();

    $itemsJson = $inventoryItems->map(fn($item) => [
        'id' => $item->id,
        'name' => $item->name,
        'code' => $item->item_code ?? '',
        'unit' => $item->unit->name ?? ($item->unit->abbreviation ?? ''),
    ])->values();
@endphp

<div class="container-fluid p-0">
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <i class="fas fa-check-circle me-1"></i>{{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($isFormOpen)
        <!-- ========================================== -->
        <!-- FULL PAGE: CREATE STOCK REQUISITION        -->
        <!-- ========================================== -->
        <form wire:submit.prevent="save">
            <div class="row mb-3">
                <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <button type="button" wire:click="closeCreateForm" class="btn btn-outline-secondary btn-sm" title="Back to Requisitions">
                            <i class="fas fa-arrow-left me-1"></i>Back to Requisitions
                        </button>
                        <div class="ms-1">
                            <h4 class="mb-0 text-secondary d-flex align-items-center flex-wrap gap-2">
                                <span class="fas fa-file-invoice text-primary"></span>
                                <span>Create Stock Requisition</span>
                                <span class="badge bg-primary-subtle text-primary font-monospace fs-11">{{ $request_number }}</span>
                            </h4>
                            <div class="text-muted fs-11 mt-1">Submit internal store stock requisition for department operations</div>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" wire:click="closeCreateForm" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-times me-1"></i>Cancel
                        </button>
                        <button type="submit" wire:click="save" wire:loading.attr="disabled" class="btn btn-primary btn-sm">
                            <span wire:loading.remove wire:target="save">
                                <i class="fas fa-paper-plane me-1"></i>Submit Requisition
                            </span>
                            <span wire:loading wire:target="save">
                                <i class="fas fa-spinner fa-spin me-1"></i>Submitting...
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Error Alerts Banner -->
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                    <div class="d-flex align-items-center mb-1">
                        <i class="fas fa-exclamation-triangle me-2 fs-5"></i>
                        <strong class="fs-12">Please resolve the following issues before submitting:</strong>
                    </div>
                    <ul class="mb-0 ps-3 fs-11">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- 1. GENERAL INFORMATION -->
            <div class="card border border-200 shadow-sm mb-3">
                <div class="card-header bg-light py-2">
                    <h6 class="mb-0 text-700">
                        <i class="fas fa-info-circle me-2 text-primary"></i>Requisition Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label mb-1 fw-semi-bold">Request Number</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-200 text-muted"><i class="fas fa-hashtag"></i></span>
                                <input type="text" wire:model="request_number" class="form-control form-control-sm bg-200 font-monospace fw-bold" readonly>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label mb-1 fw-semi-bold">Request Date <span class="text-danger">*</span></label>
                            <input type="date" wire:model="request_date" class="form-control form-control-sm @error('request_date') is-invalid @enderror">
                            @error('request_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <!-- Searchable Department Selector -->
                        <div class="col-md-6 position-relative" x-data="{
                            open: false,
                            search: '',
                            activeIndex: -1,
                            selectedId: @entangle('department_id').live,
                            departments: {{ json_encode($departmentsJson) }},
                            init() {
                                this.updateSearchLabel();
                                this.$watch('selectedId', () => this.updateSearchLabel());
                            },
                            updateSearchLabel() {
                                const found = this.departments.find(d => d.id == this.selectedId);
                                if (found) {
                                    this.search = found.name + (found.code ? ' (' + found.code + ')' : '');
                                } else {
                                    this.search = '';
                                }
                            },
                            get filteredList() {
                                if (!this.search || this.isMatched()) {
                                    return this.departments;
                                }
                                const q = this.search.toLowerCase().trim();
                                return this.departments.filter(d => 
                                    (d.name && d.name.toLowerCase().includes(q)) || 
                                    (d.code && d.code.toLowerCase().includes(q))
                                );
                            },
                            isMatched() {
                                const found = this.departments.find(d => d.id == this.selectedId);
                                return found && this.search === (found.name + (found.code ? ' (' + found.code + ')' : ''));
                            },
                            selectDept(dept) {
                                this.selectedId = dept.id;
                                this.search = dept.name + (dept.code ? ' (' + dept.code + ')' : '');
                                this.open = false;
                                this.activeIndex = -1;
                                $wire.set('department_id', dept.id);
                            },
                            clearSelection() {
                                this.selectedId = '';
                                this.search = '';
                                this.open = true;
                                this.activeIndex = -1;
                                $wire.set('department_id', '');
                                this.$nextTick(() => this.$refs.deptSearchInput.focus());
                            },
                            handleKeydown(e) {
                                const list = this.filteredList;
                                if (!this.open) {
                                    if (e.key === 'ArrowDown' || e.key === 'ArrowUp' || e.key === 'Enter') {
                                        e.preventDefault();
                                        this.open = true;
                                        this.activeIndex = list.length > 0 ? 0 : -1;
                                    }
                                    return;
                                }
                                if (e.key === 'ArrowDown') {
                                    e.preventDefault();
                                    if (list.length > 0) {
                                        this.activeIndex = (this.activeIndex + 1) % list.length;
                                        this.scrollToActive();
                                    }
                                } else if (e.key === 'ArrowUp') {
                                    e.preventDefault();
                                    if (list.length > 0) {
                                        this.activeIndex = (this.activeIndex - 1 + list.length) % list.length;
                                        this.scrollToActive();
                                    }
                                } else if (e.key === 'Enter') {
                                    e.preventDefault();
                                    if (this.activeIndex >= 0 && this.activeIndex < list.length) {
                                        this.selectDept(list[this.activeIndex]);
                                    }
                                } else if (e.key === 'Escape') {
                                    this.open = false;
                                }
                            },
                            scrollToActive() {
                                this.$nextTick(() => {
                                    const listEl = this.$refs.deptList;
                                    if (listEl && listEl.children[this.activeIndex]) {
                                        listEl.children[this.activeIndex].scrollIntoView({ block: 'nearest' });
                                    }
                                });
                            }
                        }" @click.outside="open = false">
                            <label class="form-label mb-1 fw-semi-bold d-flex justify-content-between align-items-center">
                                <span>Department <span class="text-danger">*</span></span>
                                <span class="text-muted fs-11"><i class="fas fa-search me-1"></i>Searchable</span>
                            </label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white text-muted"><i class="fas fa-building"></i></span>
                                <input type="text"
                                       x-ref="deptSearchInput"
                                       x-model="search"
                                       @focus="open = true"
                                       @input="open = true; activeIndex = 0"
                                       @keydown="handleKeydown($event)"
                                       class="form-control form-control-sm @error('department_id') is-invalid @enderror"
                                       placeholder="Type to search department...">
                                <button x-show="selectedId"
                                        @click="clearSelection()"
                                        type="button"
                                        class="btn btn-outline-secondary btn-sm"
                                        title="Clear selection">
                                    <i class="fas fa-times"></i>
                                </button>
                                <button @click="open = !open"
                                        type="button"
                                        class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-chevron-down fs-11" :class="{ 'fa-rotate-180': open }"></i>
                                </button>
                            </div>
                            @error('department_id') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror

                            <!-- Department Dropdown list -->
                            <div x-show="open"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="opacity-0 transform scale-95"
                                 x-transition:enter-end="opacity-100 transform scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 transform scale-100"
                                 x-transition:leave-end="opacity-0 transform scale-95"
                                 class="position-absolute start-0 end-0 bg-white border border-200 rounded shadow-lg mt-1 py-1"
                                 style="max-height: 220px; overflow-y: auto; z-index: 1060; display: none;">
                                <div x-ref="deptList">
                                    <template x-for="(dept, index) in filteredList" :key="dept.id">
                                        <div @click="selectDept(dept)"
                                             @mouseenter="activeIndex = index"
                                             :class="{
                                                 'bg-primary text-white': activeIndex === index,
                                                 'bg-primary-subtle text-primary fw-bold': selectedId == dept.id && activeIndex !== index,
                                                 'text-dark': selectedId != dept.id && activeIndex !== index
                                             }"
                                             class="px-3 py-2 d-flex justify-content-between align-items-center"
                                             style="cursor: pointer; font-size: 0.85rem;">
                                            <div>
                                                <span x-text="dept.name" class="fw-semi-bold"></span>
                                                <template x-if="dept.code">
                                                    <span class="badge ms-1"
                                                          :class="activeIndex === index ? 'bg-white text-primary' : 'bg-secondary-subtle text-secondary'"
                                                          x-text="dept.code"></span>
                                                </template>
                                            </div>
                                            <i x-show="selectedId == dept.id" class="fas fa-check fs-11"></i>
                                        </div>
                                    </template>
                                    <div x-show="filteredList.length === 0" class="px-3 py-2 text-muted fs-11 text-center">
                                        No matching departments found
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. REQUISITION ITEMS TABLE -->
            <div class="card border border-200 shadow-sm mb-3">
                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 text-700">
                        <i class="fas fa-boxes me-2 text-primary"></i>Requested Items
                        <span class="badge bg-primary-subtle text-primary ms-2">{{ count($formItems) }} {{ count($formItems) === 1 ? 'Item' : 'Items' }}</span>
                    </h6>
                    <button type="button" wire:click="addItemRow" class="btn btn-outline-primary btn-sm">
                        <span class="fas fa-plus me-1"></span>Add Item Row
                    </button>
                </div>
                <div class="card-body p-0">
                    @error('formItems')
                        <div class="alert alert-danger m-3 mb-0 p-2 fs-11">
                            <i class="fas fa-exclamation-triangle me-1"></i>{{ $message }}
                        </div>
                    @enderror

                    <div class="table-responsive" style="min-height: 250px; overflow: visible;">
                        <table class="table table-sm table-hover mb-0 align-middle">
                            <thead class="bg-200 text-700 fs-11 text-uppercase">
                                <tr>
                                    <th class="ps-3" style="width: 50px;">#</th>
                                    <th>Inventory Item <span class="text-danger">*</span></th>
                                    <th style="width: 170px;">Unit of Measure</th>
                                    <th style="width: 200px;" class="text-end">Requested Qty <span class="text-danger">*</span></th>
                                    <th style="width: 80px;" class="text-center pe-3">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($formItems as $idx => $formItem)
                                    <tr wire:key="form-item-row-{{ $idx }}"
                                        x-data="{
                                            open: false,
                                            search: '',
                                            activeIndex: -1,
                                            selectedId: @entangle('formItems.'.$idx.'.item_id').live,
                                            selectedUnit: '',
                                            itemsList: {{ json_encode($itemsJson) }},
                                            init() {
                                                this.updateSearchLabel();
                                                this.$watch('selectedId', () => this.updateSearchLabel());
                                            },
                                            updateSearchLabel() {
                                                const found = this.itemsList.find(i => i.id == this.selectedId);
                                                if (found) {
                                                    this.search = found.name + (found.code ? ' (' + found.code + ')' : '');
                                                    this.selectedUnit = found.unit || '—';
                                                } else {
                                                    this.search = '';
                                                    this.selectedUnit = '—';
                                                }
                                            },
                                            get filteredItems() {
                                                if (!this.search || this.isMatched()) {
                                                    return this.itemsList;
                                                }
                                                const q = this.search.toLowerCase().trim();
                                                return this.itemsList.filter(i => 
                                                    (i.name && i.name.toLowerCase().includes(q)) || 
                                                    (i.code && i.code.toLowerCase().includes(q))
                                                );
                                            },
                                            isMatched() {
                                                const found = this.itemsList.find(i => i.id == this.selectedId);
                                                return found && this.search === (found.name + (found.code ? ' (' + found.code + ')' : ''));
                                            },
                                            selectItem(item) {
                                                this.selectedId = item.id;
                                                this.search = item.name + (item.code ? ' (' + item.code + ')' : '');
                                                this.selectedUnit = item.unit || '—';
                                                this.open = false;
                                                this.activeIndex = -1;
                                                $wire.set('formItems.{{ $idx }}.item_id', item.id);
                                                this.$nextTick(() => {
                                                    const qtyInput = document.getElementById('qty-input-{{ $idx }}');
                                                    if (qtyInput) {
                                                        qtyInput.focus();
                                                        qtyInput.select();
                                                    }
                                                });
                                            },
                                            clearItem() {
                                                this.selectedId = '';
                                                this.search = '';
                                                this.selectedUnit = '—';
                                                this.open = true;
                                                this.activeIndex = -1;
                                                $wire.set('formItems.{{ $idx }}.item_id', '');
                                                this.$nextTick(() => this.$refs.searchInput.focus());
                                            },
                                            handleKeydown(e) {
                                                const list = this.filteredItems;
                                                if (!this.open) {
                                                    if (e.key === 'ArrowDown' || e.key === 'ArrowUp' || e.key === 'Enter') {
                                                        e.preventDefault();
                                                        this.open = true;
                                                        this.activeIndex = list.length > 0 ? 0 : -1;
                                                    }
                                                    return;
                                                }
                                                if (e.key === 'ArrowDown') {
                                                    e.preventDefault();
                                                    if (list.length > 0) {
                                                        this.activeIndex = (this.activeIndex + 1) % list.length;
                                                        this.scrollToActive();
                                                    }
                                                } else if (e.key === 'ArrowUp') {
                                                    e.preventDefault();
                                                    if (list.length > 0) {
                                                        this.activeIndex = (this.activeIndex - 1 + list.length) % list.length;
                                                        this.scrollToActive();
                                                    }
                                                } else if (e.key === 'Enter') {
                                                    e.preventDefault();
                                                    if (this.activeIndex >= 0 && this.activeIndex < list.length) {
                                                        this.selectItem(list[this.activeIndex]);
                                                    }
                                                } else if (e.key === 'Escape') {
                                                    this.open = false;
                                                }
                                            },
                                            scrollToActive() {
                                                this.$nextTick(() => {
                                                    const listEl = this.$refs.dropdownList;
                                                    if (listEl && listEl.children[this.activeIndex]) {
                                                        listEl.children[this.activeIndex].scrollIntoView({ block: 'nearest' });
                                                    }
                                                });
                                            }
                                        }"
                                        @click.outside="open = false">
                                        <td class="ps-3 fw-bold text-muted">{{ $idx + 1 }}</td>
                                        
                                        <!-- Searchable Item Selector -->
                                        <td class="position-relative" style="position: relative; z-index: {{ 200 - $idx }};">
                                            <div class="input-group input-group-sm">
                                                <input type="text"
                                                       x-ref="searchInput"
                                                       x-model="search"
                                                       @focus="open = true"
                                                       @input="open = true; activeIndex = 0"
                                                       @keydown="handleKeydown($event)"
                                                       class="form-control form-control-sm @error('formItems.'.$idx.'.item_id') is-invalid @enderror"
                                                       placeholder="Type to search item name or item code...">
                                                <button x-show="selectedId"
                                                        @click="clearItem()"
                                                        type="button"
                                                        class="btn btn-outline-secondary btn-sm"
                                                        title="Clear item">
                                                    <i class="fas fa-times fs-11"></i>
                                                </button>
                                                <button @click="open = !open"
                                                        type="button"
                                                        class="btn btn-outline-secondary btn-sm">
                                                    <i class="fas fa-chevron-down fs-11" :class="{ 'fa-rotate-180': open }"></i>
                                                </button>
                                            </div>
                                            @error('formItems.'.$idx.'.item_id') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror

                                            <!-- Dropdown Results -->
                                            <div x-show="open"
                                                 x-transition:enter="transition ease-out duration-100"
                                                 x-transition:enter-start="opacity-0 transform scale-95"
                                                 x-transition:enter-end="opacity-100 transform scale-100"
                                                 x-transition:leave="transition ease-in duration-75"
                                                 x-transition:leave-start="opacity-100 transform scale-100"
                                                 x-transition:leave-end="opacity-0 transform scale-95"
                                                 class="position-absolute start-0 end-0 bg-white border border-200 rounded shadow-lg mt-1 py-1"
                                                 style="max-height: 250px; overflow-y: auto; z-index: 1070; display: none;">
                                                <div x-ref="dropdownList">
                                                    <template x-for="(item, index) in filteredItems" :key="item.id">
                                                        <div @click="selectItem(item)"
                                                             @mouseenter="activeIndex = index"
                                                             :class="{
                                                                 'bg-primary text-white': activeIndex === index,
                                                                 'bg-primary-subtle text-primary fw-bold': selectedId == item.id && activeIndex !== index,
                                                                 'text-dark': selectedId != item.id && activeIndex !== index
                                                             }"
                                                             class="px-3 py-2 cursor-pointer d-flex justify-content-between align-items-center"
                                                             style="cursor: pointer; font-size: 0.85rem;">
                                                            <div>
                                                                <span x-text="item.name" class="fw-semi-bold"></span>
                                                                <template x-if="item.code">
                                                                    <span class="badge ms-1"
                                                                          :class="activeIndex === index ? 'bg-white text-primary' : 'bg-secondary-subtle text-secondary'"
                                                                          x-text="item.code"></span>
                                                                </template>
                                                                <template x-if="item.unit">
                                                                    <span class="text-muted fs-11 ms-1"
                                                                          :class="activeIndex === index ? 'text-white' : ''"
                                                                          x-text="'(' + item.unit + ')'"></span>
                                                                </template>
                                                            </div>
                                                            <i x-show="selectedId == item.id" class="fas fa-check fs-11"></i>
                                                        </div>
                                                    </template>
                                                    <div x-show="filteredItems.length === 0" class="px-3 py-2 text-muted fs-11 text-center">
                                                        No matching items found
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Unit of Measure -->
                                        <td>
                                            <span class="badge bg-secondary-subtle text-secondary py-2 px-3 fs-11 font-monospace" x-text="selectedUnit || '—'"></span>
                                        </td>

                                        <!-- Requested Quantity -->
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="number"
                                                       id="qty-input-{{ $idx }}"
                                                       step="0.01"
                                                       wire:model="formItems.{{ $idx }}.requested_qty"
                                                       class="form-control form-control-sm text-end font-monospace fw-bold @error('formItems.'.$idx.'.requested_qty') is-invalid @enderror"
                                                       placeholder="0.00">
                                            </div>
                                            @error('formItems.'.$idx.'.requested_qty') <div class="text-danger fs-11 mt-1 text-end">{{ $message }}</div> @enderror
                                        </td>

                                        <!-- Remove Row Action -->
                                        <td class="text-center pe-3">
                                            <button type="button"
                                                    wire:click="removeItemRow({{ $idx }})"
                                                    class="btn btn-outline-danger btn-sm px-2 py-1"
                                                    title="Remove Item">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-light">
                                    <td colspan="5" class="py-2 ps-3">
                                        <button type="button" wire:click="addItemRow" class="btn btn-outline-primary btn-sm">
                                            <span class="fas fa-plus me-1"></span>Add Another Item Row
                                        </button>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 3. REMARKS & ACTIONS -->
            <div class="card border border-200 shadow-sm mb-4">
                <div class="card-header bg-light py-2">
                    <h6 class="mb-0 text-700">
                        <i class="fas fa-comment-alt me-2 text-primary"></i>Remarks & Purpose
                    </h6>
                </div>
                <div class="card-body">
                    <textarea wire:model="remarks" class="form-control form-control-sm" rows="3" placeholder="Enter any notes, purpose of requisition, or specific requirements..."></textarea>
                </div>
                <div class="card-footer bg-light d-flex justify-content-between align-items-center py-2">
                    <button type="button" wire:click="closeCreateForm" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>Back to Requisitions
                    </button>
                    <div class="d-flex gap-2">
                        <button type="button" wire:click="closeCreateForm" class="btn btn-secondary btn-sm">
                            Cancel
                        </button>
                        <button type="submit" wire:click="save" wire:loading.attr="disabled" class="btn btn-primary btn-sm">
                            <span wire:loading.remove wire:target="save">
                                <i class="fas fa-paper-plane me-1"></i>Submit Stock Requisition
                            </span>
                            <span wire:loading wire:target="save">
                                <i class="fas fa-spinner fa-spin me-1"></i>Submitting...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </form>

    @else
        <!-- ========================================== -->
        <!-- FULL PAGE: REQUISITIONS REGISTER (LIST)    -->
        <!-- ========================================== -->
        <div class="row mb-3">
            <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h4 class="mb-0 text-secondary">
                        <span class="fas fa-file-invoice me-2 text-primary"></span>Department Requisitions
                    </h4>
                    <div class="text-muted fs-11 mt-1">Manage and track internal stock requests across departments</div>
                </div>
                <button wire:click="openCreateForm" class="btn btn-primary btn-sm">
                    <span class="fas fa-plus me-1"></span>Create New Request
                </button>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12">
                <div class="card border border-200 shadow-sm">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h5 class="mb-0 text-700">Requisitions Register</h5>
                        <div class="d-flex gap-2 flex-wrap">
                            <div class="input-group input-group-sm" style="min-width: 240px;">
                                <span class="input-group-text bg-white text-muted"><i class="fas fa-search"></i></span>
                                <input type="search"
                                       wire:model.live.debounce.300ms="search"
                                       class="form-control form-control-sm"
                                       placeholder="Search req #, dept, item...">
                            </div>
                            <select wire:model.live="filterDepartment" class="form-select form-select-sm" style="max-width: 170px;">
                                <option value="">All Departments</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                            <select wire:model.live="filterStatus" class="form-select form-select-sm" style="max-width: 140px;">
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
                                                <div class="text-muted fs-11 text-truncate" style="max-width: 250px;" title="{{ $req->items->map(fn($i) => ($i->inventoryItem->name ?? 'Item') . ' (' . $i->requested_qty . ')')->join(', ') }}">
                                                    {{ $req->items->map(fn($i) => $i->inventoryItem->name ?? '')->filter()->join(', ') }}
                                                </div>
                                            </td>
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
                                            <td colspan="7" class="text-center py-4 text-muted">No requisitions found.</td>
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
