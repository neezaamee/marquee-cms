<?php

namespace App\Livewire\SuperAdmin\Support;

use App\Models\Marquee;
use App\Models\SupportCategory;
use App\Models\SupportTicket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class AdminTicketQueue extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $marqueeFilter = '';
    public $statusFilter = 'all';
    public $priorityFilter = 'all';
    public $agentFilter = 'all';
    public $categoryFilter = 'all';
    public $slaFilter = 'all';
    public $perPage = 15;

    public $selectedTickets = [];
    public $selectAll = false;
    public $bulkAction = '';
    public $bulkAgentId = '';
    public $bulkStatus = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'marqueeFilter' => ['except' => ''],
        'statusFilter' => ['except' => 'all'],
        'priorityFilter' => ['except' => 'all'],
        'agentFilter' => ['except' => 'all'],
        'categoryFilter' => ['except' => 'all'],
        'slaFilter' => ['except' => 'all'],
        'page' => ['except' => 1],
    ];

    public function mount()
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin() && !$user->hasPermission('support.ticket.manage_all')) {
            abort(403, 'Access denied to Support Operations Management Queue.');
        }
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedTickets = $this->getCurrentTicketIds();
        } else {
            $this->selectedTickets = [];
        }
    }

    protected function getCurrentTicketIds(): array
    {
        return $this->getTicketsQuery()->pluck('id')->map(fn($id) => (string) $id)->toArray();
    }

    public function updatingSearch() { $this->resetPage(); }
    public function updatingMarqueeFilter() { $this->resetPage(); }
    public function updatingStatusFilter() { $this->resetPage(); }
    public function updatingPriorityFilter() { $this->resetPage(); }
    public function updatingAgentFilter() { $this->resetPage(); }
    public function updatingCategoryFilter() { $this->resetPage(); }
    public function updatingSlaFilter() { $this->resetPage(); }

    public function assignAgent(int $ticketId, ?int $agentId = null)
    {
        $ticket = SupportTicket::withoutGlobalScopes()->findOrFail($ticketId);
        $oldAgent = $ticket->assigned_to;
        $ticket->update(['assigned_to' => $agentId ?: null]);

        $agentName = $agentId ? (User::find($agentId)?->name ?? 'Agent') : 'Unassigned';
        if ($ticket->status === 'open' && $agentId) {
            $ticket->transitionTo('assigned', "Ticket assigned to {$agentName}");
        } else {
            $ticket->statusHistories()->create([
                'user_id' => auth()->id(),
                'from_status' => $ticket->status,
                'to_status' => $ticket->status,
                'notes' => "Assigned agent changed to {$agentName}",
            ]);
        }

        session()->flash('success', "Ticket {$ticket->ticket_number} assigned to {$agentName}.");
    }

    public function changeStatus(int $ticketId, string $newStatus)
    {
        $ticket = SupportTicket::withoutGlobalScopes()->findOrFail($ticketId);
        $ticket->transitionTo($newStatus, "Status updated by admin/agent", auth()->id());

        session()->flash('success', "Ticket {$ticket->ticket_number} status changed to " . ucfirst(str_replace('_', ' ', $newStatus)) . ".");
    }

    public function executeBulkAction()
    {
        if (empty($this->selectedTickets)) {
            session()->flash('error', 'Please select at least one ticket to perform bulk actions.');
            return;
        }

        $tickets = SupportTicket::withoutGlobalScopes()->whereIn('id', $this->selectedTickets)->get();

        if ($this->bulkAction === 'assign' && !empty($this->bulkAgentId)) {
            $agent = User::find($this->bulkAgentId);
            $agentName = $agent ? $agent->name : 'Agent';
            foreach ($tickets as $t) {
                $t->update(['assigned_to' => $this->bulkAgentId]);
                if ($t->status === 'open') {
                    $t->transitionTo('assigned', "Bulk assigned to {$agentName}", auth()->id());
                }
            }
            session()->flash('success', count($tickets) . " ticket(s) assigned to {$agentName}.");
        } elseif ($this->bulkAction === 'status' && !empty($this->bulkStatus)) {
            foreach ($tickets as $t) {
                $t->transitionTo($this->bulkStatus, "Bulk status updated to {$this->bulkStatus}", auth()->id());
            }
            session()->flash('success', count($tickets) . " ticket(s) updated to status " . ucfirst(str_replace('_', ' ', $this->bulkStatus)) . ".");
        } elseif ($this->bulkAction === 'close') {
            foreach ($tickets as $t) {
                $t->transitionTo('closed', "Bulk closed by admin", auth()->id());
            }
            session()->flash('success', count($tickets) . " ticket(s) closed.");
        }

        $this->selectedTickets = [];
        $this->selectAll = false;
        $this->bulkAction = '';
        $this->bulkAgentId = '';
        $this->bulkStatus = '';
    }

    protected function getTicketsQuery()
    {
        // Super Admins queue bypasses tenant scope to see all marquee tickets
        $query = SupportTicket::withoutGlobalScopes()
            ->with(['marquee', 'branch', 'creator', 'category', 'assignedAgent']);

        if (!empty($this->search)) {
            $term = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('ticket_number', 'LIKE', $term)
                  ->orWhere('subject', 'LIKE', $term)
                  ->orWhere('description', 'LIKE', $term)
                  ->orWhereHas('creator', fn($u) => $u->where('name', 'LIKE', $term)->orWhere('email', 'LIKE', $term))
                  ->orWhereHas('marquee', fn($m) => $m->where('name', 'LIKE', $term));
            });
        }

        if (!empty($this->marqueeFilter)) {
            $query->where('marquee_id', $this->marqueeFilter);
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->priorityFilter !== 'all') {
            $query->where('priority', $this->priorityFilter);
        }

        if ($this->agentFilter === 'unassigned') {
            $query->whereNull('assigned_to');
        } elseif ($this->agentFilter !== 'all') {
            $query->where('assigned_to', $this->agentFilter);
        }

        if ($this->categoryFilter !== 'all') {
            $query->where('category_id', $this->categoryFilter);
        }

        if ($this->slaFilter === 'breached') {
            $query->where(function ($q) {
                $q->where('is_sla_breached', true)
                  ->orWhere(function ($sub) {
                      $sub->whereNotIn('status', ['resolved', 'closed'])
                          ->whereNotNull('sla_due_at')
                          ->where('sla_due_at', '<', Carbon::now());
                  });
            });
        }

        return $query;
    }

    public function render()
    {
        $tickets = $this->getTicketsQuery()->latest()->paginate($this->perPage);

        // Calculate KPI Metrics across all tickets
        $allBase = SupportTicket::withoutGlobalScopes();
        $totalOpen = (clone $allBase)->whereIn('status', ['open', 'assigned', 'in_progress', 'awaiting_internal', 'reopened'])->count();
        $unassignedCount = (clone $allBase)->whereIn('status', ['open', 'assigned', 'in_progress', 'reopened'])->whereNull('assigned_to')->count();
        $slaBreachedCount = (clone $allBase)->whereNotIn('status', ['resolved', 'closed'])
            ->where(function ($q) {
                $q->where('is_sla_breached', true)
                  ->orWhere('sla_due_at', '<', Carbon::now());
            })->count();
        $resolvedToday = (clone $allBase)->whereDate('resolved_at', Carbon::today())->count();

        // Dropdown reference lists
        $marquees = Marquee::orderBy('name')->get();
        $categories = SupportCategory::where('is_active', true)->orderBy('display_order')->get();
        $agents = User::whereHas('role', fn($r) => $r->whereIn('name', ['super_admin', 'support_agent', 'admin']))
            ->orWhereNull('marquee_id')
            ->orderBy('name')
            ->get();

        if ($agents->isEmpty()) {
            $agents = User::where('status', 'active')->orderBy('name')->take(20)->get();
        }

        return view('livewire.super-admin.support.admin-ticket-queue', [
            'tickets' => $tickets,
            'totalOpen' => $totalOpen,
            'unassignedCount' => $unassignedCount,
            'slaBreachedCount' => $slaBreachedCount,
            'resolvedToday' => $resolvedToday,
            'marquees' => $marquees,
            'categories' => $categories,
            'agents' => $agents,
        ])->layout('layouts.admin', ['title' => 'Support Desk Queue — Operations']);
    }
}
