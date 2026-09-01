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
        Schema::table('vendor_sales', function (Blueprint $table) {
            if (!Schema::hasColumn('vendor_sales', 'include_in_invoice')) {
                $table->boolean('include_in_invoice')->default(true)->after('payment_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_sales', function (Blueprint $table) {
            if (Schema::hasColumn('vendor_sales', 'include_in_invoice')) {
                $table->dropColumn('include_in_invoice');
            }
        });
    }
};
