<div wire:poll.3s="refreshMessages">
    {{-- Header & Breadcrumbs --}}
    <div class="row mb-3">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('support.index') }}">Support &amp; Help</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Live Support Chat</li>
                </ol>
            </nav>
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0 text-1000 fw-bold">Live Support Chat</h4>
                    <p class="text-secondary fs-10 mb-0">Chat in real-time with technical specialists and SaaS onboarding engineers.</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fs-11">
                        <i class="fas fa-circle fs-12 me-1"></i> Support Desk Online
                    </span>
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

    <div class="card overflow-hidden" style="height: calc(100vh - 230px); min-height: 550px;">
        <div class="row g-0 h-100">
            {{-- Conversations Sidebar --}}
            <div class="col-md-4 col-lg-3 border-end h-100 d-flex flex-column bg-body-tertiary">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-1000">Conversations</h6>
                    <button type="button" wire:click="$set('activeConversationId', null)" class="btn btn-primary btn-xs">
                        <i class="fas fa-plus me-1"></i> New
                    </button>
                </div>

                <div class="flex-grow-1 overflow-auto p-2">
                    @forelse($conversations as $conv)
                        @php
                            $isSelected = $activeConversationId === $conv->id;
                            $unread = $conv->messages->where('sender_id', '!=', auth()->id())->where('is_read', false)->count();
                        @endphp
                        <div wire:click="$set('activeConversationId', {{ $conv->id }})" class="p-2 mb-1 rounded-3 cursor-pointer transition-all {{ $isSelected ? 'bg-primary-subtle border border-primary-subtle' : 'hover-bg-100' }}" role="button">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <span class="font-monospace fs-11 fw-bold text-primary">{{ $conv->conversation_number }}</span>
                                <span class="fs-12 text-secondary">{{ $conv->last_message_at ? $conv->last_message_at->diffForHumans(['short' => true]) : '' }}</span>
                            </div>
                            <div class="fw-semibold fs-10 text-1000 text-truncate mb-1">{{ $conv->subject ?: 'Support Conversation' }}</div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="{{ $conv->status_badge }} fs-12 px-1 py-0">{{ $conv->status_label }}</span>
                                @if($unread > 0)
                                    <span class="badge bg-danger rounded-pill fs-12">{{ $unread }}</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-secondary fs-11">
                            <i class="far fa-comments fa-2x mb-2 text-300"></i>
                            <p class="mb-0">No conversations yet.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Chat Conversation Pane --}}
            <div class="col-md-8 col-lg-9 h-100 d-flex flex-column">
                @if($activeConversation)
                    {{-- Active Chat Header --}}
                    <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-white">
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar avatar-m">
                                <div class="avatar-name rounded-circle bg-primary-subtle text-primary">
                                    <i class="fas fa-headset"></i>
                                </div>
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fw-bold text-1000 font-monospace">{{ $activeConversation->conversation_number }}</span>
                                    <span class="{{ $activeConversation->status_badge }} px-2 py-0 fs-11">{{ $activeConversation->status_label }}</span>
                                </div>
                                <small class="text-secondary">{{ $activeConversation->subject }}</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <a href="{{ route('support.tickets.create') }}" class="btn btn-outline-secondary btn-xs" title="Escalate to formal ticket">
                                <i class="fas fa-ticket-alt me-1"></i> Open Ticket
                            </a>
                            @if($activeConversation->status !== 'closed')
                                <button type="button" wire:click="closeChat" wire:confirm="Close this chat conversation?" class="btn btn-outline-danger btn-xs">
                                    <i class="fas fa-times me-1"></i> End Chat
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Messages Scroll Area --}}
                    <div class="flex-grow-1 overflow-auto p-3 bg-light d-flex flex-column gap-2" id="chatContainer">
                        @foreach($activeConversation->messages as $msg)
                            @php
                                $isMe = $msg->sender_id === auth()->id();
                            @endphp
                            <div class="d-flex {{ $isMe ? 'justify-content-end' : 'justify-content-start' }} mb-2">
                                <div class="d-flex flex-column {{ $isMe ? 'align-items-end' : 'align-items-start' }}" style="max-width: 75%;">
                                    <span class="fs-12 text-secondary mb-1">
                                        {{ $isMe ? 'You' : ($msg->sender?->name ?? 'Support Specialist') }} &bull; {{ $msg->created_at->format('h:i A') }}
                                    </span>
                                    <div class="p-3 rounded-3 shadow-none {{ $isMe ? 'bg-primary text-white' : 'bg-white text-dark border' }}" style="word-break: break-word;">
                                        {!! nl2br(e($msg->message)) !!}

                                        @if($msg->attachment_path)
                                            <div class="mt-2 pt-2 border-top {{ $isMe ? 'border-primary-subtle' : 'border-light' }}">
                                                <a href="{{ asset('storage/' . $msg->attachment_path) }}" target="_blank" class="{{ $isMe ? 'text-white' : 'text-primary' }} text-decoration-none fs-11 d-flex align-items-center">
                                                    <i class="fas fa-paperclip me-1"></i> {{ $msg->attachment_name ?: 'Attached File' }}
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Message Composer --}}
                    @if($activeConversation->status !== 'closed')
                        <div class="p-3 bg-white border-top">
                            <form wire:submit.prevent="sendMessage">
                                <div class="input-group">
                                    <input type="text" wire:model="newMessage" class="form-control" placeholder="Type your message and press Enter..." autofocus>
                                    <label class="btn btn-outline-secondary mb-0" for="chatAttachment" title="Attach image or file">
                                        <i class="fas fa-paperclip"></i>
                                    </label>
                                    <input type="file" id="chatAttachment" wire:model="attachment" class="d-none">
                                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                                        <span wire:loading.remove wire:target="sendMessage"><i class="fas fa-paper-plane"></i></span>
                                        <span wire:loading wire:target="sendMessage"><i class="fas fa-spinner fa-spin"></i></span>
                                    </button>
                                </div>
                                @if($attachment)
                                    <div class="badge bg-light text-dark border mt-2 p-1 px-2 d-inline-flex align-items-center">
                                        <i class="far fa-file me-1 text-primary"></i>
                                        <span class="text-truncate" style="max-width: 200px;">{{ $attachment->getClientOriginalName() }}</span>
                                        <button type="button" wire:click="$set('attachment', null)" class="btn-close btn-close-sm ms-2" aria-label="Remove"></button>
                                    </div>
                                @endif
                                @error('newMessage') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                            </form>
                        </div>
                    @else
                        <div class="p-3 bg-light border-top text-center text-secondary fs-11">
                            <i class="fas fa-lock me-1"></i> This conversation has ended. Click <strong>New</strong> in the left panel to start a fresh chat.
                        </div>
                    @endif
                @else
                    {{-- Start New Chat Welcome State --}}
                    <div class="h-100 d-flex align-items-center justify-content-center p-4">
                        <div class="card border-0 shadow-none text-center" style="max-width: 460px;">
                            <div class="avatar avatar-3xl mx-auto mb-3">
                                <div class="avatar-name rounded-circle bg-primary-subtle text-primary">
                                    <i class="fas fa-comments fs-2"></i>
                                </div>
                            </div>
                            <h5 class="fw-bold text-1000 mb-1">Start a Live Support Session</h5>
                            <p class="text-secondary fs-10 mb-4">Connect with a real-time specialist for urgent wedding-day software questions or configuration assistance.</p>

                            <form wire:submit.prevent="startNewChat" class="text-start">
                                <div class="mb-3">
                                    <label class="form-label fs-11 fw-semibold text-secondary">What do you need help with? <span class="text-danger">*</span></label>
                                    <input type="text" wire:model="newSubject" class="form-control @error('newSubject') is-invalid @enderror" placeholder="e.g. Issue printing kitchen order slips">
                                    @error('newSubject') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fs-11 fw-semibold text-secondary">Initial Message <span class="text-danger">*</span></label>
                                    <textarea wire:model="newMessage" rows="3" class="form-control @error('newMessage') is-invalid @enderror" placeholder="Describe the issue briefly..."></textarea>
                                    @error('newMessage') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                                        <span wire:loading.remove wire:target="startNewChat"><i class="fas fa-comment-dots me-1"></i> Start Conversation</span>
                                        <span wire:loading wire:target="startNewChat"><i class="fas fa-spinner fa-spin me-1"></i> Starting...</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

