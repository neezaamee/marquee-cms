<div>
    <!-- Top Header Bar -->
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2 py-2">
            <div>
                <h5 class="mb-0 text-primary fw-bold">
                    <span class="fas fa-globe me-2"></span>Global Default Data Management System
                </h5>
                <div class="text-muted fs-11 mt-1">
                    Manage standard master templates available globally to all Marquee SaaS tenants.
                </div>
            </div>
            <div>
                <button wire:click="openCreateModal" class="btn btn-falcon-primary btn-sm text-nowrap" type="button">
                    <span class="fas fa-plus me-1" data-fa-transform="shrink-3"></span> Add Global Master
                </button>
            </div>
        </div>
    </div>

    <!-- Category Metrics Grid -->
    <div class="row g-2 mb-3">
        <div class="col-6 col-sm-4 col-md">
            <div class="card border-200">
                <div class="card-body p-2 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-500 fw-bold fs-12 text-uppercase">Total Masters</div>
                        <h4 class="mb-0 font-monospace text-primary fw-bold">{{ number_format($metrics['total']) }}</h4>
                    </div>
                    <div class="icon-item bg-primary-subtle text-primary rounded-3"><span class="fas fa-database fs-9"></span></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md">
            <div class="card border-200">
                <div class="card-body p-2 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-500 fw-bold fs-12 text-uppercase">Active Templates</div>
                        <h4 class="mb-0 font-monospace text-success fw-bold">{{ number_format($metrics['active']) }}</h4>
                    </div>
                    <div class="icon-item bg-success-subtle text-success rounded-3"><span class="fas fa-check-circle fs-9"></span></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md">
            <div class="card border-200">
                <div class="card-body p-2 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-500 fw-bold fs-12 text-uppercase">Event Types</div>
                        <h4 class="mb-0 font-monospace text-info fw-bold">{{ number_format($metrics['event_type']) }}</h4>
                    </div>
                    <div class="icon-item bg-info-subtle text-info rounded-3"><span class="fas fa-glass-cheers fs-9"></span></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md">
            <div class="card border-200">
                <div class="card-body p-2 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-500 fw-bold fs-12 text-uppercase">Menu Categories</div>
                        <h4 class="mb-0 font-monospace text-warning fw-bold">{{ number_format($metrics['menu_category']) }}</h4>
                    </div>
                    <div class="icon-item bg-warning-subtle text-warning rounded-3"><span class="fas fa-utensils fs-9"></span></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md">
            <div class="card border-200">
                <div class="card-body p-2 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-500 fw-bold fs-12 text-uppercase">Units</div>
                        <h4 class="mb-0 font-monospace text-dark fw-bold">{{ number_format($metrics['inventory_unit']) }}</h4>
                    </div>
                    <div class="icon-item bg-secondary-subtle text-dark rounded-3"><span class="fas fa-ruler fs-9"></span></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Tabs Navigation -->
    <div class="card mb-3">
        <div class="card-header bg-light border-bottom py-2">
            <ul class="nav nav-tabs card-header-tabs fs-11" id="categoryTabs">
                <li class="nav-item">
                    <button wire:click="setCategory('event_type')" class="nav-link {{ $activeCategory === 'event_type' ? 'active fw-bold text-primary' : 'text-600' }}" type="button">
                        <span class="fas fa-glass-cheers me-1"></span>Event Types ({{ $metrics['event_type'] }})
                    </button>
                </li>
                <li class="nav-item">
                    <button wire:click="setCategory('menu_category')" class="nav-link {{ $activeCategory === 'menu_category' ? 'active fw-bold text-primary' : 'text-600' }}" type="button">
                        <span class="fas fa-utensils me-1"></span>Menu Categories ({{ $metrics['menu_category'] }})
                    </button>
                </li>
                <li class="nav-item">
                    <button wire:click="setCategory('inventory_category')" class="nav-link {{ $activeCategory === 'inventory_category' ? 'active fw-bold text-primary' : 'text-600' }}" type="button">
                        <span class="fas fa-boxes me-1"></span>Inventory Cats ({{ $metrics['inventory_category'] }})
                    </button>
                </li>
                <li class="nav-item">
                    <button wire:click="setCategory('inventory_unit')" class="nav-link {{ $activeCategory === 'inventory_unit' ? 'active fw-bold text-primary' : 'text-600' }}" type="button">
                        <span class="fas fa-ruler me-1"></span>Units ({{ $metrics['inventory_unit'] }})
                    </button>
                </li>
                <li class="nav-item">
                    <button wire:click="setCategory('expense_category')" class="nav-link {{ $activeCategory === 'expense_category' ? 'active fw-bold text-primary' : 'text-600' }}" type="button">
                        <span class="fas fa-receipt me-1"></span>Expenses ({{ $metrics['expense_category'] }})
                    </button>
                </li>
                <li class="nav-item">
                    <button wire:click="setCategory('department_type')" class="nav-link {{ $activeCategory === 'department_type' ? 'active fw-bold text-primary' : 'text-600' }}" type="button">
                        <span class="fas fa-sitemap me-1"></span>Departments ({{ $metrics['department_type'] }})
                    </button>
                </li>
                <li class="nav-item">
                    <button wire:click="setCategory('vendor_type')" class="nav-link {{ $activeCategory === 'vendor_type' ? 'active fw-bold text-primary' : 'text-600' }}" type="button">
                        <span class="fas fa-truck me-1"></span>Vendors ({{ $metrics['vendor_type'] }})
                    </button>
                </li>
                <li class="nav-item">
                    <button wire:click="setCategory('customer_type')" class="nav-link {{ $activeCategory === 'customer_type' ? 'active fw-bold text-primary' : 'text-600' }}" type="button">
                        <span class="fas fa-users me-1"></span>Customer Types ({{ $metrics['customer_type'] }})
                    </button>
                </li>
                <li class="nav-item">
                    <button wire:click="setCategory('payment_method')" class="nav-link {{ $activeCategory === 'payment_method' ? 'active fw-bold text-primary' : 'text-600' }}" type="button">
                        <span class="fas fa-wallet me-1"></span>Payments ({{ $metrics['payment_method'] }})
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body bg-light border-bottom py-2">
            <div class="row g-2">
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <input wire:model.live.debounce.300ms="search" class="form-control" type="search" placeholder="Search Master Templates..." />
                        <span class="input-group-text"><span class="fas fa-search"></span></span>
                    </div>
                </div>
                <div class="col-md-3">
                    <select wire:model.live="filterStatus" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="1">Active Only</option>
                        <option value="0">Inactive Only</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            @if(session('success'))
                <div class="alert alert-success border-2 d-flex align-items-center m-3" role="alert">
                    <div class="bg-success me-3 icon-item"><span class="fas fa-check-circle text-white fs-8"></span></div>
                    <p class="mb-0 flex-grow-1 text-success-800">{{ session('success') }}</p>
                    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger border-2 d-flex align-items-center m-3" role="alert">
                    <div class="bg-danger me-3 icon-item"><span class="fas fa-times-circle text-white fs-8"></span></div>
                    <p class="mb-0 flex-grow-1 text-danger-800">{{ session('error') }}</p>
                    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="table-responsive scrollbar">
                <table class="table table-sm table-striped fs-10 mb-0 align-middle table-hover">
                    <thead class="bg-200 text-900">
                        <tr>
                            <th class="px-3">Master Name</th>
                            <th>Code / Key</th>
                            <th>Description</th>
                            <th>Attributes</th>
                            <th class="text-center">Status</th>
                            <th class="text-end px-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($masters as $m)
                            <tr>
                                <td class="px-3 fw-bold text-900">
                                    {{ $m->name }}
                                </td>
                                <td>
                                    <span class="badge badge-subtle-secondary font-monospace">{{ $m->code ?? '—' }}</span>
                                </td>
                                <td class="text-600 fs-11">
                                    {{ $m->description ?? 'No description provided' }}
                                </td>
                                <td>
                                    @php $extra = $m->extra_attributes ?? []; @endphp
                                    @if(isset($extra['short_code']))
                                        <span class="badge bg-secondary">Short: {{ $extra['short_code'] }}</span>
                                    @endif
                                    @if(isset($extra['color']))
                                        <span class="badge" style="background-color: {{ $extra['color'] }}; color: #fff;">{{ $extra['color'] }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <button wire:click="toggleStatus({{ $m->id }})" class="btn p-0 border-0" type="button">
                                        @if($m->is_active)
                                            <span class="badge bg-success-subtle text-success rounded-pill px-2 py-1 fs-12">Active</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2 py-1 fs-12">Inactive</span>
                                        @endif
                                    </button>
                                </td>
                                <td class="text-end px-3">
                                    <div class="btn-group btn-group-sm">
                                        <button wire:click="editMaster({{ $m->id }})" class="btn btn-falcon-default btn-xs" type="button" title="Edit Master">
                                            <span class="fas fa-edit text-primary"></span> Edit
                                        </button>
                                        <button wire:click="deleteMaster({{ $m->id }})" wire:confirm="Are you sure you want to delete this global master template?" class="btn btn-falcon-default btn-xs text-danger" type="button" title="Delete Master">
                                            <span class="fas fa-trash-alt"></span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <span class="fas fa-database fa-3x mb-2 d-block text-400"></span>
                                    No global default records found for category: <strong>{{ str_replace('_', ' ', strtoupper($activeCategory)) }}</strong>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($masters->hasPages())
            <div class="card-footer d-flex align-items-center justify-content-center bg-light">
                {{ $masters->links() }}
            </div>
        @endif
    </div>

    <!-- Create / Edit Modal -->
    @if($isModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title text-white">
                            <span class="fas fa-globe me-2"></span>{{ $isEditMode ? 'Edit Global Master Template' : 'Create Global Master Template' }}
                        </h5>
                        <button wire:click="closeModal" type="button" class="btn-close btn-close-white" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-start">
                        <div class="mb-3">
                            <label class="form-label fw-bold fs-11 text-700">Category Type</label>
                            <input class="form-control form-control-sm bg-light" type="text" value="{{ str_replace('_', ' ', strtoupper($activeCategory)) }}" readonly />
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold fs-11 text-700">Master Name <span class="text-danger">*</span></label>
                            <input wire:model="name" class="form-control form-control-sm @error('name') is-invalid @enderror" type="text" placeholder="e.g. Wedding (Baraat) / Kilogram / BBQ Kitchen" />
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold fs-11 text-700">Code / Identifier</label>
                            <input wire:model="code" class="form-control form-control-sm font-monospace" type="text" placeholder="e.g. ET-BARAAT or KG" />
                        </div>
                        @if($activeCategory === 'inventory_unit')
                            <div class="mb-3">
                                <label class="form-label fw-bold fs-11 text-700">Unit Short Code</label>
                                <input wire:model="short_code" class="form-control form-control-sm" type="text" placeholder="e.g. Kg, Pcs, Ltr" />
                            </div>
                        @endif
                        @if($activeCategory === 'event_type')
                            <div class="mb-3">
                                <label class="form-label fw-bold fs-11 text-700">Color Code</label>
                                <input wire:model="color_code" class="form-control form-control-color w-100" type="color" title="Choose event type badge color" />
                            </div>
                        @endif
                        <div class="mb-3">
                            <label class="form-label fw-bold fs-11 text-700">Description</label>
                            <textarea wire:model="description" class="form-control form-control-sm" rows="3" placeholder="Brief description of this default template..."></textarea>
                        </div>
                        <div class="form-check form-switch">
                            <input wire:model="is_active" class="form-check-input" type="checkbox" id="isActiveCheck" />
                            <label class="form-check-label fw-bold fs-11 text-700" for="isActiveCheck">Active Template (Available for new tenant seeding)</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button wire:click="closeModal" type="button" class="btn btn-falcon-default btn-sm">Cancel</button>
                        <button wire:click="saveMaster" type="button" class="btn btn-primary btn-sm">
                            <span class="fas fa-save me-1"></span>Save Template
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
