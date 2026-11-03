<div>
    {{-- Header & Breadcrumbs --}}
    <div class="row mb-3">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('support.index') }}">Support &amp; Help</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Feedback &amp; Suggestions</li>
                </ol>
            </nav>
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0 text-1000 fw-bold">Customer Feedback &amp; Suggestions</h4>
                    <p class="text-secondary fs-10 mb-0">Help us tailor MarqueeCMS to better suit your banquet and wedding hall operations.</p>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Tabs --}}
    <div class="card mb-3">
        <div class="card-header bg-body-tertiary">
            <ul class="nav nav-tabs card-header-tabs">
                <li class="nav-item">
                    <button type="button" class="nav-link {{ $activeTab === 'submit' ? 'active fw-bold text-primary' : '' }}" wire:click="$set('activeTab', 'submit')">
                        <i class="fas fa-pen-alt me-1"></i> Submit New Feedback
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link {{ $activeTab === 'history' ? 'active fw-bold text-primary' : '' }}" wire:click="$set('activeTab', 'history')">
                        <i class="fas fa-list me-1"></i> My Feedback Submissions ({{ $feedbacks->total() }})
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-4">
            @if($activeTab === 'submit')
                <form wire:submit.prevent="submitFeedback">
                    {{-- Feedback Type Selector --}}
                    <div class="mb-4">
                        <label class="form-label fs-11 fw-semibold text-secondary d-block">What type of feedback are you sharing?</label>
                        <div class="btn-group w-100 flex-wrap" role="group">
                            <input type="radio" class="btn-check" wire:model="feedbackType" value="suggestion" id="type_suggestion">
                            <label class="btn btn-outline-primary fs-11" for="type_suggestion">
                                <i class="fas fa-lightbulb me-1"></i> Suggestion
                            </label>

                            <input type="radio" class="btn-check" wire:model="feedbackType" value="bug_report" id="type_bug">
                            <label class="btn btn-outline-danger fs-11" for="type_bug">
                                <i class="fas fa-bug me-1"></i> Bug Report
                            </label>

                            <input type="radio" class="btn-check" wire:model="feedbackType" value="feature_request" id="type_feature">
                            <label class="btn btn-outline-info fs-11" for="type_feature">
                                <i class="fas fa-puzzle-piece me-1"></i> Feature Idea
                            </label>

                            <input type="radio" class="btn-check" wire:model="feedbackType" value="ux" id="type_ux">
                            <label class="btn btn-outline-warning fs-11" for="type_ux">
                                <i class="fas fa-magic me-1"></i> Ease of Use (UX)
                            </label>

                            <input type="radio" class="btn-check" wire:model="feedbackType" value="service_rating" id="type_rating">
                            <label class="btn btn-outline-success fs-11" for="type_rating">
                                <i class="fas fa-star me-1"></i> Service Rating
                            </label>
                        </div>
                    </div>

                    {{-- Subject --}}
                    <div class="mb-3">
                        <label class="form-label fs-11 fw-semibold text-secondary" for="subject">Summary / Subject <span class="text-danger">*</span></label>
                        <input type="text" id="subject" wire:model="subject" class="form-control @error('subject') is-invalid @enderror" placeholder="e.g. Add WhatsApp message notifications for booking contract signing">
                        @error('subject') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Module & Rating Row --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fs-11 fw-semibold text-secondary" for="module">Related System Area</label>
                            <select id="module" wire:model="module" class="form-select form-select-sm">
                                <option value="general">General / Whole System</option>
                                <option value="bookings">Bookings &amp; Calendar</option>
                                <option value="accounts">Accounts &amp; Expense Ledgers</option>
                                <option value="billing">Subscription &amp; Invoices</option>
                                <option value="kitchen">Kitchen &amp; Menu Planning</option>
                                <option value="inventory">Procurement &amp; Inventory</option>
                                <option value="pos">POS / Cash Register</option>
                                <option value="reports">Business Analytics &amp; Reports</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fs-11 fw-semibold text-secondary">Satisfaction Rating (1 to 5 Stars)</label>
                            <div class="d-flex align-items-center gap-2 mt-1">
                                @for($star = 1; $star <= 5; $star++)
                                    <button type="button" wire:click="$set('rating', {{ $star }})" class="btn p-0 border-0 bg-transparent">
                                        <i class="fas fa-star fs-3 {{ $rating >= $star ? 'text-warning' : 'text-300' }}"></i>
                                    </button>
                                @endfor
                                <span class="fs-11 text-secondary ms-2 fw-semibold">({{ $rating }}/5)</span>
                            </div>
                        </div>
                    </div>

                    {{-- Description --}}
                    <div class="mb-4">
                        <label class="form-label fs-11 fw-semibold text-secondary" for="description">Detailed Description <span class="text-danger">*</span></label>
                        <textarea id="description" wire:model="description" rows="5" class="form-control @error('description') is-invalid @enderror" placeholder="Explain your recommendation or describe what happened in detail..."></textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="text-end border-top pt-3">
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="submitFeedback"><i class="fas fa-paper-plane me-1"></i> Submit Feedback</span>
                            <span wire:loading wire:target="submitFeedback"><i class="fas fa-spinner fa-spin me-1"></i> Submitting...</span>
                        </button>
                    </div>
                </form>
            @else
                {{-- My Feedback History --}}
                <div class="d-flex flex-column gap-3">
                    @forelse($feedbacks as $fb)
                        <div class="card border border-200 shadow-none">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <span class="{{ $fb->type_badge }} px-2 py-0 fs-12 me-1">{{ ucfirst(str_replace('_', ' ', $fb->feedback_type)) }}</span>
                                        <span class="badge bg-light text-secondary border fs-12 font-monospace">{{ ucfirst($fb->module) }}</span>
                                        <h6 class="fw-bold text-1000 mt-2 mb-1">{{ $fb->subject }}</h6>
                                    </div>
                                    <div class="text-end">
                                        <span class="{{ $fb->status_badge }} px-2 py-0 fs-12">{{ $fb->status_label }}</span>
                                        <div class="fs-12 text-secondary mt-1">{{ $fb->created_at->format('M d, Y') }}</div>
                                    </div>
                                </div>

                                <p class="fs-10 text-700 mb-2" style="white-space: pre-line;">{!! nl2br(e($fb->description)) !!}</p>

                                @if($fb->rating)
                                    <div class="mb-2">
                                        @for($s = 1; $s <= 5; $s++)
                                            <i class="fas fa-star fs-11 {{ $s <= $fb->rating ? 'text-warning' : 'text-300' }}"></i>
                                        @endfor
                                    </div>
                                @endif

                                @if($fb->admin_response)
                                    <div class="alert alert-info py-2 px-3 mt-3 mb-0 fs-10 border-0 bg-info-subtle">
                                        <div class="d-flex align-items-center gap-1 fw-bold text-info mb-1">
                                            <i class="fas fa-reply-all me-1"></i> Response from Product Management
                                            @if($fb->reviewed_at)
                                                <span class="fw-normal text-secondary fs-12">({{ $fb->reviewed_at->format('M d, Y') }})</span>
                                            @endif
                                        </div>
                                        <div class="text-1000">{!! nl2br(e($fb->admin_response)) !!}</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5 text-secondary">
                            <i class="far fa-smile fa-3x mb-3 text-300"></i>
                            <h6>No feedback submitted yet</h6>
                            <p class="fs-10 text-muted mb-3">You haven't submitted any feedback yet. Your suggestions help shape future updates.</p>
                            <button type="button" wire:click="$set('activeTab', 'submit')" class="btn btn-primary btn-sm">
                                Submit First Feedback
                            </button>
                        </div>
                    @endforelse
                </div>

                @if($feedbacks->hasPages())
                    <div class="mt-3">
                        {{ $feedbacks->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>

