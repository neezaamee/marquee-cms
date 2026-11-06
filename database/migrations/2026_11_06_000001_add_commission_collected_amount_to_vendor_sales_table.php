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
            if (!Schema::hasColumn('vendor_sales', 'commission_collected_amount')) {
                $table->decimal('commission_collected_amount', 15, 2)->default(0.00)->after('commission_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_sales', function (Blueprint $table) {
            if (Schema::hasColumn('vendor_sales', 'commission_collected_amount')) {
                $table->dropColumn('commission_collected_amount');
            }
        });
    }
};

