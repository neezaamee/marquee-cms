<?php

namespace App\Livewire\SuperAdmin\Support;

use App\Models\FeatureRequest;
use App\Models\Marquee;
use App\Models\SupportConversation;
use App\Models\SupportFeedback;
use App\Models\SupportTicket;
use App\Services\Support\SlaManagementService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SupportDashboard extends Component
{
    public $dateRange = '30_days';
    public $marqueeFilter = '';

    public function mount()
    {
        $user = Auth::user();
        if (!$user || (!$user->isSuperAdmin() && (!$user->hasPermission('support.analytics.view') || $user->isBusinessOwner()))) {
            abort(403, 'Unauthorized access to Support Operations Dashboard.');
        }
    }

    public function render()
    {
        $marqueeId = !empty($this->marqueeFilter) ? (int) $this->marqueeFilter : null;

        // Dynamic metrics calculation from SlaManagementService
        $metrics = SlaManagementService::calculateMetrics($marqueeId, $this->dateRange);

        // Additional Support Modules Volume Metrics
        $chatBase = SupportConversation::withoutGlobalScopes();
        if ($marqueeId) {
            $chatBase->where('marquee_id', $marqueeId);
        }
        $totalChats = (clone $chatBase)->count();
        $activeChats = (clone $chatBase)->whereIn('status', ['active', 'waiting_agent', 'waiting_customer'])->count();

        $feedbackBase = SupportFeedback::withoutGlobalScopes();
        if ($marqueeId) {
            $feedbackBase->where('marquee_id', $marqueeId);
        }
        $totalFeedbacks = (clone $feedbackBase)->count();
        $pendingFeedbacks = (clone $feedbackBase)->whereIn('status', ['new', 'under_review'])->count();

        $totalFeatures = FeatureRequest::count();
        $plannedFeatures = FeatureRequest::whereIn('status', ['planned', 'in_development'])->count();

        // High priority or SLA breached tickets requiring immediate operational attention
        $criticalTickets = SupportTicket::withoutGlobalScopes()
            ->with(['marquee', 'assignedAgent', 'category'])
            ->whereNotIn('status', ['resolved', 'closed'])
            ->where(function ($q) {
                $q->where('is_sla_breached', true)
                  ->orWhere('priority', 'urgent')
                  ->orWhere(function ($sub) {
                      $sub->whereNotNull('sla_due_at')->where('sla_due_at', '<', Carbon::now()->addHours(2));
                  });
            })
            ->latest()
            ->take(6)
            ->get();

        $marquees = Marquee::orderBy('name')->get();

        return view('livewire.super-admin.support.support-dashboard', [
            'metrics' => $metrics,
            'totalChats' => $totalChats,
            'activeChats' => $activeChats,
            'totalFeedbacks' => $totalFeedbacks,
            'pendingFeedbacks' => $pendingFeedbacks,
            'totalFeatures' => $totalFeatures,
            'plannedFeatures' => $plannedFeatures,
            'criticalTickets' => $criticalTickets,
            'marquees' => $marquees,
        ])->layout('layouts.admin', ['title' => 'Support Operations & SLA Dashboard']);
    }
}
