<div>
    {{-- Header & Breadcrumbs --}}
    <div class="row mb-3">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Administration</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.support.tickets') }}">Support Desk Queue</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $ticket->ticket_number }}</li>
                </ol>
            </nav>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="fs-4 fw-bold font-monospace text-primary">{{ $ticket->ticket_number }}</span>
                        <span class="{{ $ticket->status_badge }} px-2 py-1 fs-11">{{ $ticket->status_label }}</span>
                        <span class="{{ $ticket->priority_badge }} px-2 py-1 fs-11">{{ ucfirst($ticket->priority) }} Priority</span>
                        @if($ticket->is_sla_breached)
                            <span class="badge bg-danger px-2 py-1 fs-11"><i class="fas fa-exclamation-triangle me-1"></i> SLA Breached</span>
                        @endif
                    </div>
                    <h5 class="mb-0 text-1000 fw-bold">{{ $ticket->subject }}</h5>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.support.tickets') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Back to Queue
                    </a>
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
        {{-- Left: Conversation & Reply Area --}}
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-body-tertiary d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 text-1000 fw-semibold"><i class="fas fa-history me-2 text-primary"></i>Conversation &amp; Internal Notes</h6>
                    <span class="fs-10 text-secondary">{{ $messages->count() }} total message(s)</span>
                </div>
                <div class="card-body p-4">
                    <div class="timeline-vertical">
                        @foreach($messages as $msg)
                            @if($msg->is_internal_note)
                                {{-- Private Internal Staff Note --}}
                                <div class="border border-warning bg-warning-subtle rounded-3 p-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-warning text-dark fs-11">
                                                <i class="fas fa-lock me-1"></i> PRIVATE INTERNAL NOTE (Hidden from Customer)
                                            </span>
                                            <span class="fw-bold fs-10 text-1000">{{ $msg->author?->name ?? 'Staff Agent' }}</span>
                                        </div>
                                        <span class="text-secondary fs-11">{{ $msg->created_at->format('M d, Y h:i A') }} ({{ $msg->created_at->diffForHumans() }})</span>
                                    </div>
                                    <div class="fs-10 text-1000" style="white-space: pre-line;">{!! nl2br(e($msg->message)) !!}</div>

                                    @if($msg->attachments->isNotEmpty())
                                        <div class="mt-2 pt-2 border-top border-warning-subtle">
                                            <span class="fs-11 fw-semibold text-secondary d-block mb-1">Internal Attachments:</span>
                                            <div class="d-flex flex-wrap gap-2">
                                                @foreach($msg->attachments as $att)
                                                    <a href="{{ route('support.tickets.attachments.download', $att->id) }}" class="btn btn-sm btn-white border fs-11 d-inline-flex align-items-center" target="_blank">
                                                        <i class="{{ $att->file_icon }} me-1"></i>
                                                        <span class="me-2">{{ $att->file_name }}</span>
                                                        <i class="fas fa-download ms-2 text-warning fs-12"></i>
                                                    </a>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @else
                                {{-- Customer or Agent Public Message --}}
                                @php
                                    $isAgent = $msg->user_id !== $ticket->user_id;
                                @endphp
                                <div class="border rounded-3 p-3 mb-3 {{ $isAgent ? 'bg-primary-subtle border-primary-subtle' : 'bg-body-secondary border-light' }}">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar avatar-s">
                                                <div class="avatar-name rounded-circle {{ $isAgent ? 'bg-primary text-white' : 'bg-secondary text-white' }}">
                                                    {{ strtoupper(substr($msg->author?->name ?? 'U', 0, 1)) }}
                                                </div>
                                            </div>
                                            <div>
                                                <span class="fw-bold fs-10 text-1000">{{ $msg->author?->name ?? 'User' }}</span>
                                                @if($isAgent)
                                                    <span class="badge bg-primary text-white ms-1 fs-11">Support Agent</span>
                                                @else
                                                    <span class="badge bg-secondary text-white ms-1 fs-11">Customer</span>
                                                @endif
                                            </div>
                                        </div>
                                        <span class="text-secondary fs-11">{{ $msg->created_at->format('M d, Y h:i A') }} ({{ $msg->created_at->diffForHumans() }})</span>
                                    </div>
                                    <div class="fs-10 text-1000" style="white-space: pre-line;">{!! nl2br(e($msg->message)) !!}</div>

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
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Agent Reply & Internal Note Form --}}
            <div class="card">
                <div class="card-header bg-body-tertiary">
                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                        <li class="nav-item">
                            <button type="button" class="nav-link {{ $messageType === 'public' ? 'active fw-bold text-primary' : '' }}" wire:click="$set('messageType', 'public')">
                                <i class="fas fa-reply me-1"></i> Reply to Customer
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link {{ $messageType === 'internal' ? 'active fw-bold text-warning' : '' }}" wire:click="$set('messageType', 'internal')">
                                <i class="fas fa-lock me-1"></i> Add Private Internal Note
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body p-4">
                    @if($messageType === 'internal')
                        <div class="alert alert-warning py-2 fs-10 mb-3">
                            <i class="fas fa-info-circle me-1"></i> <strong>Staff Privacy Mode:</strong> This note is visible only to support agents and system administrators. It will NOT be emailed or shown to the customer.
                        </div>
                    @endif

                    <form wire:submit.prevent="submitReply">
                        <div class="mb-3">
                            <textarea wire:model="replyMessage" rows="5" class="form-control @error('replyMessage') is-invalid @enderror" placeholder="{{ $messageType === 'internal' ? 'Write internal investigation notes, debugging details, or steps taken...' : 'Compose your response to the customer...' }}"></textarea>
                            @error('replyMessage') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- If public reply, status transition dropdown --}}
                        @if($messageType === 'public')
                            <div class="row g-2 mb-3 align-items-center">
                                <div class="col-md-3">
                                    <label class="form-label fs-10 fw-semibold text-secondary mb-0">Set status upon reply:</label>
                                </div>
                                <div class="col-md-4">
                                    <select wire:model="newStatus" class="form-select form-select-sm">
                                        <option value="awaiting_customer">Awaiting Customer Reply</option>
                                        <option value="in_progress">In Progress</option>
                                        <option value="resolved">Resolved</option>
                                        <option value="closed">Closed</option>
                                    </select>
                                </div>
                            </div>
                        @endif

                        {{-- Attachments --}}
                        <div class="mb-3">
                            <label class="form-label fs-11 fw-semibold text-secondary">Upload Attachments (Optional)</label>
                            <input type="file" wire:model="attachments" multiple class="form-control form-control-sm @error('attachments.*') is-invalid @enderror">
                            @error('attachments.*') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror

                            <div wire:loading wire:target="attachments" class="text-primary fs-11 mt-1">
                                <i class="fas fa-spinner fa-spin me-1"></i> Uploading...
                            </div>

                            @if(!empty($attachments))
                                <div class="mt-2 d-flex flex-wrap gap-2">
                                    @foreach($attachments as $attIdx => $attF)
                                        <div class="badge bg-light text-dark border p-2 d-flex align-items-center">
                                            <i class="far fa-file me-2 text-primary"></i>
                                            <span class="text-truncate me-2" style="max-width: 180px;">{{ $attF->getClientOriginalName() }}</span>
                                            <button type="button" wire:click="removeAttachment({{ $attIdx }})" class="btn-close btn-close-sm" aria-label="Remove"></button>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <button type="submit" class="btn {{ $messageType === 'internal' ? 'btn-warning' : 'btn-primary' }} btn-sm" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="submitReply">
                                    <i class="fas {{ $messageType === 'internal' ? 'fa-lock' : 'fa-paper-plane' }} me-1"></i>
                                    {{ $messageType === 'internal' ? 'Save Private Note' : 'Send Reply to Customer' }}
                                </span>
                                <span wire:loading wire:target="submitReply"><i class="fas fa-spinner fa-spin me-1"></i> Submitting...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Right: Management Properties & Context --}}
        <div class="col-lg-4">
            {{-- Ticket Controls --}}
            <div class="card mb-3">
                <div class="card-header bg-body-tertiary">
                    <h6 class="mb-0 text-1000 fw-semibold"><i class="fas fa-cog me-2 text-primary"></i>Ticket Attributes</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fs-11 fw-semibold text-secondary">Assigned Agent</label>
                        <select wire:model.live="assignedTo" class="form-select form-select-sm">
                            <option value="">-- Unassigned (Support Pool) --</option>
                            @foreach($agents as $ag)
                                <option value="{{ $ag->id }}">{{ $ag->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fs-11 fw-semibold text-secondary">Ticket Status</label>
                        <select wire:model.live="currentStatus" class="form-select form-select-sm">
                            <option value="open">Open</option>
                            <option value="assigned">Assigned</option>
                            <option value="in_progress">In Progress</option>
                            <option value="awaiting_customer">Awaiting Customer</option>
                            <option value="awaiting_internal">Awaiting Staff</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                            <option value="reopened">Reopened</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fs-11 fw-semibold text-secondary">Priority Level</label>
                        <select wire:model.live="currentPriority" class="form-select form-select-sm">
                            <option value="urgent">Urgent</option>
                            <option value="high">High</option>
                            <option value="normal">Normal</option>
                            <option value="low">Low</option>
                        </select>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fs-11 fw-semibold text-secondary">Category</label>
                        <select wire:model.live="categoryId" class="form-select form-select-sm">
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Tenant & Customer Card --}}
            <div class="card mb-3">
                <div class="card-header bg-body-tertiary">
                    <h6 class="mb-0 text-1000 fw-semibold"><i class="fas fa-building me-2 text-info"></i>Customer Context</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0 fs-10">
                        <tbody>
                            <tr>
                                <th class="text-secondary ps-3 py-2">Marquee Business</th>
                                <td class="pe-3 py-2 fw-bold text-1000">{{ $ticket->marquee ? $ticket->marquee->name : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th class="text-secondary ps-3 py-2">Branch</th>
                                <td class="pe-3 py-2">{{ $ticket->branch?->name ?? 'All Branches / Head Office' }}</td>
                            </tr>
                            <tr>
                                <th class="text-secondary ps-3 py-2">Submitter</th>
                                <td class="pe-3 py-2">{{ $ticket->creator?->name }}</td>
                            </tr>
                            <tr>
                                <th class="text-secondary ps-3 py-2">Email</th>
                                <td class="pe-3 py-2 font-monospace">{{ $ticket->creator?->email }}</td>
                            </tr>
                            <tr>
                                <th class="text-secondary ps-3 py-2">Phone</th>
                                <td class="pe-3 py-2 font-monospace">{{ $ticket->creator?->phone ?? 'N/A' }}</td>
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
                            @if($ticket->satisfaction_rating)
                                <tr>
                                    <th class="text-secondary ps-3 py-2">Customer CSAT</th>
                                    <td class="pe-3 py-2 text-warning">
                                        @for($r = 1; $r <= 5; $r++)
                                            <i class="fas fa-star {{ $r <= $ticket->satisfaction_rating ? 'text-warning' : 'text-300' }}"></i>
                                        @endfor
                                        <span class="text-secondary ms-1">({{ $ticket->satisfaction_rating }}/5)</span>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Status Audit Log --}}
            <div class="card">
                <div class="card-header bg-body-tertiary">
                    <h6 class="mb-0 text-1000 fw-semibold"><i class="fas fa-list-alt me-2 text-secondary"></i>Status Audit Log</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush fs-10">
                        @forelse($ticket->statusHistories as $hist)
                            <li class="list-group-item px-3 py-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-semibold text-1000">
                                        {{ $hist->from_status ? ucfirst(str_replace('_', ' ', $hist->from_status)) . ' → ' : '' }}
                                        <span class="badge bg-light text-dark border">{{ ucfirst(str_replace('_', ' ', $hist->to_status)) }}</span>
                                    </span>
                                    <span class="text-secondary fs-11">{{ $hist->created_at->format('M d, H:i') }}</span>
                                </div>
                                <div class="text-secondary fs-11 mt-1">
                                    <i class="fas fa-user-edit me-1"></i> {{ $hist->user?->name ?? 'System' }}
                                    @if($hist->notes)
                                        <span class="fst-italic">— {{ $hist->notes }}</span>
                                    @endif
                                </div>
                            </li>
                        @empty
                            <li class="list-group-item text-secondary text-center py-3">No status changes recorded.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
