<div>
    <!-- KPI Row -->
    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-md-3">
            <div class="card overflow-hidden shadow-none border">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted text-uppercase fs-11 mb-1">Allocated Budget</h6>
                            <h4 class="fw-bold font-monospace text-primary mb-0">{{ number_format($totalAllocated, 2) }}</h4>
                            <span class="fs-11 text-muted">{{ $filterYear ?: 'All Time' }} limits (PKR)</span>
                        </div>
                        <div class="icon-item bg-subtle-primary text-primary rounded-circle"><span class="fas fa-shield-alt"></span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-md-3">
            <div class="card overflow-hidden shadow-none border">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted text-uppercase fs-11 mb-1">Consumed Spend</h6>
                            <h4 class="fw-bold font-monospace text-secondary mb-0">{{ number_format($totalConsumed, 2) }}</h4>
                            <span class="fs-11 text-muted">Actual GL expenditures (PKR)</span>
                        </div>
                        <div class="icon-item bg-subtle-secondary text-secondary rounded-circle"><span class="fas fa-money-check-alt"></span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-md-3">
            <div class="card overflow-hidden shadow-none border">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted text-uppercase fs-11 mb-1">Remaining Balance</h6>
                            <h4 class="fw-bold font-monospace {{ $totalRemaining < 0 ? 'text-danger' : 'text-success' }} mb-0">{{ number_format($totalRemaining, 2) }}</h4>
                            <span class="fs-11 text-muted">Net available margin (PKR)</span>
                        </div>
                        <div class="icon-item bg-subtle-{{ $totalRemaining < 0 ? 'danger' : 'success' }} text-{{ $totalRemaining < 0 ? 'danger' : 'success' }} rounded-circle"><span class="fas fa-balance-scale"></span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-md-3">
            <div class="card overflow-hidden shadow-none border">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted text-uppercase fs-11 mb-1">Exceeded Limits</h6>
                            <h4 class="fw-bold font-monospace {{ $exceededCount > 0 ? 'text-danger' : 'text-success' }} mb-0">{{ $exceededCount }}</h4>
                            <span class="fs-11 text-muted">Over-budget categories</span>
                        </div>
                        <div class="icon-item bg-subtle-{{ $exceededCount > 0 ? 'danger' : 'success' }} text-{{ $exceededCount > 0 ? 'danger' : 'success' }} rounded-circle"><span class="fas fa-exclamation-circle"></span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtration & Controls Block -->
    <div class="card mb-3 shadow-none border">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2 py-2">
            <h6 class="mb-0"><span class="fas fa-chart-pie me-2 text-primary"></span>Category Expense Budgets</h6>
            <div class="d-flex gap-2">
                <button wire:click="syncActuals" wire:loading.attr="disabled" class="btn btn-falcon-default btn-sm" type="button" title="Synchronize actual spent from posted expenses">
                    <span wire:loading.remove wire:target="syncActuals"><span class="fas fa-sync-alt me-1"></span>Sync Actuals</span>
                    <span wire:loading wire:target="syncActuals"><span class="spinner-border spinner-border-sm me-1"></span>Syncing...</span>
                </button>
                <button wire:click="openCreateForm" class="btn btn-falcon-primary btn-sm" type="button">
                    <span class="fas fa-plus me-1"></span> Set Budget Limit
                </button>
            </div>
        </div>
        <div class="card-body bg-light border-bottom py-2">
            <div class="row g-2">
                <div class="col-md-4">
                    <select wire:model.live="filterBranch" class="form-select form-select-sm">
                        <option value="">All Branches</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <select wire:model.live="filterCategory" class="form-select form-select-sm">
                        <option value="">All Categories (26 Marquee Types)</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <input wire:model.live="filterYear" type="number" class="form-control form-control-sm" placeholder="Year (e.g. 2026)">
                </div>
            </div>
        </div>

        <!-- Budget Table Listings -->
        <div class="card-body p-0">
            @if(session('success'))
                <div class="alert alert-success border-2 d-flex align-items-center m-3 py-2" role="alert">
                    <div class="bg-success me-2 icon-item icon-item-sm rounded-circle text-white d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;"><span class="fas fa-check fs-11"></span></div>
                    <p class="mb-0 flex-grow-1 fs-11 text-success-800">{{ session('success') }}</p>
                    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="table-responsive scrollbar">
                <table class="table table-sm table-striped fs-10 mb-0 align-middle table-hover">
                    <thead class="bg-200 text-900">
                        <tr>
                            <th class="px-3" style="width: 22%;">Expense Category</th>
                            <th style="width: 15%;">Scope / Branch</th>
                            <th style="width: 12%;">Period</th>
                            <th class="text-end" style="width: 12%;">Allocated (PKR)</th>
                            <th class="text-end" style="width: 12%;">Consumed (PKR)</th>
                            <th class="text-end" style="width: 12%;">Remaining (PKR)</th>
                            <th style="width: 10%;">Utilization</th>
                            <th class="text-end px-3" style="width: 5%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($budgets as $bg)
                            @php
                                $percent = $bg->allocated_amount > 0 ? ($bg->consumed_amount / $bg->allocated_amount) * 100 : 0;
                                $pbColor = 'bg-success';
                                if ($percent >= 100) {
                                    $pbColor = 'bg-danger';
                                } elseif ($percent >= 80) {
                                    $pbColor = 'bg-warning';
                                }
                            @endphp
                            <tr>
                                <td class="px-3 fw-semi-bold">{{ $bg->category->name ?? '—' }}</td>
                                <td>
                                    <div class="fw-semi-bold">{{ $bg->branch->name ?? 'Company-Wide' }}</div>
                                    @if($bg->department)
                                        <div class="text-muted fs-11">{{ $bg->department }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($bg->month)
                                        <span class="badge badge-subtle-primary">{{ date('F', mktime(0, 0, 0, $bg->month, 10)) }} {{ $bg->year }}</span>
                                    @else
                                        <span class="badge badge-subtle-info">Annual {{ $bg->year }}</span>
                                    @endif
                                </td>
                                <td class="text-end font-monospace fw-bold">{{ number_format($bg->allocated_amount, 2) }}</td>
                                <td class="text-end font-monospace text-secondary fw-bold">{{ number_format($bg->consumed_amount, 2) }}</td>
                                <td class="text-end font-monospace fw-bold">
                                    <span class="{{ $bg->remaining_amount < 0 ? 'text-danger' : 'text-success' }}">
                                        {{ number_format($bg->remaining_amount, 2) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress me-2 flex-grow-1" style="height: 6px;">
                                            <div class="progress-bar {{ $pbColor }}" role="progressbar" style="width: {{ min($percent, 100) }}%;" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <span class="fw-semi-bold fs-11 {{ $percent >= 100 ? 'text-danger fw-bold' : '' }}">{{ number_format($percent, 0) }}%</span>
                                    </div>
                                </td>
                                <td class="text-end px-3">
                                    <div class="d-flex justify-content-end gap-2 align-items-center">
                                        <button wire:click="edit({{ $bg->id }})" class="btn btn-sm btn-link p-0 text-primary" title="Edit Budget Limit">
                                            <span class="fas fa-edit"></span>
                                        </button>
                                        <button onclick="confirm('Are you sure you want to delete this budget limit?') || event.stopImmediatePropagation()" wire:click="delete({{ $bg->id }})" class="btn btn-sm btn-link p-0 text-danger" title="Delete">
                                            <span class="fas fa-trash-alt"></span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <span class="fas fa-chart-pie fa-2x mb-2 d-block"></span>
                                    No budget limits defined for the current selection.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="card-footer d-flex justify-content-end py-2">
                {{ $budgets->links() }}
            </div>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    @if($isFormOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0, 0, 0, 0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <form wire:submit.prevent="save">
                        <div class="modal-header bg-light">
                            <h5 class="modal-title fs-10"><span class="fas fa-chart-pie me-2 text-primary"></span>{{ $editId ? 'Edit Budget Limit' : 'Set Category Budget Limit' }}</h5>
                            <button type="button" wire:click="closeForm" class="btn-close" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label" for="b_cat">Expense Category <span class="text-danger">*</span></label>
                                <select wire:model="category_id" class="form-select form-select-sm @error('category_id') is-invalid @enderror" id="b_cat">
                                    <option value="">Select category</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                                @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="b_br">Branch Scope</label>
                                    <select wire:model="branch_id" class="form-select form-select-sm @error('branch_id') is-invalid @enderror" id="b_br">
                                        <option value="">Company-Wide (All Branches)</option>
                                        @foreach($branches as $br)
                                            <option value="{{ $br->id }}">{{ $br->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="b_dept">Department (Optional)</label>
                                    <select wire:model="department" class="form-select form-select-sm @error('department') is-invalid @enderror" id="b_dept">
                                        <option value="">All Departments</option>
                                        @foreach($departments as $dept)
                                            <option value="{{ $dept }}">{{ $dept }}</option>
                                        @endforeach
                                    </select>
                                    @error('department') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="b_yr">Budget Year <span class="text-danger">*</span></label>
                                    <input wire:model="year" type="number" class="form-control form-control-sm @error('year') is-invalid @enderror" id="b_yr">
                                    @error('year') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="b_mo">Month (Optional)</label>
                                    <select wire:model="month" class="form-select form-select-sm @error('month') is-invalid @enderror" id="b_mo">
                                        <option value="">Full Year (Annual)</option>
                                        @for($m = 1; $m <= 12; $m++)
                                            <option value="{{ $m }}">{{ date('F', mktime(0, 0, 0, $m, 10)) }}</option>
                                        @endfor
                                    </select>
                                    @error('month') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="b_amt">Allocated Budget Limit (PKR) <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text font-monospace">PKR</span>
                                    <input wire:model="allocated_amount" type="number" step="0.01" class="form-control form-control-sm @error('allocated_amount') is-invalid @enderror" id="b_amt" placeholder="e.g. 100000.00">
                                </div>
                                @error('allocated_amount') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="modal-footer bg-light py-2">
                            <button type="button" wire:click="closeForm" class="btn btn-falcon-default btn-sm">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm px-3">
                                <span class="fas fa-save me-1"></span>Save Budget Limit
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
