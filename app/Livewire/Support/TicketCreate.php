<?php

namespace App\Livewire\Support;

use App\Models\SupportCategory;
use App\Models\SupportTicket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class TicketCreate extends Component
{
    use WithFileUploads;

    public $subject = '';
    public $category_id = '';
    public $priority = 'normal';
    public $related_module = '';
    public $related_record_id = '';
    public $description = '';
    public $attachments = [];

    protected function rules(): array
    {
        return [
            'subject' => 'required|string|min:5|max:255',
            'category_id' => 'required|exists:support_categories,id',
            'priority' => 'required|in:low,normal,high,urgent',
            'related_module' => 'nullable|string|max:50',
            'related_record_id' => 'nullable|numeric',
            'description' => 'required|string|min:15|max:10000',
            'attachments.*' => 'nullable|file|max:10240|mimes:png,jpg,jpeg,pdf,doc,docx,zip,txt,csv,xlsx',
        ];
    }

    protected $messages = [
        'category_id.required' => 'Please select a support category.',
        'subject.required' => 'A ticket subject is required.',
        'description.min' => 'Please provide detailed information (at least 15 characters).',
        'attachments.*.max' => 'Each attachment must not exceed 10 megabytes.',
    ];

    public function mount()
    {
        $firstCategory = SupportCategory::where('is_active', true)->orderBy('display_order')->first();
        if ($firstCategory) {
            $this->category_id = $firstCategory->id;
        }
    }

    public function removeAttachment($index)
    {
        if (isset($this->attachments[$index])) {
            unset($this->attachments[$index]);
            $this->attachments = array_values($this->attachments);
        }
    }

    public function submit()
    {
        $this->validate();

        $user = Auth::user();
        $ticketNumber = SupportTicket::generateTicketNumber();
        $slaDueAt = SupportTicket::calculateSlaTarget($this->priority);

        $ticket = DB::transaction(function () use ($user, $ticketNumber, $slaDueAt) {
            // 1. Create Support Ticket
            $ticket = SupportTicket::create([
                'ticket_number' => $ticketNumber,
                'marquee_id' => $user->getActiveMarqueeId(),
                'branch_id' => $user->branch_id,
                'user_id' => $user->id,
                'category_id' => $this->category_id,
                'subject' => $this->subject,
                'description' => $this->description,
                'priority' => $this->priority,
                'status' => 'open',
                'related_module' => !empty($this->related_module) ? $this->related_module : null,
                'related_record_id' => !empty($this->related_record_id) ? (int) $this->related_record_id : null,
                'sla_due_at' => $slaDueAt,
            ]);

            // 2. Create Initial Message
            $message = TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'message' => $this->description,
                'is_internal_note' => false,
                'is_customer_read' => true,
                'is_agent_read' => false,
            ]);

            // 3. Handle File Attachments
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

            // 4. Record Initial Status History
            $ticket->statusHistories()->create([
                'user_id' => $user->id,
                'from_status' => null,
                'to_status' => 'open',
                'notes' => 'Ticket created by user',
            ]);

            return $ticket;
        });

        session()->flash('success', "Ticket {$ticket->ticket_number} created successfully! Our support team has been notified.");

        return redirect()->route('support.tickets.show', $ticket->ticket_number);
    }

    public function render()
    {
        $categories = SupportCategory::where('is_active', true)
            ->orderBy('display_order')
            ->get();

        return view('livewire.support.ticket-create', [
            'categories' => $categories,
        ])->layout('layouts.admin', ['title' => 'Submit Support Ticket']);
    }
}
