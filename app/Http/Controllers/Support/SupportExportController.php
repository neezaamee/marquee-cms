<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupportExportController extends Controller
{
    /**
     * Export support tickets to CSV for audit and reporting.
     */
    public function exportTickets(Request $request): StreamedResponse
    {
        $user = Auth::user();

        if (!$user || (!$user->isSuperAdmin() && ((!$user->hasPermission('support.analytics.view') && !$user->hasPermission('support.ticket.manage_all')) || $user->isBusinessOwner()))) {
            abort(403, 'Unauthorized access to support analytics export.');
        }

        $marqueeId = $request->query('marquee_id');
        $status = $request->query('status');
        $priority = $request->query('priority');

        $query = SupportTicket::withoutGlobalScopes()
            ->with(['marquee', 'branch', 'creator', 'assignedAgent', 'category']);

        if ($marqueeId) {
            $query->where('marquee_id', $marqueeId);
        }
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        if ($priority && $priority !== 'all') {
            $query->where('priority', $priority);
        }

        $tickets = $query->latest()->get();

        $filename = 'support_tickets_export_' . Carbon::now()->format('Y_m_d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->streamDownload(function () use ($tickets) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Ticket ID',
                'Marquee Business',
                'Branch',
                'Submitter Name',
                'Submitter Email',
                'Assigned Specialist',
                'Category',
                'Subject',
                'Priority',
                'Status',
                'Related Module',
                'Reference ID',
                'Created At',
                'First Response At',
                'Resolved At',
                'SLA Due At',
                'SLA Breached',
                'CSAT Rating',
                'CSAT Feedback',
            ]);

            foreach ($tickets as $t) {
                fputcsv($handle, [
                    $t->ticket_number,
                    $t->marquee ? $t->marquee->name : 'Platform',
                    $t->branch?->name ?? 'Head Office',
                    $t->creator?->name ?? 'N/A',
                    $t->creator?->email ?? 'N/A',
                    $t->assignedAgent?->name ?? 'Unassigned',
                    $t->category?->name ?? 'General',
                    $t->subject,
                    ucfirst($t->priority),
                    $t->status_label,
                    $t->related_module ?: 'None',
                    $t->related_record_id ? '#' . $t->related_record_id : 'N/A',
                    $t->created_at->format('Y-m-d H:i:s'),
                    $t->first_response_at ? $t->first_response_at->format('Y-m-d H:i:s') : 'N/A',
                    $t->resolved_at ? $t->resolved_at->format('Y-m-d H:i:s') : 'N/A',
                    $t->sla_due_at ? $t->sla_due_at->format('Y-m-d H:i:s') : 'N/A',
                    $t->is_sla_breached ? 'YES' : 'NO',
                    $t->satisfaction_rating ?: 'Unrated',
                    $t->satisfaction_comment ?: '',
                ]);
            }

            fclose($handle);
        }, $filename, $headers);
    }
}
