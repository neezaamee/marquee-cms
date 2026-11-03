<div wire:poll.3s="refreshInbox">
    {{-- Header & Breadcrumbs --}}
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Administration</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('support.index') }}">Support Operations</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Live Chat Inbox</li>
                    </ol>
                </nav>
                <h4 class="mb-0 text-1000 fw-bold">Support Desk Live Chat Inbox</h4>
            </div>
            <div>
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fs-11">
                    <i class="fas fa-satellite-dish me-1"></i> Live Stream Active (3s poll)
                </span>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card overflow-hidden" style="height: calc(100vh - 230px); min-height: 600px;">
        <div class="row g-0 h-100">
            {{-- Master Conversations Sidebar --}}
            <div class="col-md-4 col-lg-3 border-end h-100 d-flex flex-column bg-body-tertiary">
                {{-- Filters --}}
                <div class="p-2 border-bottom bg-white">
                    <div class="row g-1">
                        <div class="col-6">
                            <select wire:model.live="statusFilter" class="form-select form-select-sm fs-12">
                                <option value="active">Active Queue</option>
                                <option value="waiting_agent">Waiting for Staff</option>
                                <option value="waiting_customer">Waiting for Client</option>
                                <option value="closed">Closed</option>
                                <option value="all">All Chats</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <select wire:model.live="marqueeFilter" class="form-select form-select-sm fs-12">
                                <option value="">All Marquees</option>
                                @foreach($marquees as $mq)
                                    <option value="{{ $mq->id }}">{{ Str::limit($mq->name, 16) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Conversations List --}}
                <div class="flex-grow-1 overflow-auto p-2">
                    @forelse($conversations as $c)
                        @php
                            $isSelected = $activeConversationId === $c->id;
                            $unread = $c->messages->where('sender_id', '!=', auth()->id())->where('is_read', false)->count();
                        @endphp
                        <div wire:click="selectConversation({{ $c->id }})" class="p-2 mb-1 rounded-3 cursor-pointer transition-all {{ $isSelected ? 'bg-primary-subtle border border-primary-subtle' : 'hover-bg-100 bg-white border border-100' }}" role="button">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <span class="font-monospace fs-12 fw-bold text-primary">{{ $c->conversation_number }}</span>
                                <span class="fs-12 text-secondary">{{ $c->last_message_at ? $c->last_message_at->diffForHumans(['short' => true]) : '' }}</span>
                            </div>
                            <div class="fw-bold fs-11 text-1000 text-truncate">{{ $c->marquee?->name ?? 'General Tenant' }}</div>
                            <div class="fs-12 text-secondary text-truncate mb-1">{{ $c->customer?->name }} &bull; {{ $c->subject ?: 'Chat Inquiry' }}</div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="{{ $c->status_badge }} fs-12 px-1 py-0">{{ $c->status_label }}</span>
                                @if($unread > 0)
                                    <span class="badge bg-danger rounded-pill fs-12">{{ $unread }} new</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5 text-secondary fs-11">
                            <i class="far fa-inbox fa-2x mb-2 text-300"></i>
                            <p class="mb-0">No chats in selected filter.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Right Pane: Conversation & Actions --}}
            <div class="col-md-8 col-lg-9 h-100 d-flex flex-column bg-white">
                @if($activeConversation)
                    {{-- Header with Agent Controls and Convert to Ticket --}}
                    <div class="p-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2 bg-light">
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="font-monospace fw-bold text-primary fs-10">{{ $activeConversation->conversation_number }}</span>
                                <span class="{{ $activeConversation->status_badge }} px-2 py-0 fs-11">{{ $activeConversation->status_label }}</span>
                                <span class="fw-bold text-1000">{{ $activeConversation->marquee?->name ?? 'Platform' }}</span>
                                <span class="text-secondary fs-11">({{ $activeConversation->customer?->name }})</span>
                            </div>
                            <div class="fs-11 text-secondary mt-1">
                                <strong>Subject:</strong> {{ $activeConversation->subject ?: 'Live Technical Support' }}
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            {{-- Assign Agent --}}
                            <select wire:change="assignAgent({{ $activeConversation->id }}, $event.target.value)" class="form-select form-select-sm py-0 fs-11" style="width: auto;">
                                <option value="">-- Assign Specialist --</option>
                                @foreach($agents as $ag)
                                    <option value="{{ $ag->id }}" {{ $activeConversation->assigned_to == $ag->id ? 'selected' : '' }}>
                                        {{ $ag->name }}
                                    </option>
                                @endforeach
                            </select>

                            {{-- Convert to Ticket CTA --}}
                            @if(!$activeConversation->converted_ticket_id)
                                <button type="button" wire:click="convertToTicket" wire:confirm="Convert this entire chat conversation to a formal Support Ticket?" class="btn btn-warning btn-xs" title="Convert to formal ticket">
                                    <i class="fas fa-ticket-alt me-1"></i> Convert to Ticket
                                </button>
                            @else
                                <a href="{{ route('admin.support.tickets.show', $activeConversation->convertedTicket->ticket_number) }}" class="btn btn-outline-info btn-xs">
                                    <i class="fas fa-external-link-alt me-1"></i> View Ticket #{{ $activeConversation->convertedTicket->ticket_number }}
                                </a>
                            @endif
                        </div>
                    </div>

                    {{-- Messages Thread Container --}}
                    <div class="flex-grow-1 overflow-auto p-3 bg-light d-flex flex-column gap-2" id="adminChatContainer">
                        @foreach($activeConversation->messages as $m)
                            @php
                                $isStaff = $m->isSenderStaff() || $m->sender_id === auth()->id();
                            @endphp
                            <div class="d-flex {{ $isStaff ? 'justify-content-end' : 'justify-content-start' }} mb-2">
                                <div class="d-flex flex-column {{ $isStaff ? 'align-items-end' : 'align-items-start' }}" style="max-width: 75%;">
                                    <span class="fs-12 text-secondary mb-1">
                                        {{ $isStaff ? ($m->sender?->name ?? 'Support Agent') : ($activeConversation->customer?->name ?? 'Customer') }} &bull; {{ $m->created_at->format('h:i A') }}
                                    </span>
                                    <div class="p-3 rounded-3 shadow-none {{ $isStaff ? 'bg-primary text-white' : 'bg-white text-dark border' }}" style="word-break: break-word;">
                                        {!! nl2br(e($m->message)) !!}

                                        @if($m->attachment_path)
                                            <div class="mt-2 pt-2 border-top {{ $isStaff ? 'border-primary-subtle' : 'border-light' }}">
                                                <a href="{{ asset('storage/' . $m->attachment_path) }}" target="_blank" class="{{ $isStaff ? 'text-white' : 'text-primary' }} text-decoration-none fs-11 d-flex align-items-center">
                                                    <i class="fas fa-paperclip me-1"></i> {{ $m->attachment_name ?: 'Attachment' }}
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Staff Composer --}}
                    <div class="p-3 bg-white border-top">
                        <form wire:submit.prevent="sendReply">
                            <div class="input-group">
                                <input type="text" wire:model="replyMessage" class="form-control" placeholder="Type staff response and press Enter..." autofocus>
                                <label class="btn btn-outline-secondary mb-0" for="adminChatAttachment" title="Attach file or screenshot">
                                    <i class="fas fa-paperclip"></i>
                                </label>
                                <input type="file" id="adminChatAttachment" wire:model="attachment" class="d-none">
                                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="sendReply"><i class="fas fa-paper-plane me-1"></i> Send</span>
                                    <span wire:loading wire:target="sendReply"><i class="fas fa-spinner fa-spin me-1"></i> Sending...</span>
                                </button>
                            </div>
                            @if($attachment)
                                <div class="badge bg-light text-dark border mt-2 p-1 px-2 d-inline-flex align-items-center">
                                    <i class="far fa-file me-1 text-primary"></i>
                                    <span class="text-truncate" style="max-width: 200px;">{{ $attachment->getClientOriginalName() }}</span>
                                    <button type="button" wire:click="$set('attachment', null)" class="btn-close btn-close-sm ms-2" aria-label="Remove"></button>
                                </div>
                            @endif
                            @error('replyMessage') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                        </form>
                    </div>
                @else
                    <div class="h-100 d-flex align-items-center justify-content-center p-4 text-center text-secondary">
                        <div>
                            <i class="fas fa-inbox fa-3x mb-3 text-300"></i>
                            <h5>Select a conversation</h5>
                            <p class="fs-10 text-muted">Choose a chat from the left sidebar to start responding to customer inquiries.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
