<?php

namespace App\Livewire\Support;

use App\Models\Announcement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class AnnouncementBanner extends Component
{
    public function acknowledge(int $announcementId)
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }

        try {
            $announcement = Announcement::findOrFail($announcementId);
            $announcement->acknowledge($user->id);
        } catch (\Throwable $e) {
            // Gracefully ignore if table or record does not exist
        }
    }

    public function render()
    {
        $user = Auth::user();

        if (!$user) {
            return view('livewire.support.announcement-banner', ['announcements' => collect()]);
        }

        try {
            if (!Schema::hasTable('announcements')) {
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
        } catch (\Throwable $e) {
            $announcements = collect();
        }

        return view('livewire.support.announcement-banner', [
            'announcements' => $announcements,
        ]);
    }
}
