<?php

namespace App\Livewire\Support;

use App\Models\Announcement;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AnnouncementBanner extends Component
{
    public function acknowledge(int $announcementId)
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }

        $announcement = Announcement::findOrFail($announcementId);
        $announcement->acknowledge($user->id);
    }

    public function render()
    {
        $user = Auth::user();

        if (!$user) {
            return view('livewire.support.announcement-banner', ['announcements' => collect()]);
        }

        // Fetch active announcements targeted to this user
        $announcements = Announcement::active()
            ->forUser($user)
            ->with(['acknowledgments' => fn($q) => $q->where('user_id', $user->id)])
            ->latest('start_date')
            ->get()
            ->filter(function ($announcement) use ($user) {
                // If requires acknowledgment and already acknowledged, hide it
                if ($announcement->requires_acknowledgment && $announcement->isAcknowledgedBy($user->id)) {
                    return false;
                }
                return true;
            });

        return view('livewire.support.announcement-banner', [
            'announcements' => $announcements,
        ]);
    }
}
