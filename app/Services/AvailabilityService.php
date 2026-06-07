<?php

namespace App\Services;

use App\Models\Hall;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AvailabilityService
{
    /**
     * Check if a specific Hall is available on a specific Date and Slot.
     * 
     * Rules:
     * - Same Date + Same Hall + Same Slot = NOT ALLOWED (Already Booked)
     * - Same Date + Same Hall + Different Slot = ALLOWED
     * - Different Hall + Same Date + Same Slot = ALLOWED
     *
     * @param int $hallId
     * @param string $bookingDate (YYYY-MM-DD)
     * @param int $slotId
     * @return bool (true if available, false if already booked)
     */
    public function checkAvailability(int $hallId, string $bookingDate, int $slotId): bool
    {
        // If the bookings table doesn't exist yet, return true (fully available)
        if (!Schema::hasTable('bookings')) {
            return true;
        }

        // Query the bookings table
        // A booking is considered active if it exists, is not cancelled, and not soft deleted
        $exists = DB::table('bookings')
            ->where('hall_id', $hallId)
            ->where('booking_date', $bookingDate)
            ->where('slot_id', $slotId)
            ->whereNull('deleted_at')
            ->whereIn('status', ['confirmed', 'pending']) // Adapt to actual booking statuses
            ->exists();

        return !$exists;
    }

    /**
     * Get availability calendar data for a branch within a date range.
     * Ready to be consumed by the future calendar UI.
     *
     * @param int $branchId
     * @param string $startDate (YYYY-MM-DD)
     * @param string $endDate (YYYY-MM-DD)
     * @return array
     */
    public function getAvailabilityCalendar(int $branchId, string $startDate, string $endDate): array
    {
        // 1. Fetch all active halls in the branch along with their active assigned slots
        $halls = Hall::where('branch_id', $branchId)
            ->where('status', 'active')
            ->with(['slots' => function ($query) {
                $query->where('slots.status', 'active');
            }])
            ->get();

        // 2. Fetch bookings for these halls in the date range (if bookings table exists)
        $bookings = collect();
        if (Schema::hasTable('bookings')) {
            $bookings = DB::table('bookings')
                ->whereIn('hall_id', $halls->pluck('id'))
                ->whereBetween('booking_date', [$startDate, $endDate])
                ->whereNull('deleted_at')
                ->whereIn('status', ['confirmed', 'pending'])
                ->get()
                ->groupBy(['booking_date', 'hall_id', 'slot_id']);
        }

        // 3. Construct the daily matrix
        $calendarData = [];
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $interval = new \DateInterval('P1D');
        $period = new \DatePeriod($start, $interval, $end->modify('+1 day'));

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $calendarData[$dateStr] = [];

            foreach ($halls as $hall) {
                $hallData = [
                    'hall_id' => $hall->id,
                    'hall_name' => $hall->hall_name,
                    'hall_code' => $hall->hall_code,
                    'capacity' => $hall->capacity,
                    'slots' => []
                ];

                foreach ($hall->slots as $slot) {
                    // Check if slot is booked
                    $isBooked = isset($bookings[$dateStr][$hall->id][$slot->id]);
                    
                    $hallData['slots'][] = [
                        'slot_id' => $slot->id,
                        'slot_name' => $slot->slot_name,
                        'start_time' => $slot->start_time,
                        'end_time' => $slot->end_time,
                        'status' => $isBooked ? 'booked' : 'available',
                        'booking_id' => $isBooked ? $bookings[$dateStr][$hall->id][$slot->id]->first()->id ?? null : null,
                    ];
                }

                $calendarData[$dateStr][] = $hallData;
            }
        }

        return $calendarData;
    }
}
