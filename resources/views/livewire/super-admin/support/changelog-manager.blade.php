<div>
    {{-- Header & Breadcrumbs --}}
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Administration</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('support.index') }}">Support Operations</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Product Changelog</li>
                    </ol>
                </nav>
                <h4 class="mb-0 text-1000 fw-bold">Product Changelog Management</h4>
            </div>
            <div>
                <button type="button" wire:click="resetForm" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#changelogModal">
                    <i class="fas fa-plus me-1"></i> New Release
                </button>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Table Card --}}
    <div class="card">
        <div class="card-header bg-body-tertiary py-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="fas fa-search text-secondary"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Search version (v1.38) or release title...">
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 fs-10">
                    <thead class="bg-body-secondary text-secondary">
                        <tr>
                            <th class="ps-3" style="width: 110px;">VERSION</th>
                            <th>TITLE &amp; SUMMARY</th>
                            <th>TYPE</th>
                            <th>AFFECTED MODULES</th>
                            <th>RELEASE DATE</th>
                            <th>STATUS</th>
                            <th class="pe-3 text-end" style="width: 140px;">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($changelogs as $cl)
                            <tr>
                                <td class="ps-3 font-monospace fw-bold text-primary">{{ $cl->version }}</td>
                                <td>
                                    <span class="fw-bold text-1000 d-block">{{ $cl->title }}</span>
                                    <small class="text-secondary text-truncate d-inline-block" style="max-width: 320px;">
                                        {{ $cl->summary ?: Str::limit($cl->description, 60) }}
                                    </small>
                                </td>
                                <td>
                                    <span class="{{ $cl->type_badge }} px-2 py-0 fs-11">{{ $cl->type_label }}</span>
                                </td>
                                <td>
                                    @if(!empty($cl->affected_modules) && is_array($cl->affected_modules))
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach($cl->affected_modules as $mod)
                                                <span class="badge bg-light text-dark border fs-12">{{ ucfirst($mod) }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-secondary">{{ $cl->release_date->format('M d, Y') }}</td>
                                <td>
                                    <button type="button" wire:click="togglePublish({{ $cl->id }})" class="btn btn-xs {{ $cl->is_published ? 'btn-success' : 'btn-secondary' }}">
                                        {{ $cl->is_published ? 'Published' : 'Draft' }}
                                    </button>
                                </td>
                                <td class="pe-3 text-end">
                                    <button type="button" wire:click="edit({{ $cl->id }})" class="btn btn-xs btn-outline-primary" data-bs-toggle="modal" data-bs-target="#changelogModal">
                                        Edit
                                    </button>
                                    <button type="button" wire:click="delete({{ $cl->id }})" wire:confirm="Are you sure you want to delete this changelog entry?" class="btn btn-xs btn-outline-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-secondary">
                                    <p class="mb-0">No changelog entries found.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($changelogs->hasPages())
            <div class="card-footer bg-body-tertiary py-2">
                {{ $changelogs->links() }}
            </div>
        @endif
    </div>

    {{-- Create / Edit Changelog Modal --}}
    <div class="modal fade" id="changelogModal" tabindex="-1" aria-labelledby="changelogModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form wire:submit.prevent="save">
                    <div class="modal-header">
                        <h5 class="modal-title" id="changelogModalLabel">{{ $editingId ? 'Edit Release' : 'New Release Entry' }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label fs-11 fw-semibold text-secondary">Version Tag <span class="text-danger">*</span></label>
                                <input type="text" wire:model="version" class="form-control form-control-sm font-monospace @error('version') is-invalid @enderror" placeholder="e.g. v1.38.0">
                                @error('version') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fs-11 fw-semibold text-secondary">Release Type <span class="text-danger">*</span></label>
                                <select wire:model="release_type" class="form-select form-select-sm">
                                    <option value="new_feature">New Feature</option>
                                    <option value="improvement">Improvement</option>
                                    <option value="bug_fix">Bug Fix</option>
                                    <option value="security_update">Security Update</option>
                                    <option value="performance">Performance Tuning</option>
                                    <option value="maintenance">Maintenance</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fs-11 fw-semibold text-secondary">Release Date <span class="text-danger">*</span></label>
                                <input type="date" wire:model="release_date" class="form-control form-control-sm @error('release_date') is-invalid @enderror">
                                @error('release_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fs-11 fw-semibold text-secondary">Release Title <span class="text-danger">*</span></label>
                            <input type="text" wire:model="title" class="form-control form-control-sm @error('title') is-invalid @enderror" placeholder="e.g. Real-Time Live Support Chat & Community Roadmap">
                            @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fs-11 fw-semibold text-secondary">Summary (Short Subtitle)</label>
                            <input type="text" wire:model="summary" class="form-control form-control-sm @error('summary') is-invalid @enderror" placeholder="High-level 1-sentence synopsis of this release...">
                            @error('summary') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fs-11 fw-semibold text-secondary d-block">Affected System Modules</label>
                            <div class="d-flex flex-wrap gap-3 fs-11">
                                @foreach(['bookings', 'accounts', 'billing', 'inventory', 'kitchen', 'pos', 'reports', 'support', 'settings'] as $mName)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" wire:model="affected_modules" value="{{ $mName }}" id="mod_{{ $mName }}">
                                        <label class="form-check-label" for="mod_{{ $mName }}">{{ ucfirst($mName) }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fs-11 fw-semibold text-secondary">Release Notes &amp; Walkthrough <span class="text-danger">*</span></label>
                            <textarea wire:model="description" rows="6" class="form-control form-control-sm @error('description') is-invalid @enderror" placeholder="Detailed bullet points or markdown describing changes..."></textarea>
                            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" wire:model="is_published" id="is_published_check">
                            <label class="form-check-label fs-11 fw-semibold text-secondary" for="is_published_check">Published (Visible to all users immediately)</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" data-bs-dismiss="modal" class="btn btn-primary btn-sm">Save Release</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
