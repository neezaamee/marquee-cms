<div>
    {{-- Header & Breadcrumbs --}}
    <div class="row mb-3">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('support.index') }}">Support &amp; Help</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('support.tickets.index') }}">Support Tickets</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $ticket->ticket_number }}</li>
                </ol>
            </nav>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="fs-4 fw-bold font-monospace text-primary">{{ $ticket->ticket_number }}</span>
                        <span class="{{ $ticket->status_badge }} px-2 py-1 fs-11">{{ $ticket->status_label }}</span>
                        <span class="{{ $ticket->priority_badge }} px-2 py-1 fs-11">{{ ucfirst($ticket->priority) }} Priority</span>
                    </div>
                    <h5 class="mb-0 text-1000 fw-bold">{{ $ticket->subject }}</h5>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('support.tickets.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Back to List
                    </a>
                    @if(!in_array($ticket->status, ['resolved', 'closed']))
                        <button type="button" wire:click="markResolved" wire:confirm="Are you sure you want to mark this ticket as resolved?" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-check-circle me-1"></i> Mark as Resolved
                        </button>
                    @elseif(in_array($ticket->status, ['resolved', 'closed']))
                        <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#reopenModal">
                            <i class="fas fa-undo me-1"></i> Reopen Ticket
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-3">
        {{-- Left: Conversation Timeline --}}
        <div class="col-lg-8">
            {{-- Message Timeline --}}
            <div class="card mb-4">
                <div class="card-header bg-body-tertiary d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 text-1000 fw-semibold"><i class="fas fa-comments me-2 text-primary"></i>Conversation History</h6>
                    <span class="fs-10 text-secondary">{{ $messages->count() }} message(s)</span>
                </div>
                <div class="card-body p-4">
                    <div class="timeline-vertical">
                        @foreach($messages as $index => $msg)
                            @php
                                $isStaff = $msg->author && ($msg->author->isSuperAdmin() || $msg->author->role?->name === 'support_agent' || $msg->user_id !== $ticket->user_id);
                            @endphp
                            <div class="border rounded-3 p-3 mb-3 {{ $isStaff ? 'bg-primary-subtle border-primary-subtle' : 'bg-body-secondary border-light' }}">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar avatar-s">
                                            <div class="avatar-name rounded-circle {{ $isStaff ? 'bg-primary text-white' : 'bg-secondary text-white' }}">
                                                {{ strtoupper(substr($msg->author?->name ?? 'User', 0, 1)) }}
                                            </div>
                                        </div>
                                        <div>
                                            <span class="fw-bold fs-10 text-1000">{{ $msg->author?->name ?? 'System' }}</span>
                                            @if($isStaff)
                                                <span class="badge bg-primary text-white ms-1 fs-11">Support Specialist</span>
                                            @else
                                                <span class="badge bg-secondary text-white ms-1 fs-11">Customer</span>
                                            @endif
                                        </div>
                                    </div>
                                    <span class="text-secondary fs-11" title="{{ $msg->created_at->format('M d, Y h:i A') }}">
                                        {{ $msg->created_at->diffForHumans() }}
                                    </span>
                                </div>

                                <div class="fs-10 text-1000" style="white-space: pre-line;">{!! nl2br(e($msg->message)) !!}</div>

                                {{-- Attachments --}}
                                @if($msg->attachments->isNotEmpty())
                                    <div class="mt-3 pt-2 border-top border-200">
                                        <span class="fs-11 fw-semibold text-secondary d-block mb-1">Attachments:</span>
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach($msg->attachments as $att)
                                                <a href="{{ route('support.tickets.attachments.download', $att->id) }}" class="btn btn-sm btn-white border fs-11 d-inline-flex align-items-center" target="_blank">
                                                    <i class="{{ $att->file_icon }} me-1"></i>
                                                    <span class="me-2">{{ $att->file_name }}</span>
                                                    <span class="text-secondary">({{ $att->formatted_size }})</span>
                                                    <i class="fas fa-download ms-2 text-primary fs-12"></i>
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- CSAT Rating Card (If Resolved or Closed) --}}
            @if(in_array($ticket->status, ['resolved', 'closed']))
                <div class="card mb-4 border-success-subtle bg-success-subtle">
                    <div class="card-header bg-transparent border-0 pb-0">
                        <h6 class="text-success fw-bold mb-1">
                            <i class="fas fa-star me-2"></i>How was our support service?
                        </h6>
                        <p class="text-secondary fs-10 mb-0">Your rating helps our team constantly improve service quality.</p>
                    </div>
                    <div class="card-body">
                        @if($ratingSubmitted)
                            <div class="text-center py-2">
                                <div class="mb-2">
                                    @for($s = 1; $s <= 5; $s++)
                                        <i class="fas fa-star fs-3 {{ $s <= $rating ? 'text-warning' : 'text-300' }}"></i>
                                    @endfor
                                </div>
                                <h6 class="text-success fw-bold mb-1">Thank you for rating our support!</h6>
                                @if($feedbackComment)
                                    <p class="text-secondary fs-10 fst-italic mb-0">"{{ $feedbackComment }}"</p>
                                @endif
                            </div>
                        @else
                            <form wire:submit.prevent="submitRating">
                                <div class="d-flex justify-content-center gap-3 my-3">
                                    @for($i = 1; $i <= 5; $i++)
                                        <button type="button" wire:click="$set('rating', {{ $i }})" class="btn p-2 border-0 bg-transparent">
                                            <i class="fas fa-star fs-2 {{ $rating && $i <= $rating ? 'text-warning' : 'text-300' }}"></i>
                                            <div class="fs-11 text-secondary mt-1">
                                                {{ match($i) { 1 => 'Poor', 2 => 'Fair', 3 => 'Good', 4 => 'Very Good', 5 => 'Excellent' } }}
                                            </div>
                                        </button>
                                    @endfor
                                </div>
                                @error('rating') <div class="text-danger text-center fs-10 mb-2">{{ $message }}</div> @enderror

                                <div class="mb-3">
                                    <textarea wire:model="feedbackComment" class="form-control" rows="2" placeholder="Optional: Any additional comments or praise for our support specialist?"></textarea>
                                </div>

                                <div class="text-end">
                                    <button type="submit" class="btn btn-success btn-sm" {{ empty($rating) ? 'disabled' : '' }}>
                                        <i class="fas fa-check me-1"></i> Submit Feedback
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Reply Card (If not closed) --}}
            @if($ticket->status !== 'closed')
                <div class="card">
                    <div class="card-header bg-body-tertiary">
                        <h6 class="mb-0 text-1000 fw-semibold"><i class="fas fa-reply me-2 text-primary"></i>Post a Reply</h6>
                    </div>
                    <div class="card-body p-4">
                        <form wire:submit.prevent="sendReply">
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="replyMessage">Your Message <span class="text-danger">*</span></label>
                                <textarea id="replyMessage" wire:model="replyMessage" rows="5" class="form-control @error('replyMessage') is-invalid @enderror" placeholder="Type your reply or additional information here..."></textarea>
                                @error('replyMessage') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- File Attachments --}}
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="replyAttachments">Attach Documents / Screenshots (Optional)</label>
                                <input type="file" id="replyAttachments" wire:model="attachments" multiple class="form-control @error('attachments.*') is-invalid @enderror">
                                <span class="fs-11 text-secondary d-block mt-1">Accepted formats: JPG, PNG, PDF, Word DOCX, ZIP (Max 10 MB each).</span>
                                @error('attachments.*') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror

                                <div wire:loading wire:target="attachments" class="text-primary fs-11 mt-1">
                                    <i class="fas fa-spinner fa-spin me-1"></i> Uploading files...
                                </div>

                                @if(!empty($attachments))
                                    <div class="mt-2 d-flex flex-wrap gap-2">
                                        @foreach($attachments as $idx => $attFile)
                                            <div class="badge bg-light text-dark border p-2 d-flex align-items-center">
                                                <i class="far fa-file me-2 text-primary"></i>
                                                <span class="text-truncate me-2" style="max-width: 200px;">{{ $attFile->getClientOriginalName() }}</span>
                                                <button type="button" wire:click="removeAttachment({{ $idx }})" class="btn-close btn-close-sm" aria-label="Remove"></button>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="sendReply"><i class="fas fa-paper-plane me-1"></i> Send Reply</span>
                                    <span wire:loading wire:target="sendReply"><i class="fas fa-spinner fa-spin me-1"></i> Sending...</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @else
                <div class="alert alert-secondary text-center py-3">
                    <i class="fas fa-lock me-2"></i> This ticket is permanently closed. If you require further assistance on this issue, please click <strong>Reopen Ticket</strong> above.
                </div>
            @endif
        </div>

        {{-- Right: Ticket Information & Metadata --}}
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header bg-body-tertiary">
                    <h6 class="mb-0 text-1000 fw-semibold"><i class="fas fa-info-circle me-2 text-primary"></i>Ticket Details</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0 fs-10">
                        <tbody>
                            <tr>
                                <th class="text-secondary ps-3 py-2" style="width: 40%;">Ticket Number</th>
                                <td class="pe-3 py-2 font-monospace fw-bold text-primary">{{ $ticket->ticket_number }}</td>
                            </tr>
                            <tr>
                                <th class="text-secondary ps-3 py-2">Status</th>
                                <td class="pe-3 py-2"><span class="{{ $ticket->status_badge }} px-2 py-1 fs-11">{{ $ticket->status_label }}</span></td>
                            </tr>
                            <tr>
                                <th class="text-secondary ps-3 py-2">Priority</th>
                                <td class="pe-3 py-2"><span class="{{ $ticket->priority_badge }} px-2 py-1 fs-11">{{ ucfirst($ticket->priority) }}</span></td>
                            </tr>
                            <tr>
                                <th class="text-secondary ps-3 py-2">Category</th>
                                <td class="pe-3 py-2 fw-semibold">{{ $ticket->category ? $ticket->category->name : 'General' }}</td>
                            </tr>
                            @if($ticket->related_module)
                                <tr>
                                    <th class="text-secondary ps-3 py-2">Related Module</th>
                                    <td class="pe-3 py-2"><span class="badge bg-light text-dark border">{{ ucfirst($ticket->related_module) }}</span></td>
                                </tr>
                            @endif
                            @if($ticket->related_record_id)
                                <tr>
                                    <th class="text-secondary ps-3 py-2">Reference ID</th>
                                    <td class="pe-3 py-2 font-monospace">#{{ $ticket->related_record_id }}</td>
                                </tr>
                            @endif
                            <tr>
                                <th class="text-secondary ps-3 py-2">Assigned Specialist</th>
                                <td class="pe-3 py-2">
                                    @if($ticket->assignedAgent)
                                        <i class="fas fa-user-tie text-primary me-1"></i> {{ $ticket->assignedAgent->name }}
                                    @else
                                        <span class="text-secondary fst-italic">Assigned to Queue</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="text-secondary ps-3 py-2">Created At</th>
                                <td class="pe-3 py-2">{{ $ticket->created_at->format('M d, Y h:i A') }}</td>
                            </tr>
                            @if($ticket->resolved_at)
                                <tr>
                                    <th class="text-secondary ps-3 py-2">Resolved At</th>
                                    <td class="pe-3 py-2 text-success">{{ $ticket->resolved_at->format('M d, Y h:i A') }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- SLA Information --}}
            <div class="card mb-3">
                <div class="card-header bg-body-tertiary">
                    <h6 class="mb-0 text-1000 fw-semibold"><i class="fas fa-stopwatch me-2 text-warning"></i>SLA Commitment</h6>
                </div>
                <div class="card-body fs-10">
                    @if($ticket->sla_due_at)
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-secondary">Resolution Target:</span>
                            <span class="fw-bold">{{ $ticket->sla_due_at->format('M d, Y h:i A') }}</span>
                        </div>
                        @if($ticket->is_sla_breached)
                            <div class="alert alert-danger p-2 mb-0 fs-11">
                                <i class="fas fa-exclamation-circle me-1"></i> SLA Target has been escalated to Tier-2 support team.
                            </div>
                        @else
                            <div class="alert alert-info p-2 mb-0 fs-11">
                                <i class="fas fa-shield-alt me-1"></i> Active SLA target: Our team will address within designated turnaround time.
                            </div>
                        @endif
                    @else
                        <span class="text-secondary">Standard 24-hour response SLA applies.</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Reopen Modal --}}
    <div class="modal fade" id="reopenModal" tabindex="-1" aria-labelledby="reopenModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reopenModalLabel">Reopen Ticket {{ $ticket->ticket_number }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Reason for Reopening <span class="text-danger">*</span></label>
                        <textarea wire:model="reopenReason" class="form-control" rows="3" placeholder="Please clarify why the issue requires further attention..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" wire:click="reopenTicket" data-bs-dismiss="modal" class="btn btn-danger btn-sm">
                        <i class="fas fa-undo me-1"></i> Confirm Reopen
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
