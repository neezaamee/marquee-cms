<div>
    {{-- Header & Breadcrumbs --}}
    <div class="row mb-3">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Administration</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('support.index') }}">Support Operations</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Feedback &amp; Roadmap Moderation</li>
                </ol>
            </nav>
            <h4 class="mb-0 text-1000 fw-bold">Feedback &amp; Roadmap Moderation</h4>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Tabs & Filters Card --}}
    <div class="card">
        <div class="card-header bg-body-tertiary">
            <ul class="nav nav-tabs card-header-tabs">
                <li class="nav-item">
                    <button type="button" class="nav-link {{ $activeTab === 'feedback' ? 'active fw-bold text-primary' : '' }}" wire:click="$set('activeTab', 'feedback')">
                        <i class="fas fa-comments me-1"></i> Customer Feedback ({{ $feedbacks->total() }})
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link {{ $activeTab === 'features' ? 'active fw-bold text-primary' : '' }}" wire:click="$set('activeTab', 'features')">
                        <i class="fas fa-lightbulb me-1"></i> Feature Requests &amp; Roadmap ({{ $features->total() }})
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-3 border-bottom bg-light">
            <div class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="fas fa-search text-secondary"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Search by subject, title, description, or submitter...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select wire:model.live="statusFilter" class="form-select form-select-sm">
                        <option value="all">All Statuses</option>
                        @if($activeTab === 'feedback')
                            <option value="new">New</option>
                            <option value="under_review">Under Review</option>
                            <option value="planned">Planned</option>
                            <option value="completed">Completed</option>
                            <option value="declined">Declined</option>
                        @else
                            <option value="submitted">Submitted</option>
                            <option value="under_review">Under Review</option>
                            <option value="planned">Planned</option>
                            <option value="in_development">In Development</option>
                            <option value="released">Released</option>
                            <option value="declined">Declined</option>
                        @endif
                    </select>
                </div>
                @if($activeTab === 'feedback')
                    <div class="col-md-3">
                        <select wire:model.live="typeFilter" class="form-select form-select-sm">
                            <option value="all">All Feedback Types</option>
                            <option value="suggestion">Suggestion</option>
                            <option value="bug_report">Bug Report</option>
                            <option value="feature_request">Feature Request</option>
                            <option value="ux">Ease of Use (UX)</option>
                            <option value="service_rating">Service Rating</option>
                        </select>
                    </div>
                @endif
                <div class="col-md-1 text-end">
                    <button wire:click="$set('search', ''); $set('statusFilter', 'all'); $set('typeFilter', 'all');" class="btn btn-outline-secondary btn-sm w-100" title="Reset Filters">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            @if($activeTab === 'feedback')
                {{-- Feedbacks Table --}}
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 fs-10">
                        <thead class="bg-body-secondary text-secondary">
                            <tr>
                                <th class="ps-3">CUSTOMER / BUSINESS</th>
                                <th>TYPE</th>
                                <th>SUBJECT &amp; DETAILS</th>
                                <th>MODULE</th>
                                <th>RATING</th>
                                <th>STATUS</th>
                                <th>SUBMITTED</th>
                                <th class="pe-3 text-end">ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($feedbacks as $fb)
                                <tr>
                                    <td class="ps-3">
                                        <span class="fw-bold text-1000 d-block">{{ $fb->marquee?->name ?? 'General Tenant' }}</span>
                                        <small class="text-secondary">{{ $fb->user?->name }}</small>
                                    </td>
                                    <td>
                                        <span class="{{ $fb->type_badge }} px-2 py-0 fs-11">
                                            {{ ucfirst(str_replace('_', ' ', $fb->feedback_type)) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-1000 d-block">{{ $fb->subject }}</span>
                                        <span class="text-secondary text-truncate d-inline-block" style="max-width: 300px;">
                                            {{ Str::limit($fb->description, 70) }}
                                        </span>
                                        @if($fb->admin_response)
                                            <div class="text-info fs-11 mt-1">
                                                <i class="fas fa-check-circle me-1"></i> Responded
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">{{ ucfirst($fb->module) }}</span>
                                    </td>
                                    <td>
                                        @if($fb->rating)
                                            <span class="text-warning fw-bold">{{ $fb->rating }} <i class="fas fa-star fs-12"></i></span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="{{ $fb->status_badge }} px-2 py-0 fs-11">{{ $fb->status_label }}</span>
                                    </td>
                                    <td class="text-secondary">{{ $fb->created_at->format('M d, Y') }}</td>
                                    <td class="pe-3 text-end">
                                        <button type="button" wire:click="editFeedback({{ $fb->id }})" class="btn btn-primary btn-xs" data-bs-toggle="modal" data-bs-target="#feedbackModal">
                                            Moderate
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-secondary">
                                        <p class="mb-0">No feedback matching the selected filter.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($feedbacks->hasPages())
                    <div class="card-footer bg-body-tertiary py-2">
                        {{ $feedbacks->links() }}
                    </div>
                @endif
            @else
                {{-- Feature Requests Table --}}
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 fs-10">
                        <thead class="bg-body-secondary text-secondary">
                            <tr>
                                <th class="ps-3" style="width: 80px;">VOTES</th>
                                <th>FEATURE TITLE &amp; DESCRIPTION</th>
                                <th>MODULE</th>
                                <th>AUTHOR / BUSINESS</th>
                                <th>STATUS</th>
                                <th>TARGET RELEASE</th>
                                <th class="pe-3 text-end">ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($features as $feat)
                                <tr>
                                    <td class="ps-3">
                                        <span class="badge bg-primary rounded-pill px-2 py-1 fs-11">
                                            <i class="fas fa-arrow-up me-1"></i> {{ $feat->votes_count }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-1000 d-block">{{ $feat->title }}</span>
                                        <span class="text-secondary text-truncate d-inline-block" style="max-width: 350px;">
                                            {{ Str::limit($feat->description, 90) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-secondary border">{{ ucfirst($feat->module) }}</span>
                                    </td>
                                    <td>
                                        <span class="text-1000 d-block">{{ $feat->creator?->name ?? 'User' }}</span>
                                        <small class="text-secondary">{{ $feat->marquee?->name ?? 'Community' }}</small>
                                    </td>
                                    <td>
                                        <span class="{{ $feat->status_badge }} px-2 py-0 fs-11">{{ $feat->status_label }}</span>
                                    </td>
                                    <td>
                                        <span class="font-monospace text-secondary">{{ $feat->target_release ?: 'TBD' }}</span>
                                    </td>
                                    <td class="pe-3 text-end">
                                        <button type="button" wire:click="editFeature({{ $feat->id }})" class="btn btn-primary btn-xs" data-bs-toggle="modal" data-bs-target="#featureModal">
                                            Moderate
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-secondary">
                                        <p class="mb-0">No feature requests found.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($features->hasPages())
                    <div class="card-footer bg-body-tertiary py-2">
                        {{ $features->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>

    {{-- Feedback Moderation Modal --}}
    <div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog">
            <div class="modal-content">
                <form wire:submit.prevent="saveFeedback">
                    <div class="modal-header">
                        <h5 class="modal-title" id="feedbackModalLabel">Moderate Customer Feedback</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fs-11 fw-semibold text-secondary">Status</label>
                            <select wire:model="feedbackStatus" class="form-select form-select-sm">
                                <option value="new">New</option>
                                <option value="under_review">Under Review</option>
                                <option value="planned">Planned</option>
                                <option value="completed">Completed</option>
                                <option value="declined">Declined</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fs-11 fw-semibold text-secondary">Product Management Response</label>
                            <textarea wire:model="feedbackAdminResponse" rows="4" class="form-control form-control-sm" placeholder="Provide a response visible to the customer..."></textarea>
                            <span class="fs-12 text-secondary">This response will be visible to the customer in their feedback history tab.</span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" data-bs-dismiss="modal" class="btn btn-primary btn-sm">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Feature Request Moderation Modal --}}
    <div class="modal fade" id="featureModal" tabindex="-1" aria-labelledby="featureModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog">
            <div class="modal-content">
                <form wire:submit.prevent="saveFeature">
                    <div class="modal-header">
                        <h5 class="modal-title" id="featureModalLabel">Moderate Roadmap Feature</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fs-11 fw-semibold text-secondary">Roadmap Status</label>
                                <select wire:model="featureStatus" class="form-select form-select-sm">
                                    <option value="submitted">Community Idea</option>
                                    <option value="under_review">Under Review</option>
                                    <option value="planned">Planned for Release</option>
                                    <option value="in_development">In Active Development</option>
                                    <option value="released">Shipped / Released</option>
                                    <option value="declined">Declined</option>
                                    <option value="duplicate">Duplicate</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label fs-11 fw-semibold text-secondary">Target Release</label>
                                <input type="text" wire:model="featureTargetRelease" class="form-control form-control-sm" placeholder="e.g. v1.38.0 or Q4 2026">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fs-11 fw-semibold text-secondary">Public Roadmap Update Note</label>
                            <textarea wire:model="featurePublicNotes" rows="3" class="form-control form-control-sm" placeholder="Message visible to all users browsing the roadmap..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fs-11 fw-semibold text-secondary">Internal Engineering Notes (Private)</label>
                            <textarea wire:model="featureAdminNotes" rows="2" class="form-control form-control-sm" placeholder="Internal notes for developers..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" data-bs-dismiss="modal" class="btn btn-primary btn-sm">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

