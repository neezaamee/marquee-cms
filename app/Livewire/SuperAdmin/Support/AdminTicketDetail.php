<?php

namespace App\Livewire\SuperAdmin\Support;

use App\Models\SupportCategory;
use App\Models\SupportTicket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;

class AdminTicketDetail extends Component
{
    use WithFileUploads;

    public $ticketNumber;
    public $messageType = 'public'; // 'public' (customer reply) or 'internal' (private note)
    public $replyMessage = '';
    public $attachments = [];
    public $newStatus = '';

    // Management properties
    public $assignedTo;
    public $currentStatus;
    public $currentPriority;
    public $categoryId;

    protected function rules(): array
    {
        return [
            'replyMessage' => 'required|string|min:3|max:5000',
            'attachments.*' => 'nullable|file|max:10240|mimes:png,jpg,jpeg,pdf,doc,docx,zip,txt,csv,xlsx',
        ];
    }

    public function mount(string $ticketNumber)
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin() && !$user->hasPermission('support.ticket.manage_all')) {
            abort(403, 'Unauthorized access to admin ticket management.');
        }

        $this->ticketNumber = $ticketNumber;
        $ticket = $this->getTicket();

        $this->assignedTo = $ticket->assigned_to;
        $this->currentStatus = $ticket->status;
        $this->currentPriority = $ticket->priority;
        $this->categoryId = $ticket->category_id;
        $this->newStatus = $ticket->status === 'open' ? 'awaiting_customer' : $ticket->status;

        // Mark messages as read by agent
        $ticket->messages()
            ->where('is_agent_read', false)
            ->update(['is_agent_read' => true]);
    }

    protected function getTicket(): SupportTicket
    {
        return SupportTicket::withoutGlobalScopes()
            ->with([
                'marquee',
                'branch',
                'creator',
                'category',
                'assignedAgent',
                'messages.author',
                'messages.attachments',
                'statusHistories.user',
            ])
            ->where('ticket_number', $this->ticketNumber)
            ->firstOrFail();
    }

    public function updatedAssignedTo($value)
    {
        $ticket = $this->getTicket();
        $agentId = !empty($value) ? (int) $value : null;
        $ticket->update(['assigned_to' => $agentId]);

        $agentName = $agentId ? (User::find($agentId)?->name ?? 'Agent') : 'Unassigned';
        if ($ticket->status === 'open' && $agentId) {
            $ticket->transitionTo('assigned', "Assigned to {$agentName}", auth()->id());
            $this->currentStatus = 'assigned';
        } else {
            $ticket->statusHistories()->create([
                'user_id' => auth()->id(),
                'from_status' => $ticket->status,
                'to_status' => $ticket->status,
                'notes' => "Assigned agent changed to {$agentName}",
            ]);
        }

        session()->flash('success', "Assigned agent updated to {$agentName}.");
    }

    public function updatedCurrentStatus($value)
    {
        $ticket = $this->getTicket();
        $ticket->transitionTo($value, "Status updated by admin/agent", auth()->id());
        session()->flash('success', "Ticket status changed to " . ucfirst(str_replace('_', ' ', $value)) . ".");
    }

    public function updatedCurrentPriority($value)
    {
        $ticket = $this->getTicket();
        $ticket->update(['priority' => $value]);
        $ticket->statusHistories()->create([
            'user_id' => auth()->id(),
            'from_status' => $ticket->status,
            'to_status' => $ticket->status,
            'notes' => "Priority changed to " . ucfirst($value),
        ]);
        session()->flash('success', "Priority updated to " . ucfirst($value) . ".");
    }

    public function updatedCategoryId($value)
    {
        $ticket = $this->getTicket();
        $ticket->update(['category_id' => $value]);
        session()->flash('success', "Ticket category updated.");
    }

    public function removeAttachment($index)
    {
        if (isset($this->attachments[$index])) {
            unset($this->attachments[$index]);
            $this->attachments = array_values($this->attachments);
        }
    }

    public function submitReply()
    {
        $this->validate();

        $ticket = $this->getTicket();
        $user = Auth::user();
        $isInternal = $this->messageType === 'internal';

        DB::transaction(function () use ($ticket, $user, $isInternal) {
            // 1. Create ticket message
            $message = TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'message' => $this->replyMessage,
                'is_internal_note' => $isInternal,
                'is_customer_read' => $isInternal, // internal notes are considered read for customer
                'is_agent_read' => true,
            ]);

            // 2. Upload attachments
            if (!empty($this->attachments)) {
                foreach ($this->attachments as $file) {
                    $originalName = $file->getClientOriginalName();
                    $mimeType = $file->getMimeType();
                    $fileSize = $file->getSize();
                    $path = $file->store('support/attachments', 'local');

                    TicketAttachment::create([
                        'ticket_id' => $ticket->id,
                        'ticket_message_id' => $message->id,
                        'file_name' => $originalName,
                        'file_path' => $path,
                        'file_type' => $mimeType,
                        'file_size' => $fileSize,
                    ]);
                }
            }

            // 3. If public reply, record first response time if empty
            if (!$isInternal) {
                if (!$ticket->first_response_at) {
                    $ticket->update(['first_response_at' => Carbon::now()]);
                }

                // If admin selected new status, transition it
                if (!empty($this->newStatus) && $this->newStatus !== $ticket->status) {
                    $ticket->transitionTo($this->newStatus, "Status updated on agent reply", $user->id);
                    $this->currentStatus = $this->newStatus;
                }
            }
        });

        $this->replyMessage = '';
        $this->attachments = [];
        $statusMsg = $isInternal ? 'Private internal note added.' : 'Reply sent to customer.';
        session()->flash('success', $statusMsg);
    }

    public function render()
    {
        $ticket = $this->getTicket();
        $messages = $ticket->messages; // All messages including internal notes

        $categories = SupportCategory::where('is_active', true)->orderBy('display_order')->get();
        $agents = User::whereHas('role', fn($r) => $r->whereIn('name', ['super_admin', 'support_agent', 'admin']))
            ->orWhereNull('marquee_id')
            ->orderBy('name')
            ->get();

        if ($agents->isEmpty()) {
            $agents = User::where('status', 'active')->orderBy('name')->take(20)->get();
        }

        return view('livewire.super-admin.support.admin-ticket-detail', [
            'ticket' => $ticket,
            'messages' => $messages,
            'categories' => $categories,
            'agents' => $agents,
        ])->layout('layouts.admin', ['title' => "Manage Ticket {$ticket->ticket_number} — Desk Operations"]);
    }
}
