<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->integer('tentative_guests')->nullable()->after('guest_count');
            $table->integer('confirmed_guests')->nullable()->after('tentative_guests');
            $table->string('guest_status')->default('Tentative')->after('confirmed_guests');
        });

        // Safe data backfill for existing bookings
        DB::table('bookings')->whereNull('tentative_guests')->update([
            'tentative_guests' => DB::raw('guest_count'),
            'guest_status' => 'Tentative',
        ]);

        // For existing bookings that were already confirmed or completed, backfill confirmed_guests
        DB::table('bookings')->whereIn('booking_status', ['Confirmed', 'Completed'])->update([
            'confirmed_guests' => DB::raw('guest_count'),
            'guest_status' => 'Confirmed',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['tentative_guests', 'confirmed_guests', 'guest_status']);
        });
    }
};
