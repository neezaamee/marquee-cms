<?php

namespace App\Livewire\SuperAdmin\Support;

use App\Models\ChatMessage;
use App\Models\Marquee;
use App\Models\SupportCategory;
use App\Models\SupportConversation;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;

class ChatInbox extends Component
{
    use WithFileUploads;

    public $activeConversationId;
    public $statusFilter = 'active'; // 'active', 'waiting_agent', 'waiting_customer', 'closed', 'all'
    public $marqueeFilter = '';
    public $replyMessage = '';
    public $attachment;

    public function mount()
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin() && !$user->hasPermission('support.chat.manage')) {
            abort(403, 'Unauthorized access to Support Chat Inbox.');
        }

        // Default to first active conversation if present
        $first = $this->getConversationsQuery()->first();
        if ($first) {
            $this->activeConversationId = $first->id;
            $this->markAsRead();
        }
    }

    public function refreshInbox()
    {
        if ($this->activeConversationId) {
            $this->markAsRead();
        }
    }

    protected function markAsRead()
    {
        if (!$this->activeConversationId) {
            return;
        }

        ChatMessage::where('conversation_id', $this->activeConversationId)
            ->where('sender_id', '!=', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    public function selectConversation(int $id)
    {
        $this->activeConversationId = $id;
        $this->markAsRead();
    }

    public function assignAgent(int $conversationId, ?int $agentId)
    {
        $conversation = SupportConversation::withoutGlobalScopes()->findOrFail($conversationId);
        $conversation->update(['assigned_to' => $agentId ?: null]);

        $agent = $agentId ? User::find($agentId)?->name : 'Unassigned';
        session()->flash('success', "Conversation assigned to {$agent}.");
    }

    public function sendReply()
    {
        $this->validate([
            'replyMessage' => 'required|string|min:1|max:2000',
            'attachment' => 'nullable|file|max:5120|mimes:png,jpg,jpeg,pdf,doc,docx,zip',
        ]);

        if (!$this->activeConversationId) {
            return;
        }

        $conversation = SupportConversation::withoutGlobalScopes()->findOrFail($this->activeConversationId);
        $user = Auth::user();

        $attachmentPath = null;
        $attachmentName = null;
        if ($this->attachment) {
            $attachmentName = $this->attachment->getClientOriginalName();
            $attachmentPath = $this->attachment->store('support/attachments', 'local');
        }

        ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'message' => $this->replyMessage,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'is_read' => false,
        ]);

        $conversation->update([
            'status' => 'waiting_customer',
            'assigned_to' => $conversation->assigned_to ?: $user->id,
            'last_message_at' => Carbon::now(),
        ]);

        $this->replyMessage = '';
        $this->attachment = null;
    }

    /**
     * Convert active chat to a formal Support Ticket
     */
    public function convertToTicket()
    {
        if (!$this->activeConversationId) {
            return;
        }

        $conversation = SupportConversation::withoutGlobalScopes()
            ->with(['messages.sender', 'customer', 'marquee'])
            ->findOrFail($this->activeConversationId);

        $user = Auth::user();

        $ticket = DB::transaction(function () use ($conversation, $user) {
            $category = SupportCategory::where('is_active', true)->first();
            $ticketNumber = SupportTicket::generateTicketNumber();

            // Build transcript of the conversation
            $transcript = "=== CHAT CONVERSATION TRANSCRIPT ({$conversation->conversation_number}) ===\n";
            $transcript .= "Customer: " . ($conversation->customer?->name ?? 'User') . "\n";
            $transcript .= "Business: " . ($conversation->marquee?->name ?? 'General') . "\n";
            $transcript .= "Started At: " . $conversation->created_at->format('Y-m-d H:i') . "\n\n";

            foreach ($conversation->messages as $m) {
                $senderName = $m->sender?->name ?? 'User';
                $roleTag = $m->isSenderStaff() ? '[Support]' : '[Customer]';
                $transcript .= "[{$m->created_at->format('H:i')}] {$roleTag} {$senderName}: {$m->message}\n";
            }

            // Create Ticket
            $ticket = SupportTicket::create([
                'ticket_number' => $ticketNumber,
                'marquee_id' => $conversation->marquee_id,
                'user_id' => $conversation->user_id,
                'assigned_to' => $conversation->assigned_to ?: $user->id,
                'category_id' => $category?->id,
                'subject' => 'Converted from Chat: ' . ($conversation->subject ?: $conversation->conversation_number),
                'description' => $transcript,
                'priority' => 'normal',
                'status' => 'open',
                'sla_due_at' => SupportTicket::calculateSlaTarget('normal'),
            ]);

            // Initial ticket message
            TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => $conversation->user_id,
                'message' => $transcript,
                'is_internal_note' => false,
                'is_customer_read' => true,
                'is_agent_read' => true,
            ]);

            // Ticket history
            $ticket->statusHistories()->create([
                'user_id' => $user->id,
                'from_status' => null,
                'to_status' => 'open',
                'notes' => "Converted from live chat {$conversation->conversation_number}",
            ]);

            // Close chat and link ticket
            $conversation->update([
                'converted_ticket_id' => $ticket->id,
                'status' => 'closed',
                'closed_at' => Carbon::now(),
            ]);

            // Add system closure message to chat
            ChatMessage::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $user->id,
                'message' => "This conversation has been converted to Support Ticket #{$ticket->ticket_number}. Further updates will be handled via the ticket portal.",
                'is_read' => true,
            ]);

            return $ticket;
        });

        session()->flash('success', "Chat converted to Support Ticket {$ticket->ticket_number} successfully!");
        return redirect()->route('admin.support.tickets.show', $ticket->ticket_number);
    }

    protected function getConversationsQuery()
    {
        $query = SupportConversation::withoutGlobalScopes()
            ->with(['marquee', 'customer', 'agent', 'latestMessage']);

        if ($this->statusFilter === 'active') {
            $query->whereIn('status', ['active', 'waiting_agent', 'waiting_customer']);
        } elseif ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if (!empty($this->marqueeFilter)) {
            $query->where('marquee_id', $this->marqueeFilter);
        }

        return $query->latest('last_message_at');
    }

    public function render()
    {
        $conversations = $this->getConversationsQuery()->get();

        $activeConversation = $this->activeConversationId
            ? SupportConversation::withoutGlobalScopes()
                ->with(['messages.sender', 'customer', 'marquee', 'agent'])
                ->find($this->activeConversationId)
            : null;

        $marquees = Marquee::orderBy('name')->get();
        $agents = User::whereHas('role', fn($r) => $r->whereIn('name', ['super_admin', 'support_agent', 'admin']))
            ->orWhereNull('marquee_id')
            ->orderBy('name')
            ->get();

        if ($agents->isEmpty()) {
            $agents = User::where('status', 'active')->orderBy('name')->take(20)->get();
        }

        return view('livewire.super-admin.support.chat-inbox', [
            'conversations' => $conversations,
            'activeConversation' => $activeConversation,
            'marquees' => $marquees,
            'agents' => $agents,
        ])->layout('layouts.admin', ['title' => 'Support Live Chat Inbox — Operations']);
    }
}
