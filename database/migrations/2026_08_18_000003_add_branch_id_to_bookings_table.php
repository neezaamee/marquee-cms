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
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('marquee_id')->constrained('branches')->cascadeOnDelete();
            
            $table->index(['marquee_id', 'branch_id', 'booking_date']);
            $table->index(['branch_id', 'booking_status']);
        });

        // Data Backfill: Populate branch_id on existing bookings from primary hall's branch_id
        if (Schema::hasTable('halls')) {
            if (DB::getDriverName() === 'sqlite') {
                DB::statement('
                    UPDATE bookings 
                    SET branch_id = (SELECT branch_id FROM halls WHERE halls.id = bookings.hall_id)
                    WHERE branch_id IS NULL AND hall_id IS NOT NULL
                ');
            } else {
                DB::statement('
                    UPDATE bookings 
                    INNER JOIN halls ON bookings.hall_id = halls.id 
                    SET bookings.branch_id = halls.branch_id 
                    WHERE bookings.branch_id IS NULL AND halls.branch_id IS NOT NULL
                ');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropIndex(['marquee_id', 'branch_id', 'booking_date']);
            $table->dropIndex(['branch_id', 'booking_status']);
            $table->dropColumn('branch_id');
        });
    }
};
