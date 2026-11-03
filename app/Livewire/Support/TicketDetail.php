<?php

namespace App\Livewire\Support;

use App\Models\SupportTicket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class TicketDetail extends Component
{
    use WithFileUploads;

    public $ticketNumber;
    public $replyMessage = '';
    public $attachments = [];

    // CSAT Feedback properties
    public $rating = null;
    public $feedbackComment = '';
    public $ratingSubmitted = false;

    // Reopen modal/reason
    public $reopenReason = '';

    protected function rules(): array
    {
        return [
            'replyMessage' => 'required|string|min:3|max:5000',
            'attachments.*' => 'nullable|file|max:10240|mimes:png,jpg,jpeg,pdf,doc,docx,zip,txt,csv,xlsx',
        ];
    }

    public function mount(string $ticketNumber)
    {
        $this->ticketNumber = $ticketNumber;
        $ticket = $this->getTicket();

        // Security check
        $user = Auth::user();
        if (!$user->isSuperAdmin() && $ticket->marquee_id && !$user->hasAccessToMarquee($ticket->marquee_id)) {
            abort(403, 'Unauthorized access to this support ticket.');
        }

        // Mark unread messages as read for customer
        $ticket->messages()
            ->where('is_internal_note', false)
            ->where('is_customer_read', false)
            ->update(['is_customer_read' => true]);

        if ($ticket->satisfaction_rating) {
            $this->rating = $ticket->satisfaction_rating;
            $this->feedbackComment = $ticket->satisfaction_comment;
            $this->ratingSubmitted = true;
        }
    }

    protected function getTicket(): SupportTicket
    {
        // Must bypass tenant scope to fetch model, then explicitly check access
        $ticket = SupportTicket::withoutGlobalScopes()
            ->with(['category', 'creator', 'assignedAgent', 'customerVisibleMessages.author', 'customerVisibleMessages.attachments', 'statusHistories.user'])
            ->where('ticket_number', $this->ticketNumber)
            ->firstOrFail();

        return $ticket;
    }

    public function removeAttachment($index)
    {
        if (isset($this->attachments[$index])) {
            unset($this->attachments[$index]);
            $this->attachments = array_values($this->attachments);
        }
    }

    public function sendReply()
    {
        $this->validate();

        $ticket = $this->getTicket();
        $user = Auth::user();

        // Check if ticket is closed and cannot be replied directly without reopening
        if ($ticket->status === 'closed') {
            session()->flash('error', 'This ticket is closed. Please reopen it or submit a new ticket.');
            return;
        }

        DB::transaction(function () use ($ticket, $user) {
            // 1. Create message
            $message = TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'message' => $this->replyMessage,
                'is_internal_note' => false,
                'is_customer_read' => true,
                'is_agent_read' => false,
            ]);

            // 2. Handle attachments
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

            // 3. Update status if it was awaiting customer or resolved
            if (in_array($ticket->status, ['awaiting_customer', 'resolved'])) {
                $ticket->transitionTo('open', 'Customer replied to ticket', $user->id);
            }
        });

        $this->replyMessage = '';
        $this->attachments = [];
        session()->flash('success', 'Your reply has been sent to our support desk.');
    }

    public function markResolved()
    {
        $ticket = $this->getTicket();
        $user = Auth::user();

        $ticket->transitionTo('resolved', 'Marked as resolved by customer', $user->id);
        session()->flash('success', 'Thank you! The ticket is marked as resolved. Please take a moment to rate our service.');
    }

    public function reopenTicket()
    {
        $ticket = $this->getTicket();
        $user = Auth::user();

        $notes = !empty($this->reopenReason) ? "Customer reopened: " . $this->reopenReason : "Ticket reopened by customer";
        $ticket->transitionTo('reopened', $notes, $user->id);

        $this->reopenReason = '';
        session()->flash('success', 'Ticket has been reopened. A support agent will assist you shortly.');
    }

    public function submitRating()
    {
        $this->validate([
            'rating' => 'required|integer|min:1|max:5',
            'feedbackComment' => 'nullable|string|max:1000',
        ]);

        $ticket = $this->getTicket();
        $ticket->update([
            'satisfaction_rating' => $this->rating,
            'satisfaction_comment' => $this->feedbackComment,
        ]);

        $this->ratingSubmitted = true;
        session()->flash('success', 'Thank you for your valuable feedback!');
    }

    public function render()
    {
        $ticket = $this->getTicket();
        $messages = $ticket->customerVisibleMessages;

        return view('livewire.support.ticket-detail', [
            'ticket' => $ticket,
            'messages' => $messages,
        ])->layout('layouts.admin', ['title' => "Ticket {$ticket->ticket_number} - {$ticket->subject}"]);
    }
}
