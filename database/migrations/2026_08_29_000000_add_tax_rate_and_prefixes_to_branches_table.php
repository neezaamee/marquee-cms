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
        Schema::table('branches', function (Blueprint $table) {
            $table->decimal('tax_rate', 5, 2)->default(13.00)->after('fbr_sandbox_mode');
            $table->string('invoice_prefix')->nullable()->after('tax_rate');
            $table->string('booking_prefix')->nullable()->after('invoice_prefix');
            $table->string('branch_manager')->nullable()->after('booking_prefix');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['tax_rate', 'invoice_prefix', 'booking_prefix', 'branch_manager']);
        });
    }
};
