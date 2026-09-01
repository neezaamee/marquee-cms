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
            if (!Schema::hasColumn('vendor_sales', 'advance_amount')) {
                $table->decimal('advance_amount', 12, 2)->default(0.00)->after('vendor_net_amount');
            }
            if (!Schema::hasColumn('vendor_sales', 'paid_amount')) {
                $table->decimal('paid_amount', 12, 2)->default(0.00)->after('advance_amount');
            }
            if (!Schema::hasColumn('vendor_sales', 'remaining_amount')) {
                $table->decimal('remaining_amount', 12, 2)->default(0.00)->after('paid_amount');
            }
            if (!Schema::hasColumn('vendor_sales', 'payment_status')) {
                $table->string('payment_status')->default('unpaid')->after('remaining_amount'); // unpaid, partially_paid, fully_paid
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_sales', function (Blueprint $table) {
            $table->dropColumn(['advance_amount', 'paid_amount', 'remaining_amount', 'payment_status']);
        });
    }
};
