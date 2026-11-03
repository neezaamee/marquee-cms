<div>
    {{-- Header & Breadcrumbs --}}
    <div class="row mb-3">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('support.index') }}">Support &amp; Help</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Product Changelog</li>
                </ol>
            </nav>
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0 text-1000 fw-bold">Product Changelog &amp; Release Notes</h4>
                    <p class="text-secondary fs-10 mb-0">Stay up to date with new modules, improvements, and system patches deployed to MarqueeCMS.</p>
                </div>
                @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('support.changelog.manage'))
                    <a href="{{ route('admin.support.changelog') }}" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-edit me-1"></i> Manage Releases
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Filter Toolbar --}}
    <div class="card mb-4">
        <div class="card-header bg-body-tertiary py-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="fas fa-search text-secondary"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Search version (e.g. v1.38) or feature update...">
                    </div>
                </div>
                <div class="col-md-4">
                    <select wire:model.live="typeFilter" class="form-select form-select-sm">
                        <option value="all">All Release Types</option>
                        <option value="new_feature">New Features</option>
                        <option value="improvement">Improvements &amp; Enhancements</option>
                        <option value="bug_fix">Bug Fixes</option>
                        <option value="security_update">Security Updates</option>
                        <option value="performance">Performance Tuning</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>
                <div class="col-md-2 text-end">
                    <button wire:click="$set('search', ''); $set('typeFilter', 'all');" class="btn btn-outline-secondary btn-sm w-100" title="Reset Filters">
                        <i class="fas fa-sync-alt me-1"></i> Reset
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body p-4">
            <div class="timeline-vertical">
                @forelse($changelogs as $cl)
                    <div class="border rounded-3 p-4 mb-4 bg-white shadow-sm border-200">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-primary fs-10 font-monospace px-3 py-1">{{ $cl->version }}</span>
                                <span class="{{ $cl->type_badge }} px-2 py-1 fs-11">{{ $cl->type_label }}</span>
                                <h5 class="mb-0 text-1000 fw-bold">{{ $cl->title }}</h5>
                            </div>
                            <span class="text-secondary fs-11">
                                <i class="far fa-calendar-alt me-1"></i> {{ $cl->release_date->format('F d, Y') }}
                            </span>
                        </div>

                        @if($cl->summary)
                            <p class="text-secondary fs-10 fw-semibold mb-3">{{ $cl->summary }}</p>
                        @endif

                        {{-- Affected Modules --}}
                        @if(!empty($cl->affected_modules) && is_array($cl->affected_modules))
                            <div class="d-flex flex-wrap gap-1 mb-3">
                                <span class="fs-12 text-secondary me-1 align-self-center">Affected:</span>
                                @foreach($cl->affected_modules as $mod)
                                    <span class="badge bg-light text-secondary border font-monospace fs-12">{{ ucfirst($mod) }}</span>
                                @endforeach
                            </div>
                        @endif

                        <div class="fs-10 text-1000 border-top pt-3" style="white-space: pre-line;">{!! nl2br(e($cl->description)) !!}</div>
                    </div>
                @empty
                    <div class="text-center py-5 text-secondary">
                        <i class="fas fa-bullhorn fa-3x mb-3 text-300"></i>
                        <h5>No changelog entries found</h5>
                        <p class="fs-10 text-muted">Try adjusting your version search or filter criteria.</p>
                    </div>
                @endforelse
            </div>

            @if($changelogs->hasPages())
                <div class="mt-4">
                    {{ $changelogs->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
