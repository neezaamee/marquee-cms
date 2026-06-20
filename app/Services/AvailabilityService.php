<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Hall;
use App\Models\Slot;
use Carbon\Carbon;

class AvailabilityService
{
    /**
     * Resolve a start/end time relative to a date, adjusting for midnight crossings.
     *
     * @param string|Carbon $date
     * @param string|Carbon $startTime
     * @param string|Carbon $endTime
     * @return array [Carbon $start, Carbon $end]
     */
    public function parseTimeRange($date, $startTime, $endTime): array
    {
        $dateStr = $date instanceof Carbon ? $date->format('Y-m-d') : $date;

        $start = $this->resolveDateTime($dateStr, $startTime);
        $end = $this->resolveDateTime($dateStr, $endTime);

        // If the end time is on or before the start time, it means the event crosses midnight
        // (e.g. 18:00 to 01:00). Move the end datetime to the next day.
        if ($end->lte($start)) {
            $end->addDay();
        }

        return [$start, $end];
    }

    /**
     * Helper to parse string times or Carbon objects.
     */
    protected function resolveDateTime(string $dateStr, $time): Carbon
    {
        if ($time instanceof Carbon) {
            return $time;
        }

        // Check if the input is already a full datetime string
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}/', $time)) {
            return Carbon::parse($time);
        }

        return Carbon::parse($dateStr . ' ' . $time);
    }

    /**
     * Retrieve the first conflicting booking for a hall and time range, if one exists.
     *
     * @param int $hallId
     * @param string|Carbon $date
     * @param string|Carbon $startTime
     * @param string|Carbon $endTime
     * @param int|null $excludeBookingId
     * @return Booking|null
     */
    public function getConflictingBooking(int $hallId, $date, $startTime, $endTime, ?int $excludeBookingId = null): ?Booking
    {
        [$start, $end] = $this->parseTimeRange($date, $startTime, $endTime);

        $query = Booking::where(function ($q) use ($hallId) {
                $q->where('hall_id', $hallId)
                  ->orWhereHas('halls', function ($sub) use ($hallId) {
                      $sub->where('halls.id', $hallId);
                  });
            })
            ->whereIn('booking_status', ['Reserved', 'Confirmed'])
            ->where(function ($q) use ($start, $end) {
                // requested_start < existing_end AND requested_end > existing_start
                $q->where('start_time', '<', $end)
                  ->where('end_time', '>', $start);
            });

        if ($excludeBookingId) {
            $query->where('id', '!=', $excludeBookingId);
        }

        return $query->with(['slot', 'creator'])->first();
    }

    /**
     * Check if a specific time range or slot is available for a hall.
     * Supports legacy signature checkAvailability(int $hallId, $date, int $slotId)
     * and current checkAvailability(int $hallId, $date, string $startTime, string $endTime, ?int $excludeBookingId = null)
     *
     * @param int $hallId
     * @param string|Carbon $date
     * @param mixed $startTimeOrSlotId
     * @param mixed|null $endTime
     * @param int|null $excludeBookingId
     * @return bool
     */
    public function checkAvailability(int $hallId, $date, $startTimeOrSlotId, $endTime = null, ?int $excludeBookingId = null): bool
    {
        // If bookings table doesn't exist, return true (available)
        if (!\Illuminate\Support\Facades\Schema::hasTable('bookings')) {
            return true;
        }

        // If the table doesn't have start_time column, run the simple old slot-id checks
        if (!\Illuminate\Support\Facades\Schema::hasColumn('bookings', 'start_time')) {
            $statusCheck = ['confirmed', 'reserved', 'Confirmed', 'Reserved'];
            return !\Illuminate\Support\Facades\DB::table('bookings')
                ->where('hall_id', $hallId)
                ->where('booking_date', $date)
                ->where('slot_id', $startTimeOrSlotId)
                ->whereIn('status', $statusCheck)
                ->exists();
        }

        // Otherwise, run our advanced datetime conflict range calculations
        if ($endTime === null) {
            // If only slot ID was provided in the new schema, resolve the slot start/end times
            $slot = Slot::findOrFail($startTimeOrSlotId);
            $startTime = $slot->start_time;
            $endTime = $slot->end_time;
        } else {
            $startTime = $startTimeOrSlotId;
        }

        return !$this->getConflictingBooking($hallId, $date, $startTime, $endTime, $excludeBookingId);
    }

    /**
     * Direct alias for checkAvailability.
     */
    public function checkTimeAvailability(int $hallId, $date, $startTimeOrSlotId, $endTime = null, ?int $excludeBookingId = null): bool
    {
        return $this->checkAvailability($hallId, $date, $startTimeOrSlotId, $endTime, $excludeBookingId);
    }

    /**
     * Check if the hall has ZERO bookings for the entire day.
     *
     * @param int $hallId
     * @param string|Carbon $date
     * @return bool
     */
    public function checkHallAvailability(int $hallId, $date): bool
    {
        $dateStr = $date instanceof Carbon ? $date->format('Y-m-d') : $date;

        return !Booking::where(function ($q) use ($hallId) {
                $q->where('hall_id', $hallId)
                  ->orWhereHas('halls', function ($sub) use ($hallId) {
                      $sub->where('halls.id', $hallId);
                  });
            })
            ->where('booking_date', $dateStr)
            ->whereIn('booking_status', ['Reserved', 'Confirmed'])
            ->exists();
    }

    /**
     * Retrieve all predefined slots that are BOOKED (overlapping with any confirmed bookings) for a hall and date.
     *
     * @param int $hallId
     * @param string|Carbon $date
     * @return \Illuminate\Support\Collection
     */
    public function getBookedSlots(int $hallId, $date)
    {
        $hall = Hall::findOrFail($hallId);
        $dateStr = $date instanceof Carbon ? $date->format('Y-m-d') : $date;

        // Fetch all active slots for the marquee
        $slots = Slot::where('marquee_id', $hall->marquee_id)
            ->where('status', 'active')
            ->get();

        return $slots->filter(function ($slot) use ($hallId, $dateStr) {
            return !$this->checkAvailability($hallId, $dateStr, $slot->start_time, $slot->end_time);
        });
    }

    /**
     * Retrieve all predefined slots that are AVAILABLE (not overlapping with any confirmed bookings) for a hall and date.
     *
     * @param int $hallId
     * @param string|Carbon $date
     * @return \Illuminate\Support\Collection
     */
    public function getAvailableSlots(int $hallId, $date)
    {
        $hall = Hall::findOrFail($hallId);
        $dateStr = $date instanceof Carbon ? $date->format('Y-m-d') : $date;

        // Fetch all active slots for the marquee
        $slots = Slot::where('marquee_id', $hall->marquee_id)
            ->where('status', 'active')
            ->get();

        return $slots->filter(function ($slot) use ($hallId, $dateStr) {
            return $this->checkAvailability($hallId, $dateStr, $slot->start_time, $slot->end_time);
        });
    }
}
