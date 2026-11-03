<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Models\TicketAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketAttachmentController extends Controller
{
    /**
     * Authenticated, authorized download for ticket attachments.
     */
    public function download(Request $request, int|string $id): StreamedResponse
    {
        $attachment = TicketAttachment::with([
            'ticket' => fn($q) => $q->withoutGlobalScopes(),
            'message',
        ])->findOrFail($id);

        $ticket = $attachment->ticket;
        if (!$ticket) {
            abort(404, 'Parent ticket not found.');
        }

        $user = Auth::user();

        if (!$user) {
            abort(401, 'Unauthorized');
        }

        // 1. Check Super Admin access
        if (!$user->isSuperAdmin()) {
            // Check Tenant access
            if ($ticket->marquee_id && !$user->hasAccessToMarquee($ticket->marquee_id)) {
                abort(403, 'Access denied to this ticket resource.');
            }

            // Check Internal Note security - never expose internal staff attachments to customer/tenant users
            if ($attachment->message && $attachment->message->is_internal_note) {
                if (!$user->hasRole(['super_admin', 'support_agent'])) {
                    abort(403, 'Access denied: private internal staff attachment.');
                }
            }
        }

        // 2. Check file on disk
        if (!Storage::disk('local')->exists($attachment->file_path)) {
            abort(404, 'Attachment file not found on storage.');
        }

        return Storage::disk('local')->download($attachment->file_path, $attachment->file_name);
    }
}
