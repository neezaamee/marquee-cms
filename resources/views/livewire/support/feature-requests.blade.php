<div>
    {{-- Header & Breadcrumbs --}}
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="{{ route('support.index') }}">Support &amp; Help</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Feature Requests</li>
                    </ol>
                </nav>
                <h4 class="mb-0 text-1000 fw-bold">Community Roadmap &amp; Feature Requests</h4>
                <p class="text-secondary fs-10 mb-0">Vote on requested features or propose new functionality for MarqueeCMS.</p>
            </div>
            <div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newIdeaModal">
                    <i class="fas fa-plus me-1"></i> Suggest Feature
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

    {{-- Roadmap Summary Metrics --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <div class="card border-0 shadow-none bg-primary-subtle border-start border-primary border-4">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-m me-3">
                            <div class="avatar-name rounded-circle bg-white text-primary">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="text-secondary fs-11 mb-0">Planned on Roadmap</h6>
                            <h4 class="text-primary fw-bold mb-0">{{ number_format($countPlanned) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card border-0 shadow-none bg-info-subtle border-start border-info border-4">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-m me-3">
                            <div class="avatar-name rounded-circle bg-white text-info">
                                <i class="fas fa-code-branch"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="text-secondary fs-11 mb-0">In Active Development</h6>
                            <h4 class="text-info fw-bold mb-0">{{ number_format($countInDev) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card border-0 shadow-none bg-success-subtle border-start border-success border-4">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-m me-3">
                            <div class="avatar-name rounded-circle bg-white text-success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="text-secondary fs-11 mb-0">Recently Released</h6>
                            <h4 class="text-success fw-bold mb-0">{{ number_format($countReleased) }}</h4>
                        </div>
                    </div>
                </div>
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
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Search feature ideas and suggestions...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select wire:model.live="statusFilter" class="form-select form-select-sm">
                        <option value="all">All Statuses</option>
                        <option value="submitted">Community Ideas</option>
                        <option value="under_review">Under Review</option>
                        <option value="planned">Planned</option>
                        <option value="in_development">In Development</option>
                        <option value="released">Released</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select wire:model.live="moduleFilter" class="form-select form-select-sm">
                        <option value="all">All Modules</option>
                        <option value="bookings">Bookings &amp; Calendar</option>
                        <option value="accounts">Accounts &amp; Expenses</option>
                        <option value="billing">Subscription &amp; Invoices</option>
                        <option value="kitchen">Kitchen &amp; Menu</option>
                        <option value="inventory">Inventory</option>
                        <option value="pos">POS / FBR</option>
                        <option value="general">General</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card-body p-3">
            <div class="row g-3">
                @forelse($features as $feat)
                    @php
                        $hasVoted = $feat->votes->contains('user_id', $currentUserId);
                    @endphp
                    <div class="col-md-6 col-lg-6">
                        <div class="card h-100 border border-200 shadow-none hover-shadow transition-all">
                            <div class="card-body p-3 d-flex gap-3">
                                {{-- Upvote Button --}}
                                <div class="text-center" style="min-width: 55px;">
                                    <button type="button" wire:click="toggleVote({{ $feat->id }})" class="btn {{ $hasVoted ? 'btn-primary' : 'btn-outline-secondary' }} btn-sm w-100 p-2 d-flex flex-column align-items-center rounded-3">
                                        <i class="fas fa-caret-up fs-2 lh-1"></i>
                                        <span class="fw-bold fs-11 lh-1">{{ $feat->votes_count }}</span>
                                    </button>
                                </div>

                                {{-- Details --}}
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <span class="badge bg-light text-secondary border font-monospace fs-12">{{ ucfirst($feat->module) }}</span>
                                        <span class="{{ $feat->status_badge }} px-2 py-0 fs-12">{{ $feat->status_label }}</span>
                                    </div>
                                    <h6 class="fw-bold text-1000 mb-1">{{ $feat->title }}</h6>
                                    <p class="fs-10 text-secondary mb-2" style="white-space: pre-line;">{{ Str::limit($feat->description, 140) }}</p>

                                    @if($feat->public_notes)
                                        <div class="alert alert-info py-1 px-2 fs-11 mb-2">
                                            <i class="fas fa-info-circle me-1"></i> <strong>Roadmap update:</strong> {{ $feat->public_notes }}
                                        </div>
                                    @endif

                                    <div class="d-flex justify-content-between align-items-center fs-12 text-secondary pt-2 border-top border-100">
                                        <span>Suggested by {{ $feat->creator?->name ?? 'User' }}</span>
                                        <span>{{ $feat->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center py-5 text-secondary">
                        <i class="fas fa-lightbulb fa-3x mb-3 text-300"></i>
                        <h5>No feature requests found</h5>
                        <p class="fs-10 text-muted mb-3">Be the first to suggest an idea or clear the search filters.</p>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newIdeaModal">
                            <i class="fas fa-plus me-1"></i> Submit First Feature Request
                        </button>
                    </div>
                @endforelse
            </div>

            @if($features->hasPages())
                <div class="mt-4">
                    {{ $features->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Submit Idea Modal --}}
    <div class="modal fade" id="newIdeaModal" tabindex="-1" aria-labelledby="newIdeaModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog">
            <div class="modal-content">
                <form wire:submit.prevent="submitIdea">
                    <div class="modal-header">
                        <h5 class="modal-title" id="newIdeaModalLabel">Submit a Feature Suggestion</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fs-11 fw-semibold text-secondary">Feature Title <span class="text-danger">*</span></label>
                            <input type="text" wire:model="newTitle" class="form-control @error('newTitle') is-invalid @enderror" placeholder="e.g. Export kitchen menu ingredients to PDF">
                            @error('newTitle') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fs-11 fw-semibold text-secondary">System Module <span class="text-danger">*</span></label>
                            <select wire:model="newModule" class="form-select @error('newModule') is-invalid @enderror">
                                <option value="general">General / Multi-Module</option>
                                <option value="bookings">Bookings &amp; Calendar</option>
                                <option value="accounts">Accounts &amp; Expenses</option>
                                <option value="billing">Subscription &amp; Invoices</option>
                                <option value="kitchen">Kitchen &amp; Menu</option>
                                <option value="inventory">Inventory &amp; Supplies</option>
                                <option value="pos">POS / FBR</option>
                                <option value="reports">Reports &amp; Analytics</option>
                            </select>
                            @error('newModule') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fs-11 fw-semibold text-secondary">Description &amp; Use Case <span class="text-danger">*</span></label>
                            <textarea wire:model="newDescription" rows="4" class="form-control @error('newDescription') is-invalid @enderror" placeholder="Describe how this feature would help your hall operations..."></textarea>
                            @error('newDescription') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="submitIdea"><i class="fas fa-paper-plane me-1"></i> Post Idea</span>
                            <span wire:loading wire:target="submitIdea"><i class="fas fa-spinner fa-spin me-1"></i> Posting...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
