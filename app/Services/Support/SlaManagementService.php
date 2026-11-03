<?php

namespace App\Services\Support;

use App\Models\SupportCategory;
use App\Models\SupportSetting;
use App\Models\SupportTicket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SlaManagementService
{
    /**
     * Calculate resolution SLA due datetime for a given priority.
     */
    public static function calculateSlaDueAt(string $priority, ?Carbon $startDate = null): Carbon
    {
        $start = $startDate ?: Carbon::now();
        $slaTargets = SupportSetting::get('sla_targets_hours', [
            'urgent' => ['response' => 1, 'resolution' => 4],
            'high' => ['response' => 2, 'resolution' => 8],
            'normal' => ['response' => 4, 'resolution' => 24],
            'low' => ['response' => 8, 'resolution' => 48],
        ]);

        $hours = $slaTargets[$priority]['resolution'] ?? 24;
        return (clone $start)->addHours((int) $hours);
    }

    /**
     * Scan and update SLA breach status for tickets whose deadline has elapsed.
     */
    public static function checkAndUpdateSlaBreaches(): int
    {
        $now = Carbon::now();

        $breachedTickets = SupportTicket::withoutGlobalScopes()
            ->whereNotIn('status', ['resolved', 'closed'])
            ->whereNotNull('sla_due_at')
            ->where('sla_due_at', '<', $now)
            ->where('is_sla_breached', false)
            ->get();

        $count = 0;
        foreach ($breachedTickets as $ticket) {
            $ticket->update(['is_sla_breached' => true]);
            $count++;
        }

        return $count;
    }

    /**
     * Compute comprehensive support metrics dynamically.
     */
    public static function calculateMetrics(?int $marqueeId = null, string $dateRange = '30_days'): array
    {
        // First auto-check breaches
        static::checkAndUpdateSlaBreaches();

        $query = SupportTicket::withoutGlobalScopes();

        if ($marqueeId) {
            $query->where('marquee_id', $marqueeId);
        }

        // Apply date range filter
        $now = Carbon::now();
        if ($dateRange === 'today') {
            $query->whereDate('created_at', Carbon::today());
        } elseif ($dateRange === '7_days') {
            $query->where('created_at', '>=', (clone $now)->subDays(7));
        } elseif ($dateRange === '30_days') {
            $query->where('created_at', '>=', (clone $now)->subDays(30));
        } elseif ($dateRange === 'this_month') {
            $query->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year);
        }

        $allTickets = (clone $query)->get();
        $totalTickets = $allTickets->count();

        $openTickets = $allTickets->whereIn('status', ['open', 'assigned', 'in_progress', 'awaiting_internal', 'reopened'])->count();
        $awaitingCustomer = $allTickets->where('status', 'awaiting_customer')->count();
        $resolvedTickets = $allTickets->whereIn('status', ['resolved', 'closed'])->count();

        // SLA metrics
        $breachedTicketsCount = $allTickets->where('is_sla_breached', true)->count();
        $slaBreachRate = $totalTickets > 0 ? round(($breachedTicketsCount / $totalTickets) * 100, 1) : 0;

        // Average First Response Time (hours)
        $respondedTickets = $allTickets->filter(fn($t) => !empty($t->first_response_at));
        $avgFirstResponseHours = 0;
        if ($respondedTickets->isNotEmpty()) {
            $totalMinutes = $respondedTickets->sum(function ($t) {
                return abs(Carbon::parse($t->created_at)->diffInMinutes(Carbon::parse($t->first_response_at)));
            });
            $avgFirstResponseHours = round(($totalMinutes / $respondedTickets->count()) / 60, 1);
        }

        // Average Resolution Time (hours)
        $resolvedSet = $allTickets->filter(fn($t) => !empty($t->resolved_at));
        $avgResolutionHours = 0;
        if ($resolvedSet->isNotEmpty()) {
            $totalMinutes = $resolvedSet->sum(function ($t) {
                return abs(Carbon::parse($t->created_at)->diffInMinutes(Carbon::parse($t->resolved_at)));
            });
            $avgResolutionHours = round(($totalMinutes / $resolvedSet->count()) / 60, 1);
        }

        // Customer Satisfaction (CSAT)
        $ratedTickets = $allTickets->filter(fn($t) => !empty($t->satisfaction_rating));
        $csatCount = $ratedTickets->count();
        $csatAverage = $csatCount > 0 ? round($ratedTickets->avg('satisfaction_rating'), 1) : 0.0;

        $csatBreakdown = [
            5 => $ratedTickets->where('satisfaction_rating', 5)->count(),
            4 => $ratedTickets->where('satisfaction_rating', 4)->count(),
            3 => $ratedTickets->where('satisfaction_rating', 3)->count(),
            2 => $ratedTickets->where('satisfaction_rating', 2)->count(),
            1 => $ratedTickets->where('satisfaction_rating', 1)->count(),
        ];

        // Priority Breakdown
        $priorityBreakdown = [
            'urgent' => $allTickets->where('priority', 'urgent')->count(),
            'high' => $allTickets->where('priority', 'high')->count(),
            'normal' => $allTickets->where('priority', 'normal')->count(),
            'low' => $allTickets->where('priority', 'low')->count(),
        ];

        // Status Breakdown
        $statusBreakdown = [
            'open' => $allTickets->where('status', 'open')->count(),
            'assigned' => $allTickets->where('status', 'assigned')->count(),
            'in_progress' => $allTickets->where('status', 'in_progress')->count(),
            'awaiting_customer' => $awaitingCustomer,
            'resolved' => $allTickets->where('status', 'resolved')->count(),
            'closed' => $allTickets->where('status', 'closed')->count(),
            'reopened' => $allTickets->where('status', 'reopened')->count(),
        ];

        // Agent performance leaderboard
        $agents = User::whereHas('role', fn($r) => $r->whereIn('name', ['super_admin', 'support_agent', 'admin']))
            ->orWhereNull('marquee_id')
            ->get();

        if ($agents->isEmpty()) {
            $agents = User::where('status', 'active')->take(10)->get();
        }

        $agentLeaderboard = [];
        foreach ($agents as $agent) {
            $assignedTickets = $allTickets->where('assigned_to', $agent->id);
            if ($assignedTickets->isNotEmpty() || $agent->isSuperAdmin()) {
                $agentLeaderboard[] = [
                    'id' => $agent->id,
                    'name' => $agent->name,
                    'email' => $agent->email,
                    'total_assigned' => $assignedTickets->count(),
                    'resolved_count' => $assignedTickets->whereIn('status', ['resolved', 'closed'])->count(),
                    'pending_count' => $assignedTickets->whereNotIn('status', ['resolved', 'closed'])->count(),
                ];
            }
        }

        // Sort agents by total assigned descending
        usort($agentLeaderboard, fn($a, $b) => $b['total_assigned'] <=> $a['total_assigned']);

        return [
            'total_tickets' => $totalTickets,
            'open_tickets' => $openTickets,
            'awaiting_customer' => $awaitingCustomer,
            'resolved_tickets' => $resolvedTickets,
            'breached_count' => $breachedTicketsCount,
            'breach_rate' => $slaBreachRate,
            'avg_first_response_hours' => $avgFirstResponseHours,
            'avg_resolution_hours' => $avgResolutionHours,
            'csat_average' => $csatAverage,
            'csat_count' => $csatCount,
            'csat_breakdown' => $csatBreakdown,
            'priority_breakdown' => $priorityBreakdown,
            'status_breakdown' => $statusBreakdown,
            'agent_leaderboard' => $agentLeaderboard,
        ];
    }
}
