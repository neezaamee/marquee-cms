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
        Schema::table('bookings', function (Blueprint $table) {
            $table->boolean('privacy_required')->default(false)->after('no_food');
            $table->integer('privacy_ladies_percentage')->nullable()->after('privacy_required');
            $table->integer('privacy_gents_percentage')->nullable()->after('privacy_ladies_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['privacy_required', 'privacy_ladies_percentage', 'privacy_gents_percentage']);
        });
    }
};
