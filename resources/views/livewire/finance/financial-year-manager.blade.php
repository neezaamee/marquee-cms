<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><span class="fas fa-calendar-alt me-2 text-primary"></span>Financial Years</h5>
            <div class="d-flex align-items-center gap-2">
                @if(!$isFormOpen)
                    <button wire:click="openCreateForm" class="btn btn-falcon-primary btn-sm text-nowrap" type="button">
                        <span class="fas fa-plus me-1" data-fa-transform="shrink-3"></span> Add Financial Year
                    </button>
                @else
                    <button wire:click="closeForm" class="btn btn-falcon-default btn-sm text-nowrap" type="button">
                        <span class="fas fa-arrow-left me-1"></span> Back to List
                    </button>
                @endif
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

            @if($isFormOpen)
                <div class="p-4 bg-light border-bottom">
                    <h6 class="mb-3">{{ $editId ? 'Edit' : 'Create' }} Financial Year</h6>
                    <form wire:submit.prevent="save">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="fy_name">Financial Year Name</label>
                                <input wire:model="name" type="text" class="form-control form-control-sm @error('name') is-invalid @enderror" id="fy_name" placeholder="e.g. FY 2026-27">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="fy_start">Start Date</label>
                                <input wire:model="start_date" type="date" class="form-control form-control-sm @error('start_date') is-invalid @enderror" id="fy_start">
                                @error('start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="fy_end">End Date</label>
                                <input wire:model="end_date" type="date" class="form-control form-control-sm @error('end_date') is-invalid @enderror" id="fy_end">
                                @error('end_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="fy_status">Status</label>
                                <select wire:model="status" class="form-select form-select-sm @error('status') is-invalid @enderror" id="fy_status">
                                    <option value="active">Active</option>
                                    <option value="closed">Closed</option>
                                </select>
                                @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-4 d-flex align-items-end">
                                <div class="form-check mb-2">
                                    <input wire:model="is_default" class="form-check-input" type="checkbox" id="fy_default">
                                    <label class="form-check-label mb-0" for="fy_default">
                                        Set as Default Year
                                    </label>
                                </div>
                            </div>

                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <span class="fas fa-save me-1"></span>Save
                                </button>
                                <button type="button" wire:click="closeForm" class="btn btn-link btn-sm text-secondary">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            @endif

            <div class="table-responsive scrollbar">
                <table class="table table-sm table-striped fs-10 mb-0 align-middle table-hover">
                    <thead class="bg-200 text-900">
                        <tr>
                            <th class="px-3">Name</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th class="text-center">Default</th>
                            <th class="text-center">Status</th>
                            <th class="text-end px-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($financialYears as $fy)
                            <tr>
                                <td class="px-3 fw-bold">{{ $fy->name }}</td>
                                <td class="font-monospace">{{ $fy->start_date->format('M d, Y') }}</td>
                                <td class="font-monospace">{{ $fy->end_date->format('M d, Y') }}</td>
                                <td class="text-center">
                                    @if($fy->is_default)
                                        <span class="badge badge-subtle-success rounded-pill"><span class="fas fa-check me-1"></span>Default</span>
                                    @else
                                        @if($fy->status === 'active')
                                            <button wire:click="makeDefault({{ $fy->id }})" class="btn btn-link p-0 text-secondary fs-11" title="Make default">
                                                Make Default
                                            </button>
                                        @else
                                            <span class="text-muted fs-11">—</span>
                                        @endif
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($fy->status === 'active')
                                        <span class="badge badge-subtle-success rounded-pill">Active</span>
                                    @else
                                        <span class="badge badge-subtle-secondary rounded-pill">Closed</span>
                                    @endif
                                </td>
                                <td class="text-end px-3">
                                    <button wire:click="edit({{ $fy->id }})" class="btn btn-link p-0 text-primary" title="Edit">
                                        <span class="fas fa-edit"></span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <span class="fas fa-calendar-alt fa-2x mb-2 d-block"></span>
                                    No financial years found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($financialYears->hasPages())
            <div class="card-footer d-flex align-items-center justify-content-center bg-light">
                {{ $financialYears->links() }}
            </div>
        @endif
    </div>
</div>
