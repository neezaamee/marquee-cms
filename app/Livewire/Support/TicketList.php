<?php

namespace App\Livewire\Support;

use App\Models\SupportTicket;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class TicketList extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $status = 'all';
    public $priority = 'all';
    public $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => 'all'],
        'priority' => ['except' => 'all'],
        'page' => ['except' => 1],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function updatingPriority()
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = Auth::user();

        // Base query - automatically scoped by BelongsToTenant for non-super-admins
        $query = SupportTicket::with(['category', 'assignedAgent']);

        if (!empty($this->search)) {
            $term = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('ticket_number', 'LIKE', $term)
                  ->orWhere('subject', 'LIKE', $term)
                  ->orWhere('description', 'LIKE', $term);
            });
        }

        if ($this->status !== 'all') {
            $query->where('status', $this->status);
        }

        if ($this->priority !== 'all') {
            $query->where('priority', $this->priority);
        }

        $tickets = $query->latest()->paginate($this->perPage);

        // Metrics for summary cards
        $metricsBase = SupportTicket::query();
        $totalTickets = (clone $metricsBase)->count();
        $openTickets = (clone $metricsBase)->whereIn('status', ['open', 'assigned', 'in_progress', 'reopened'])->count();
        $awaitingResponse = (clone $metricsBase)->where('status', 'awaiting_customer')->count();
        $resolvedTickets = (clone $metricsBase)->whereIn('status', ['resolved', 'closed'])->count();

        return view('livewire.support.ticket-list', [
            'tickets' => $tickets,
            'totalTickets' => $totalTickets,
            'openTickets' => $openTickets,
            'awaitingResponse' => $awaitingResponse,
            'resolvedTickets' => $resolvedTickets,
        ])->layout('layouts.admin', ['title' => 'My Support Tickets']);
    }
}
