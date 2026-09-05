<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\EventType;
use App\Models\Hall;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Marquee;
use App\Models\Slot;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadManager extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // View Mode: 'table' or 'kanban'
    public $viewMode = 'table';

    // Filter & Search state
    public $search = '';

    public $filterStatus = '';

    public $filterPriority = '';

    public $filterSource = '';

    public $filterBranch = '';

    public $filterHall = '';

    public $filterDateStart = '';

    public $filterDateEnd = '';

    public $filterQuickShortcut = 'all';

    // Create / Edit Modal State
    public $showLeadModal = false;

    public $leadId = null;

    public $name = '';

    public $phone = '';

    public $alternate_phone = '';

    public $email = '';

    public $city = 'Lahore';

    public $branch_id = null;

    public $event_type_id = null;

    public $preferred_date = '';

    public $alternate_date = '';

    public $slot_id = null;

    public $hall_id = null;

    public $guest_count = null;

    public $estimated_budget = null;

    public $lead_source = 'walk_in';

    public $status = 'new';

    public $priority = 'warm';

    public $follow_up_date = '';

    public $notes = '';

    public $assigned_to = null;

    // Follow-up Activity Modal State
    public $showActivityModal = false;

    public ?Lead $selectedLead = null;

    public $activityType = 'call';

    public $activityNotes = '';

    public $activityFollowUpDate = '';

    // Mark as Lost Modal State
    public $showLostModal = false;

    public $lostLeadId = null;

    public $lostReason = 'chose_competitor';

    public $lostNotes = '';

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'phone' => ['required', 'string', 'regex:/^(03\d{2}-\d{7}|0(21|42)-\d{8}|0[24-9]\d{2}-\d{7,8}|\+?92\d{9,10}|0092\d{9,10}|0[0-9]{9,10})$/'],
            'alternate_phone' => ['nullable', 'string', 'regex:/^(03\d{2}-\d{7}|0(21|42)-\d{8}|0[24-9]\d{2}-\d{7,8}|\+?92\d{9,10}|0092\d{9,10}|0[0-9]{9,10})$/'],
            'email' => 'nullable|email|max:255',
            'city' => 'nullable|string|max:100',
            'branch_id' => 'nullable|exists:branches,id',
            'event_type_id' => 'nullable|exists:event_types,id',
            'preferred_date' => 'nullable|date',
            'alternate_date' => 'nullable|date',
            'slot_id' => 'nullable|exists:slots,id',
            'hall_id' => 'nullable|exists:halls,id',
            'guest_count' => 'nullable|integer|min:1|max:10000',
            'estimated_budget' => 'nullable|numeric|min:0',
            'lead_source' => 'required|string|in:walk_in,call,whatsapp,facebook,instagram,website,referral,other',
            'status' => 'required|string|in:new,contacted,site_visit,negotiation,converted,lost',
            'priority' => 'required|string|in:hot,warm,cold',
            'follow_up_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
        ];
    }

    public function getMarqueeId(): ?int
    {
        $user = auth()->user();
        $id = $user ? ($user->getActiveMarqueeId() ?: $user->marquee_id) : null;
        if (! $id && $user?->isSuperAdmin()) {
            return Marquee::first()?->id;
        }

        return $id;
    }

    public function mount()
    {
        if (request()->has('status')) {
            $this->filterStatus = request()->query('status');
        }
        if (request()->has('view')) {
            $this->viewMode = request()->query('view') === 'kanban' ? 'kanban' : 'table';
        }
    }

    public function setViewMode($mode)
    {
        $this->viewMode = in_array($mode, ['table', 'kanban']) ? $mode : 'table';
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    public function updatedFilterPriority()
    {
        $this->resetPage();
    }

    public function updatedFilterSource()
    {
        $this->resetPage();
    }

    public function updatedFilterBranch()
    {
        $this->resetPage();
    }

    public function applyShortcutFilter($shortcut)
    {
        $this->filterQuickShortcut = $shortcut;
        $this->resetPage();

        switch ($shortcut) {
            case 'new':
                $this->filterStatus = 'new';
                break;
            case 'contacted':
                $this->filterStatus = 'contacted';
                break;
            case 'site_visit':
                $this->filterStatus = 'site_visit';
                break;
            case 'negotiation':
                $this->filterStatus = 'negotiation';
                break;
            case 'converted':
                $this->filterStatus = 'converted';
                break;
            case 'lost':
                $this->filterStatus = 'lost';
                break;
            case 'overdue':
                $this->filterStatus = '';
                break;
            case 'all':
            default:
                $this->filterStatus = '';
                break;
        }
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->filterStatus = '';
        $this->filterPriority = '';
        $this->filterSource = '';
        $this->filterBranch = '';
        $this->filterHall = '';
        $this->filterDateStart = '';
        $this->filterDateEnd = '';
        $this->filterQuickShortcut = 'all';
        $this->resetPage();
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->showLeadModal = true;
    }

    public function editLead($id)
    {
        $marqueeId = $this->getMarqueeId();
        $lead = Lead::withoutGlobalScope('tenant')
            ->when($marqueeId, fn ($q) => $q->where('marquee_id', $marqueeId))
            ->findOrFail($id);

        $this->leadId = $lead->id;
        $this->name = $lead->name;
        $this->phone = $lead->phone;
        $this->alternate_phone = $lead->alternate_phone;
        $this->email = $lead->email;
        $this->city = $lead->city ?? 'Lahore';
        $this->branch_id = $lead->branch_id;
        $this->event_type_id = $lead->event_type_id;
        $this->preferred_date = $lead->preferred_date ? $lead->preferred_date->format('Y-m-d') : '';
        $this->alternate_date = $lead->alternate_date ? $lead->alternate_date->format('Y-m-d') : '';
        $this->slot_id = $lead->slot_id;
        $this->hall_id = $lead->hall_id;
        $this->guest_count = $lead->guest_count;
        $this->estimated_budget = $lead->estimated_budget;
        $this->lead_source = $lead->lead_source;
        $this->status = $lead->status;
        $this->priority = $lead->priority;
        $this->follow_up_date = $lead->follow_up_date ? $lead->follow_up_date->format('Y-m-d') : '';
        $this->notes = $lead->notes;
        $this->assigned_to = $lead->assigned_to;

        $this->showLeadModal = true;
    }

    public function saveLead()
    {
        $this->validate();

        $marqueeId = $this->getMarqueeId();

        $lead = Lead::withoutGlobalScope('tenant')->updateOrCreate(
            ['id' => $this->leadId, 'marquee_id' => $marqueeId],
            [
                'name' => $this->name,
                'phone' => $this->phone,
                'alternate_phone' => $this->alternate_phone,
                'email' => $this->email,
                'city' => $this->city ?: 'Lahore',
                'branch_id' => $this->branch_id ?: null,
                'event_type_id' => $this->event_type_id ?: null,
                'preferred_date' => $this->preferred_date ?: null,
                'alternate_date' => $this->alternate_date ?: null,
                'slot_id' => $this->slot_id ?: null,
                'hall_id' => $this->hall_id ?: null,
                'guest_count' => $this->guest_count ? intval($this->guest_count) : null,
                'estimated_budget' => $this->estimated_budget ? floatval($this->estimated_budget) : null,
                'lead_source' => $this->lead_source,
                'status' => $this->status,
                'priority' => $this->priority,
                'follow_up_date' => $this->follow_up_date ?: null,
                'notes' => $this->notes,
                'assigned_to' => $this->assigned_to ?: null,
            ]
        );

        // If newly created, log initial lead creation activity
        if (! $this->leadId) {
            LeadActivity::create([
                'lead_id' => $lead->id,
                'user_id' => auth()->id(),
                'activity_type' => 'note',
                'notes' => 'Inquiry registered from '.$lead->source_label.' with priority '.ucfirst($lead->priority),
                'follow_up_date' => $lead->follow_up_date,
            ]);
        }

        $this->showLeadModal = false;
        $this->resetForm();
        session()->flash('success', 'Lead inquiry profile saved successfully.');
    }

    public function resetForm()
    {
        $this->leadId = null;
        $this->name = '';
        $this->phone = '';
        $this->alternate_phone = '';
        $this->email = '';
        $this->city = 'Lahore';
        $this->branch_id = null;
        $this->event_type_id = null;
        $this->preferred_date = '';
        $this->alternate_date = '';
        $this->slot_id = null;
        $this->hall_id = null;
        $this->guest_count = null;
        $this->estimated_budget = null;
        $this->lead_source = 'walk_in';
        $this->status = 'new';
        $this->priority = 'warm';
        $this->follow_up_date = Carbon::tomorrow()->format('Y-m-d');
        $this->notes = '';
        $this->assigned_to = auth()->id();
    }

    public function openActivityModal($leadId)
    {
        $marqueeId = $this->getMarqueeId();
        $this->selectedLead = Lead::withoutGlobalScope('tenant')
            ->when($marqueeId, fn ($q) => $q->where('marquee_id', $marqueeId))
            ->with(['activities.user'])
            ->findOrFail($leadId);

        $this->activityType = 'call';
        $this->activityNotes = '';
        $this->activityFollowUpDate = $this->selectedLead->follow_up_date ? $this->selectedLead->follow_up_date->format('Y-m-d') : '';
        $this->showActivityModal = true;
    }

    public function saveActivity()
    {
        $this->validate([
            'activityNotes' => 'required|string|min:3',
            'activityType' => 'required|string|in:call,whatsapp,meeting,site_visit,quotation_sent,note',
            'activityFollowUpDate' => 'nullable|date',
        ]);

        if (! $this->selectedLead) {
            return;
        }

        LeadActivity::create([
            'lead_id' => $this->selectedLead->id,
            'user_id' => auth()->id(),
            'activity_type' => $this->activityType,
            'notes' => $this->activityNotes,
            'follow_up_date' => $this->activityFollowUpDate ?: null,
        ]);

        if (! empty($this->activityFollowUpDate)) {
            $this->selectedLead->update([
                'follow_up_date' => $this->activityFollowUpDate,
            ]);
        }

        $this->showActivityModal = false;
        $this->selectedLead = null;
        $this->activityNotes = '';
        session()->flash('success', 'Follow-up interaction logged successfully.');
    }

    public function updateLeadStatus($leadId, $newStatus)
    {
        $marqueeId = $this->getMarqueeId();
        $lead = Lead::withoutGlobalScope('tenant')
            ->when($marqueeId, fn ($q) => $q->where('marquee_id', $marqueeId))
            ->findOrFail($leadId);

        $oldStatusLabel = $lead->status_label;
        $lead->status = $newStatus;
        $lead->save();

        LeadActivity::create([
            'lead_id' => $lead->id,
            'user_id' => auth()->id(),
            'activity_type' => 'status_change',
            'notes' => 'Status changed from ['.$oldStatusLabel.'] to ['.$lead->status_label.']',
            'follow_up_date' => $lead->follow_up_date,
        ]);

        session()->flash('success', 'Inquiry moved to '.$lead->status_label.'.');
    }

    public function openLostModal($leadId)
    {
        $this->lostLeadId = $leadId;
        $this->lostReason = 'chose_competitor';
        $this->lostNotes = '';
        $this->showLostModal = true;
    }

    public function confirmLost()
    {
        $this->validate([
            'lostReason' => 'required|string',
            'lostNotes' => 'nullable|string',
        ]);

        $marqueeId = $this->getMarqueeId();
        $lead = Lead::withoutGlobalScope('tenant')
            ->when($marqueeId, fn ($q) => $q->where('marquee_id', $marqueeId))
            ->findOrFail($this->lostLeadId);

        $lead->status = 'lost';
        $lead->lost_reason = $this->lostReason;
        if (! empty($this->lostNotes)) {
            $lead->notes = ($lead->notes ? $lead->notes."\n" : '').'[Lost Reason Note]: '.$this->lostNotes;
        }
        $lead->save();

        LeadActivity::create([
            'lead_id' => $lead->id,
            'user_id' => auth()->id(),
            'activity_type' => 'status_change',
            'notes' => 'Inquiry marked as Lost. Reason: '.ucfirst(str_replace('_', ' ', $this->lostReason)).($this->lostNotes ? " ({$this->lostNotes})" : ''),
        ]);

        $this->showLostModal = false;
        $this->lostLeadId = null;
        session()->flash('success', 'Lead has been marked as Lost/Dropped.');
    }

    public function convertToBooking($leadId)
    {
        $marqueeId = $this->getMarqueeId();
        $lead = Lead::withoutGlobalScope('tenant')
            ->when($marqueeId, fn ($q) => $q->where('marquee_id', $marqueeId))
            ->findOrFail($leadId);

        // Find or create customer record
        if (! $lead->customer_id) {
            $customer = Customer::withoutGlobalScope('tenant')
                ->where('marquee_id', $marqueeId)
                ->where(function ($q) use ($lead) {
                    $q->where('phone_number', $lead->phone)
                        ->orWhere('phone_number', str_replace(['-', ' '], '', $lead->phone));
                })
                ->first();

            if (! $customer) {
                $nameParts = explode(' ', trim($lead->name), 2);
                $customer = Customer::create([
                    'marquee_id' => $marqueeId,
                    'customer_type' => 'Individual',
                    'first_name' => $nameParts[0],
                    'last_name' => $nameParts[1] ?? '',
                    'phone_number' => $lead->phone,
                    'alternate_phone' => $lead->alternate_phone,
                    'email' => $lead->email,
                    'city' => $lead->city ?? 'Lahore',
                    'notes' => 'Generated from CRM Inquiry: '.($lead->notes ?? ''),
                    'status' => 'Active',
                ]);
            }
            $lead->customer_id = $customer->id;
            $lead->save();
        }

        // Redirect to booking wizard with lead parameter
        return redirect()->route('bookings.create', [
            'lead_id' => $lead->id,
            'customer_id' => $lead->customer_id,
        ]);
    }

    public function exportCsv(): StreamedResponse
    {
        $marqueeId = $this->getMarqueeId();

        $query = Lead::withoutGlobalScope('tenant')
            ->when($marqueeId, fn ($q) => $q->where('marquee_id', $marqueeId))
            ->with(['branch', 'eventType', 'hall', 'slot', 'assignedUser']);

        $this->applyQueryFilters($query);

        $leads = $query->orderBy('created_at', 'desc')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="leads_inquiries_'.date('Y-m-d_His').'.csv"',
        ];

        return response()->stream(function () use ($leads) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'ID', 'Name', 'Phone', 'Email', 'Branch', 'Event Type', 'Preferred Date',
                'Hall', 'Shift', 'Guest Count', 'Estimated Budget', 'Source', 'Status', 'Priority', 'Follow-up Date', 'Assigned To', 'Created Date',
            ]);

            foreach ($leads as $lead) {
                fputcsv($handle, [
                    $lead->id,
                    $lead->name,
                    $lead->phone,
                    $lead->email ?? '',
                    $lead->branch?->name ?? 'All Branches',
                    $lead->eventType?->name ?? 'Not Specified',
                    $lead->preferred_date ? $lead->preferred_date->format('d-M-Y') : '',
                    $lead->hall?->name ?? '',
                    $lead->slot?->name ?? '',
                    $lead->guest_count ?? '',
                    $lead->estimated_budget ?? '',
                    $lead->source_label,
                    $lead->status_label,
                    ucfirst($lead->priority),
                    $lead->follow_up_date ? $lead->follow_up_date->format('d-M-Y') : '',
                    $lead->assignedUser?->name ?? '',
                    $lead->created_at->format('d-M-Y H:i'),
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }

    private function applyQueryFilters($query)
    {
        if (! empty($this->search)) {
            $term = '%'.$this->search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('alternate_phone', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('notes', 'like', $term);
            });
        }

        if (! empty($this->filterStatus)) {
            $query->where('status', $this->filterStatus);
        }

        if (! empty($this->filterPriority)) {
            $query->where('priority', $this->filterPriority);
        }

        if (! empty($this->filterSource)) {
            $query->where('lead_source', $this->filterSource);
        }

        if (! empty($this->filterBranch)) {
            $query->where('branch_id', $this->filterBranch);
        }

        if (! empty($this->filterHall)) {
            $query->where('hall_id', $this->filterHall);
        }

        if ($this->filterQuickShortcut === 'overdue') {
            $query->whereNotNull('follow_up_date')
                ->whereDate('follow_up_date', '<', Carbon::today())
                ->whereNotIn('status', ['converted', 'lost']);
        }

        if (! empty($this->filterDateStart)) {
            $query->whereDate('preferred_date', '>=', $this->filterDateStart);
        }

        if (! empty($this->filterDateEnd)) {
            $query->whereDate('preferred_date', '<=', $this->filterDateEnd);
        }
    }

    public function render()
    {
        $marqueeId = $this->getMarqueeId();

        $baseQuery = Lead::withoutGlobalScope('tenant')
            ->when($marqueeId, fn ($q) => $q->where('marquee_id', $marqueeId));

        // Metric aggregations
        $totalLeadsCount = (clone $baseQuery)->count();
        $newLeadsCount = (clone $baseQuery)->where('status', 'new')->count();
        $contactedLeadsCount = (clone $baseQuery)->where('status', 'contacted')->count();
        $siteVisitLeadsCount = (clone $baseQuery)->where('status', 'site_visit')->count();
        $negotiationLeadsCount = (clone $baseQuery)->where('status', 'negotiation')->count();
        $convertedLeadsCount = (clone $baseQuery)->where('status', 'converted')->count();
        $lostLeadsCount = (clone $baseQuery)->where('status', 'lost')->count();
        $overdueFollowupsCount = (clone $baseQuery)
            ->whereNotNull('follow_up_date')
            ->whereDate('follow_up_date', '<', Carbon::today())
            ->whereNotIn('status', ['converted', 'lost'])
            ->count();

        // Query with applied user filters
        $query = Lead::withoutGlobalScope('tenant')
            ->when($marqueeId, fn ($q) => $q->where('marquee_id', $marqueeId))
            ->with(['branch', 'eventType', 'hall', 'slot', 'assignedUser', 'activities']);

        $this->applyQueryFilters($query);

        if ($this->viewMode === 'kanban') {
            // For kanban, get all active leads grouped or ordered by status
            $leads = $query->orderBy('priority', 'asc')->orderBy('created_at', 'desc')->get();
            $kanbanColumns = [
                'new' => $leads->where('status', 'new'),
                'contacted' => $leads->where('status', 'contacted'),
                'site_visit' => $leads->where('status', 'site_visit'),
                'negotiation' => $leads->where('status', 'negotiation'),
                'converted' => $leads->where('status', 'converted'),
                'lost' => $leads->where('status', 'lost'),
            ];
            $tableLeads = null;
        } else {
            $tableLeads = $query->orderBy('created_at', 'desc')->paginate(15);
            $kanbanColumns = null;
        }

        // Auxiliary dropdown lists
        $branches = Branch::withoutGlobalScope('tenant')
            ->when($marqueeId, fn ($q) => $q->where('marquee_id', $marqueeId))
            ->where('status', 'active')
            ->get();

        $eventTypes = EventType::withoutGlobalScope('tenant')
            ->when($marqueeId, fn ($q) => $q->where('marquee_id', $marqueeId))
            ->get();

        $halls = Hall::withoutGlobalScope('tenant')
            ->when($marqueeId, fn ($q) => $q->where('marquee_id', $marqueeId))
            ->where('status', 'active')
            ->get();

        $slots = Slot::withoutGlobalScope('tenant')
            ->when($marqueeId, fn ($q) => $q->where('marquee_id', $marqueeId))
            ->whereIn('status', ['active', 'Active'])
            ->get();

        $staffUsers = User::withoutGlobalScope('tenant')
            ->when($marqueeId, fn ($q) => $q->where('marquee_id', $marqueeId))
            ->orderBy('name')
            ->get();

        return view('livewire.lead-manager', [
            'viewMode' => $this->viewMode,
            'filterQuickShortcut' => $this->filterQuickShortcut,
            'showLeadModal' => $this->showLeadModal,
            'showActivityModal' => $this->showActivityModal,
            'showLostModal' => $this->showLostModal,
            'selectedLead' => $this->selectedLead,
            'leadId' => $this->leadId,
            'totalLeadsCount' => $totalLeadsCount,
            'newLeadsCount' => $newLeadsCount,
            'contactedLeadsCount' => $contactedLeadsCount,
            'siteVisitLeadsCount' => $siteVisitLeadsCount,
            'negotiationLeadsCount' => $negotiationLeadsCount,
            'convertedLeadsCount' => $convertedLeadsCount,
            'lostLeadsCount' => $lostLeadsCount,
            'overdueFollowupsCount' => $overdueFollowupsCount,
            'tableLeads' => $tableLeads,
            'kanbanColumns' => $kanbanColumns,
            'branches' => $branches,
            'eventTypes' => $eventTypes,
            'halls' => $halls,
            'slots' => $slots,
            'staffUsers' => $staffUsers,
        ])->layout('layouts.admin');
    }
}
