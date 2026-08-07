<div>
    <!-- Top Header Bar -->
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2 py-2">
            <div>
                <h5 class="mb-0 text-primary fw-bold">
                    <span class="fas fa-sliders-h me-2"></span>Master Data & Default Configurations
                </h5>
                <div class="text-muted fs-11 mt-1">
                    Manage operational categories, event types, departments, and units for your Marquee tenant.
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button wire:click="importGlobalDefaults" class="btn btn-falcon-default btn-sm text-nowrap" type="button" data-bs-toggle="tooltip" title="Import any missing default templates from Super Admin">
                    <span class="fas fa-file-import me-1 text-success"></span>Import Missing Global Defaults
                </button>
                <button wire:click="openCreateModal" class="btn btn-falcon-primary btn-sm text-nowrap" type="button">
                    <span class="fas fa-plus me-1" data-fa-transform="shrink-3"></span> Add Custom Record
                </button>
            </div>
        </div>
    </div>

    <!-- Category Tabs Navigation -->
    <div class="card mb-3">
        <div class="card-header bg-light border-bottom py-2">
            <ul class="nav nav-tabs card-header-tabs fs-11">
                <li class="nav-item">
                    <button wire:click="setCategory('event_types')" class="nav-link {{ $activeCategory === 'event_types' ? 'active fw-bold text-primary' : 'text-600' }}" type="button">
                        <span class="fas fa-glass-cheers me-1"></span>Event Types
                    </button>
                </li>
                <li class="nav-item">
                    <button wire:click="setCategory('menu_categories')" class="nav-link {{ $activeCategory === 'menu_categories' ? 'active fw-bold text-primary' : 'text-600' }}" type="button">
                        <span class="fas fa-utensils me-1"></span>Menu Categories
                    </button>
                </li>
                <li class="nav-item">
                    <button wire:click="setCategory('inventory_categories')" class="nav-link {{ $activeCategory === 'inventory_categories' ? 'active fw-bold text-primary' : 'text-600' }}" type="button">
                        <span class="fas fa-boxes me-1"></span>Inventory Categories
                    </button>
                </li>
                <li class="nav-item">
                    <button wire:click="setCategory('units')" class="nav-link {{ $activeCategory === 'units' ? 'active fw-bold text-primary' : 'text-600' }}" type="button">
                        <span class="fas fa-ruler me-1"></span>Units of Measurement
                    </button>
                </li>
                <li class="nav-item">
                    <button wire:click="setCategory('expense_categories')" class="nav-link {{ $activeCategory === 'expense_categories' ? 'active fw-bold text-primary' : 'text-600' }}" type="button">
                        <span class="fas fa-receipt me-1"></span>Expense Categories
                    </button>
                </li>
                <li class="nav-item">
                    <button wire:click="setCategory('departments')" class="nav-link {{ $activeCategory === 'departments' ? 'active fw-bold text-primary' : 'text-600' }}" type="button">
                        <span class="fas fa-sitemap me-1"></span>Departments
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body bg-light border-bottom py-2">
            <div class="row g-2">
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <input wire:model.live.debounce.300ms="search" class="form-control" type="search" placeholder="Search Master Records..." />
                        <span class="input-group-text"><span class="fas fa-search"></span></span>
                    </div>
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
                            <th>Code / Identifier</th>
                            <th>Description</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            @php
                                $name = $item->name ?? $item->event_type_name ?? $item->category_name ?? '—';
                                $code = $item->code ?? $item->event_type_code ?? $item->category_code ?? $item->short_code ?? '—';
                                $status = $item->status ?? 'active';
                            @endphp
                            <tr>
                                <td class="px-3 fw-bold text-900">{{ $name }}</td>
                                <td><span class="badge badge-subtle-secondary font-monospace">{{ $code }}</span></td>
                                <td class="text-600 fs-11">{{ $item->description ?? 'No description provided' }}</td>
                                <td class="text-center">
                                    <span class="badge bg-success-subtle text-success rounded-pill px-2 py-1 fs-12">{{ ucfirst($status) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <span class="fas fa-folder-open fa-3x mb-2 d-block text-400"></span>
                                    No master records configured for <strong>{{ str_replace('_', ' ', strtoupper($activeCategory)) }}</strong>.
                                    <div class="mt-2">
                                        <button wire:click="importGlobalDefaults" class="btn btn-falcon-success btn-xs" type="button">
                                            <span class="fas fa-file-import me-1"></span>Click here to import default templates
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if(method_exists($items, 'hasPages') && $items->hasPages())
            <div class="card-footer d-flex align-items-center justify-content-center bg-light">
                {{ $items->links() }}
            </div>
        @endif
    </div>

    <!-- Create Custom Record Modal -->
    @if($isModalOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title text-white">
                            <span class="fas fa-plus me-2"></span>Add Custom Master Record
                        </h5>
                        <button wire:click="closeModal" type="button" class="btn-close btn-close-white" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-start">
                        <div class="mb-3">
                            <label class="form-label fw-bold fs-11 text-700">Category Type</label>
                            <input class="form-control form-control-sm bg-light" type="text" value="{{ str_replace('_', ' ', strtoupper($activeCategory)) }}" readonly />
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold fs-11 text-700">Name <span class="text-danger">*</span></label>
                            <input wire:model="name" class="form-control form-control-sm @error('name') is-invalid @enderror" type="text" placeholder="e.g. Custom Event / Custom Unit" />
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold fs-11 text-700">Code / Short Code</label>
                            <input wire:model="code" class="form-control form-control-sm font-monospace" type="text" placeholder="e.g. CODE-01" />
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold fs-11 text-700">Description</label>
                            <textarea wire:model="description" class="form-control form-control-sm" rows="3" placeholder="Brief description..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button wire:click="closeModal" type="button" class="btn btn-falcon-default btn-sm">Cancel</button>
                        <button wire:click="saveCustomRecord" type="button" class="btn btn-primary btn-sm">
                            <span class="fas fa-save me-1"></span>Save Record
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
