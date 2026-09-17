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
        Schema::table('marquees', function (Blueprint $table) {
            if (!Schema::hasColumn('marquees', 'booking_slip_terms')) {
                $table->text('booking_slip_terms')->nullable()->after('status');
            }
            if (!Schema::hasColumn('marquees', 'final_bill_conditions')) {
                $table->text('final_bill_conditions')->nullable()->after('booking_slip_terms');
            }
        });

        Schema::table('branches', function (Blueprint $table) {
            if (!Schema::hasColumn('branches', 'booking_slip_terms')) {
                $table->text('booking_slip_terms')->nullable()->after('booking_prefix');
            }
            if (!Schema::hasColumn('branches', 'final_bill_conditions')) {
                $table->text('final_bill_conditions')->nullable()->after('booking_slip_terms');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marquees', function (Blueprint $table) {
            $table->dropColumn(['booking_slip_terms', 'final_bill_conditions']);
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['booking_slip_terms', 'final_bill_conditions']);
        });
    }
};
