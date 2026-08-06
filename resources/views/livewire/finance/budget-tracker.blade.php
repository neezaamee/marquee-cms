<div>
    <!-- Filtration & Controls Block -->
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><span class="fas fa-chart-pie me-2 text-primary"></span>Expense Budgets Tracker</h5>
            <div>
                <button wire:click="openCreateForm" class="btn btn-falcon-primary btn-sm" type="button">
                    <span class="fas fa-plus me-1"></span> Set New Budget Limit
                </button>
            </div>
        </div>
        <div class="card-body bg-light border-bottom">
            <div class="row g-2">
                <div class="col-md-4">
                    <select wire:model.live="filterBranch" class="form-select form-select-sm">
                        <option value="">All Branches</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <select wire:model.live="filterCategory" class="form-select form-select-sm">
                        <option value="">All Categories</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <input wire:model.live="filterYear" type="number" class="form-control form-control-sm" placeholder="Year (e.g. 2026)">
                </div>
            </div>
        </div>

        <!-- Budget Table Listings -->
        <div class="card-body p-0">
            @if(session('success'))
                <div class="alert alert-success border-2 d-flex align-items-center m-3" role="alert">
                    <div class="bg-success me-3 icon-item"><span class="fas fa-check-circle text-white fs-8"></span></div>
                    <p class="mb-0 flex-grow-1 text-success-800">{{ session('success') }}</p>
                    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="table-responsive scrollbar">
                <table class="table table-sm table-striped fs-10 mb-0 align-middle table-hover">
                    <thead class="bg-200 text-900">
                        <tr>
                            <th class="px-3" style="width: 20%;">Category</th>
                            <th style="width: 15%;">Branch / Dept</th>
                            <th style="width: 10%;">Period</th>
                            <th class="text-end" style="width: 12%;">Allocated</th>
                            <th class="text-end" style="width: 12%;">Consumed</th>
                            <th class="text-end" style="width: 12%;">Remaining</th>
                            <th style="width: 14%;">Consumption Ratio</th>
                            <th class="text-end px-3" style="width: 5%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($budgets as $bg)
                            @php
                                $percent = $bg->allocated_amount > 0 ? ($bg->consumed_amount / $bg->allocated_amount) * 100 : 0;
                                $pbColor = 'bg-success';
                                if ($percent >= 90) {
                                    $pbColor = 'bg-danger';
                                } elseif ($percent >= 70) {
                                    $pbColor = 'bg-warning';
                                }
                            @endphp
                            <tr>
                                <td class="px-3 fw-semi-bold">{{ $bg->category->name }}</td>
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
                                <td class="text-end font-monospace fw-bold">{{ number_format($bg->allocated_amount, 2) }} PKR</td>
                                <td class="text-end font-monospace text-secondary fw-bold">{{ number_format($bg->consumed_amount, 2) }} PKR</td>
                                <td class="text-end font-monospace fw-bold">
                                    <span class="{{ $bg->remaining_amount < 0 ? 'text-danger' : 'text-success' }}">
                                        {{ number_format($bg->remaining_amount, 2) }} PKR
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress me-2 flex-grow-1" style="height: 6px;">
                                            <div class="progress-bar {{ $pbColor }}" role="progressbar" style="width: {{ min($percent, 100) }}%;" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <span class="fw-semi-bold">{{ number_format($percent, 0) }}%</span>
                                    </div>
                                </td>
                                <td class="text-end px-3">
                                    <div class="d-flex justify-content-end gap-2">
                                        <button wire:click="edit({{ $bg->id }})" class="btn btn-link p-0 text-primary" title="Edit">
                                            <span class="fas fa-edit"></span>
                                        </button>
                                        <button onclick="confirm('Are you sure you want to delete this budget?') || event.stopImmediatePropagation()" wire:click="delete({{ $bg->id }})" class="btn btn-link p-0 text-danger" title="Delete">
                                            <span class="fas fa-trash-alt"></span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <span class="fas fa-chart-pie fa-2x mb-2 d-block"></span>
                                    No budgets defined for the selection.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="card-footer d-flex justify-content-end">
                {{ $budgets->links() }}
            </div>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    @if($isFormOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0, 0, 0, 0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0">
                    <form wire:submit.prevent="save">
                        <div class="modal-header bg-light">
                            <h5 class="modal-title"><span class="fas fa-chart-line me-2"></span>{{ $editId ? 'Edit Budget Limits' : 'Set Budget limit' }}</h5>
                            <button type="button" wire:click="closeForm" class="btn-close" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label" for="category">Expense Category</label>
                                <select wire:model="category_id" class="form-select form-select-sm @error('category_id') is-invalid @enderror" id="category">
                                    <option value="">Select category</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                                @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="branch">Branch Scoping (Optional)</label>
                                <select wire:model="branch_id" class="form-select form-select-sm @error('branch_id') is-invalid @enderror" id="branch">
                                    <option value="">Company-Wide (All Branches)</option>
                                    @foreach($branches as $br)
                                        <option value="{{ $br->id }}">{{ $br->name }}</option>
                                    @endforeach
                                </select>
                                @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="department">Department (Optional)</label>
                                <select wire:model="department" class="form-select form-select-sm @error('department') is-invalid @enderror" id="department">
                                    <option value="">All Departments</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept }}">{{ $dept }}</option>
                                    @endforeach
                                </select>
                                @error('department') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="row g-3">
                                <div class="col-6 mb-3">
                                    <label class="form-label" for="budget_year">Financial Year</label>
                                    <input wire:model="year" type="number" class="form-control form-control-sm @error('year') is-invalid @enderror" id="budget_year" placeholder="e.g. 2026">
                                    @error('year') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label" for="budget_month">Month (Optional)</label>
                                    <select wire:model="month" class="form-select form-select-sm @error('month') is-invalid @enderror" id="budget_month">
                                        <option value="">Annual (Full Year)</option>
                                        @for($m = 1; $m <= 12; $m++)
                                            <option value="{{ $m }}">{{ date('F', mktime(0, 0, 0, $m, 10)) }}</option>
                                        @endfor
                                    </select>
                                    @error('month') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="alloc_amt">Allocated Budget Amount (PKR)</label>
                                <input wire:model="allocated_amount" type="number" step="0.01" class="form-control form-control-sm @error('allocated_amount') is-invalid @enderror" id="alloc_amt" placeholder="e.g. 500000.00">
                                @error('allocated_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" wire:click="closeForm" class="btn btn-falcon-default btn-sm">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm">Save Budget</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
