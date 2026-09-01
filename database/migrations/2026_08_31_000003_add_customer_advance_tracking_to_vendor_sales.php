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
            if (!Schema::hasColumn('vendor_sales', 'customer_advance_amount')) {
                $table->decimal('customer_advance_amount', 15, 2)->default(0.00)->after('sale_amount');
            }
            if (!Schema::hasColumn('vendor_sales', 'customer_paid_amount')) {
                $table->decimal('customer_paid_amount', 15, 2)->default(0.00)->after('customer_advance_amount');
            }
            if (!Schema::hasColumn('vendor_sales', 'customer_remaining_amount')) {
                $table->decimal('customer_remaining_amount', 15, 2)->default(0.00)->after('customer_paid_amount');
            }
        });

        Schema::table('booking_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('booking_payments', 'vendor_sale_id')) {
                $table->foreignId('vendor_sale_id')->nullable()->after('booking_id')->constrained('vendor_sales')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_payments', function (Blueprint $table) {
            if (Schema::hasColumn('booking_payments', 'vendor_sale_id')) {
                $table->dropForeign(['vendor_sale_id']);
                $table->dropColumn('vendor_sale_id');
            }
        });

        Schema::table('vendor_sales', function (Blueprint $table) {
            if (Schema::hasColumn('vendor_sales', 'customer_remaining_amount')) {
                $table->dropColumn('customer_remaining_amount');
            }
            if (Schema::hasColumn('vendor_sales', 'customer_paid_amount')) {
                $table->dropColumn('customer_paid_amount');
            }
            if (Schema::hasColumn('vendor_sales', 'customer_advance_amount')) {
                $table->dropColumn('customer_advance_amount');
            }
        });
    }
};
