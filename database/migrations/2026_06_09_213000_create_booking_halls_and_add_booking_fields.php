<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create booking_halls pivot table for multiple halls selection
        Schema::create('booking_halls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
            $table->foreignId('hall_id')->constrained('halls')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['booking_id', 'hall_id']);
        });

        // 2. Add no_food boolean to bookings table
        Schema::table('bookings', function (Blueprint $table) {
            $table->boolean('no_food')->default(false)->after('payment_status');
        });

        // 3. Add managed_by_host boolean to booking_menu_items table
        Schema::table('booking_menu_items', function (Blueprint $table) {
            $table->boolean('managed_by_host')->default(false)->after('custom_note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_menu_items', function (Blueprint $table) {
            $table->dropColumn('managed_by_host');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('no_food');
        });

        Schema::dropIfExists('booking_halls');
    }
};
