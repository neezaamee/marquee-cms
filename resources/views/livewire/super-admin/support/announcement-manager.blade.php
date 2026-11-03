<div>
    {{-- Header & Breadcrumbs --}}
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Administration</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('support.index') }}">Support Operations</a></li>
                        <li class="breadcrumb-item active" aria-current="page">System Announcements</li>
                    </ol>
                </nav>
                <h4 class="mb-0 text-1000 fw-bold">System Announcements &amp; Service Notices</h4>
            </div>
            <div>
                <button type="button" wire:click="resetForm" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#announcementModal">
                    <i class="fas fa-plus me-1"></i> New Announcement
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

    {{-- Announcements Table Card --}}
    <div class="card">
        <div class="card-header bg-body-tertiary py-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="fas fa-search text-secondary"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Search notices by title or message...">
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 fs-10">
                    <thead class="bg-body-secondary text-secondary">
                        <tr>
                            <th class="ps-3">SEVERITY</th>
                            <th>TITLE &amp; MESSAGE</th>
                            <th>TARGET AUDIENCE</th>
                            <th>SCHEDULE</th>
                            <th>ACKNOWLEDGED</th>
                            <th>STATUS</th>
                            <th class="pe-3 text-end" style="width: 140px;">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($announcements as $ann)
                            <tr>
                                <td class="ps-3">
                                    <span class="{{ $ann->severity_badge }} px-2 py-1 fs-11">
                                        <i class="{{ $ann->severity_icon }} me-1"></i> {{ ucfirst($ann->severity) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-bold text-1000 d-block">{{ $ann->title }}</span>
                                    <small class="text-secondary text-truncate d-inline-block" style="max-width: 320px;">
                                        {{ Str::limit($ann->message, 80) }}
                                    </small>
                                </td>
                                <td>
                                    @if($ann->target_audience === 'all')
                                        <span class="badge bg-light text-dark border">All Tenants</span>
                                    @elseif($ann->target_audience === 'selected_tenants')
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                            {{ count($ann->target_ids ?: []) }} Marquee(s)
                                        </span>
                                    @elseif($ann->target_audience === 'selected_roles')
                                        <span class="badge bg-info-subtle text-info border border-info-subtle">
                                            {{ count($ann->target_ids ?: []) }} Role(s)
                                        </span>
                                    @endif
                                </td>
                                <td class="text-secondary fs-11">
                                    @if($ann->start_date && $ann->end_date)
                                        {{ $ann->start_date->format('M d') }} &ndash; {{ $ann->end_date->format('M d, Y') }}
                                    @else
                                        Permanent / Immediate
                                    @endif
                                </td>
                                <td>
                                    @if($ann->requires_acknowledgment)
                                        <span class="badge bg-secondary font-monospace">{{ $ann->acknowledgments_count }} users</span>
                                    @else
                                        <span class="text-muted fs-11">Optional</span>
                                    @endif
                                </td>
                                <td>
                                    <button type="button" wire:click="togglePublish({{ $ann->id }})" class="btn btn-xs {{ $ann->is_published ? 'btn-success' : 'btn-secondary' }}">
                                        {{ $ann->is_published ? 'Published' : 'Hidden' }}
                                    </button>
                                </td>
                                <td class="pe-3 text-end">
                                    <button type="button" wire:click="edit({{ $ann->id }})" class="btn btn-xs btn-outline-primary" data-bs-toggle="modal" data-bs-target="#announcementModal">
                                        Edit
                                    </button>
                                    <button type="button" wire:click="delete({{ $ann->id }})" wire:confirm="Delete this announcement?" class="btn btn-xs btn-outline-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-secondary">
                                    <p class="mb-0">No announcements found.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($announcements->hasPages())
            <div class="card-footer bg-body-tertiary py-2">
                {{ $announcements->links() }}
            </div>
        @endif
    </div>

    {{-- Announcement Create/Edit Modal --}}
    <div class="modal fade" id="announcementModal" tabindex="-1" aria-labelledby="announcementModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form wire:submit.prevent="save">
                    <div class="modal-header">
                        <h5 class="modal-title" id="announcementModalLabel">{{ $editingId ? 'Edit Announcement' : 'New System Announcement' }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-8">
                                <label class="form-label fs-11 fw-semibold text-secondary">Announcement Title <span class="text-danger">*</span></label>
                                <input type="text" wire:model="title" class="form-control form-control-sm @error('title') is-invalid @enderror" placeholder="e.g. Scheduled System Maintenance on Sunday Midnight">
                                @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fs-11 fw-semibold text-secondary">Severity Level <span class="text-danger">*</span></label>
                                <select wire:model="severity" class="form-select form-select-sm">
                                    <option value="info">Info (Blue Notice)</option>
                                    <option value="warning">Warning (Yellow Advisory)</option>
                                    <option value="critical">Critical (Red Outage / Alert)</option>
                                    <option value="success">Success (Green Celebration)</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fs-11 fw-semibold text-secondary">Start Display Date / Time</label>
                                <input type="datetime-local" wire:model="start_date" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-11 fw-semibold text-secondary">End Display Date / Time</label>
                                <input type="datetime-local" wire:model="end_date" class="form-control form-control-sm">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fs-11 fw-semibold text-secondary">Target Audience <span class="text-danger">*</span></label>
                                <select wire:model.live="target_audience" class="form-select form-select-sm">
                                    <option value="all">All Marquees &amp; Users (Global)</option>
                                    <option value="selected_tenants">Specific Marquee Tenants</option>
                                    <option value="selected_roles">Specific User Roles</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                @if($target_audience === 'selected_tenants')
                                    <label class="form-label fs-11 fw-semibold text-secondary">Select Target Marquees</label>
                                    <select wire:model="target_ids" multiple class="form-select form-select-sm" style="height: 100px;">
                                        @foreach($marquees as $mq)
                                            <option value="{{ $mq->id }}">{{ $mq->name }}</option>
                                        @endforeach
                                    </select>
                                @elseif($target_audience === 'selected_roles')
                                    <label class="form-label fs-11 fw-semibold text-secondary">Select Target Roles</label>
                                    <select wire:model="target_ids" multiple class="form-select form-select-sm" style="height: 100px;">
                                        @foreach($roles as $rl)
                                            <option value="{{ $rl->name }}">{{ $rl->display_name ?: $rl->name }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fs-11 fw-semibold text-secondary">Announcement Message <span class="text-danger">*</span></label>
                            <textarea wire:model="message" rows="4" class="form-control form-control-sm @error('message') is-invalid @enderror" placeholder="Write the detailed service announcement or instructions..."></textarea>
                            @error('message') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" wire:model="requires_acknowledgment" id="requires_ack_check">
                                    <label class="form-check-label fs-11 fw-semibold text-secondary" for="requires_ack_check">
                                        Requires "I Acknowledge" confirmation to dismiss
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" wire:model="is_published" id="is_published_ann">
                                    <label class="form-check-label fs-11 fw-semibold text-secondary" for="is_published_ann">
                                        Published (Active immediately)
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" data-bs-dismiss="modal" class="btn btn-primary btn-sm">Save Announcement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
