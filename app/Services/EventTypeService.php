<?php

namespace App\Services;

use App\Models\EventType;
use Illuminate\Database\Eloquent\Collection;

class EventTypeService
{
    /**
     * Get active event types available for booking in a specific marquee and optionally a specific branch.
     * Includes marquee-wide event types (where branch_id is null) and branch-specific event types.
     */
    public function getActiveEventTypesForBooking(int $marqueeId, ?int $branchId = null): Collection
    {
        return EventType::where('marquee_id', $marqueeId)
            ->where('status', 'Active')
            ->where(function ($query) use ($branchId) {
                $query->whereNull('branch_id');
                if ($branchId) {
                    $query->orWhere('branch_id', $branchId);
                }
            })
            ->orderBy('sort_order')
            ->orderBy('event_type_name')
            ->get();
    }
}
