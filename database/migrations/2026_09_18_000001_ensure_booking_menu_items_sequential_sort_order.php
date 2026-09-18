<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add composite index on (booking_id, sort_order, id) to enforce deterministic ordering
        Schema::table('booking_menu_items', function (Blueprint $table) {
            $table->index(['booking_id', 'sort_order', 'id'], 'bmi_booking_sort_id_idx');
        });

        // 2. Normalize and backfill sequential sort_order for all existing booking menu items
        $bookingIds = DB::table('booking_menu_items')
            ->select('booking_id')
            ->distinct()
            ->pluck('booking_id');

        foreach ($bookingIds as $bookingId) {
            $items = DB::table('booking_menu_items')
                ->where('booking_id', $bookingId)
                ->orderBy('sort_order', 'asc')
                ->orderBy('id', 'asc')
                ->get(['id']);

            foreach ($items as $idx => $item) {
                DB::table('booking_menu_items')
                    ->where('id', $item->id)
                    ->update(['sort_order' => $idx]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_menu_items', function (Blueprint $table) {
            $table->dropIndex('bmi_booking_sort_id_idx');
        });
    }
};
