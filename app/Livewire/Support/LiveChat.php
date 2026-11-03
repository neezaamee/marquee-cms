<?php

namespace App\Livewire\Support;

use App\Models\ChatMessage;
use App\Models\SupportConversation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class LiveChat extends Component
{
    use WithFileUploads;

    public $activeConversationId;
    public $newMessage = '';
    public $attachment;
    public $newSubject = '';

    protected function rules(): array
    {
        return [
            'newMessage' => 'required|string|min:1|max:2000',
            'attachment' => 'nullable|file|max:5120|mimes:png,jpg,jpeg,pdf,doc,docx,zip',
        ];
    }

    public function mount()
    {
        $user = Auth::user();
        if (!$user->hasPermission('support.chat.use') && !$user->isSuperAdmin()) {
            abort(403, 'Access denied to live support chat.');
        }

        // Find recent active conversation for current marquee/user
        $activeChat = SupportConversation::where('user_id', $user->id)
            ->whereIn('status', ['active', 'waiting_customer', 'waiting_agent'])
            ->latest('last_message_at')
            ->first();

        if ($activeChat) {
            $this->activeConversationId = $activeChat->id;
            $this->markMessagesAsRead();
        }
    }

    public function refreshMessages()
    {
        if ($this->activeConversationId) {
            $this->markMessagesAsRead();
        }
    }

    protected function markMessagesAsRead()
    {
        if (!$this->activeConversationId) {
            return;
        }

        $userId = Auth::id();
        ChatMessage::where('conversation_id', $this->activeConversationId)
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    public function startNewChat()
    {
        $this->validate([
            'newSubject' => 'required|string|min:3|max:150',
            'newMessage' => 'required|string|min:1|max:2000',
        ]);

        $user = Auth::user();
        $conversationNumber = SupportConversation::generateConversationNumber();

        $conversation = SupportConversation::create([
            'conversation_number' => $conversationNumber,
            'marquee_id' => $user->getActiveMarqueeId(),
            'user_id' => $user->id,
            'subject' => $this->newSubject,
            'status' => 'waiting_agent',
            'last_message_at' => Carbon::now(),
        ]);

        $attachmentPath = null;
        $attachmentName = null;
        if ($this->attachment) {
            $attachmentName = $this->attachment->getClientOriginalName();
            $attachmentPath = $this->attachment->store('support/attachments', 'local');
        }

        ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'message' => $this->newMessage,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'is_read' => false,
        ]);

        $this->activeConversationId = $conversation->id;
        $this->newSubject = '';
        $this->newMessage = '';
        $this->attachment = null;

        session()->flash('success', 'Conversation started! A support specialist will respond shortly.');
    }

    public function sendMessage()
    {
        $this->validate();

        if (!$this->activeConversationId) {
            return;
        }

        $conversation = SupportConversation::withoutGlobalScopes()->findOrFail($this->activeConversationId);
        $user = Auth::user();

        // Check authorization
        if (!$user->isSuperAdmin() && $conversation->user_id !== $user->id) {
            abort(403, 'Unauthorized conversation access.');
        }

        $attachmentPath = null;
        $attachmentName = null;
        if ($this->attachment) {
            $attachmentName = $this->attachment->getClientOriginalName();
            $attachmentPath = $this->attachment->store('support/attachments', 'local');
        }

        ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'message' => $this->newMessage,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'is_read' => false,
        ]);

        $conversation->update([
            'status' => 'waiting_agent',
            'last_message_at' => Carbon::now(),
        ]);

        $this->newMessage = '';
        $this->attachment = null;
    }

    public function closeChat()
    {
        if (!$this->activeConversationId) {
            return;
        }

        $conversation = SupportConversation::withoutGlobalScopes()->findOrFail($this->activeConversationId);
        $user = Auth::user();

        if (!$user->isSuperAdmin() && $conversation->user_id !== $user->id) {
            abort(403, 'Unauthorized conversation access.');
        }

        $conversation->update([
            'status' => 'closed',
            'closed_at' => Carbon::now(),
        ]);

        session()->flash('success', 'Conversation closed. You can start a new chat anytime.');
    }

    public function render()
    {
        $user = Auth::user();

        // History of conversations for current user
        $conversations = SupportConversation::where('user_id', $user->id)
            ->with(['latestMessage', 'agent'])
            ->latest('last_message_at')
            ->get();

        $activeConversation = $this->activeConversationId
            ? SupportConversation::with(['messages.sender', 'agent'])->find($this->activeConversationId)
            : null;

        return view('livewire.support.live-chat', [
            'conversations' => $conversations,
            'activeConversation' => $activeConversation,
        ])->layout('layouts.admin', ['title' => 'Live Support Chat']);
    }
}
