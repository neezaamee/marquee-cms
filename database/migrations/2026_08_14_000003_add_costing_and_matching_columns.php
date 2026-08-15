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
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->decimal('average_cost', 15, 2)->default(0.00)->after('default_purchase_rate');
            $table->decimal('last_purchase_cost', 15, 2)->default(0.00)->after('average_cost');
            
            $table->unsignedBigInteger('purchase_unit_id')->nullable()->after('unit_id');
            $table->decimal('conversion_factor', 10, 4)->default(1.0000)->after('purchase_unit_id');

            $table->foreign('purchase_unit_id')->references('id')->on('inventory_units')->onDelete('set null');
        });

        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('purchase_order_id')->nullable()->after('supplier_id');
            $table->unsignedBigInteger('goods_receiving_note_id')->nullable()->after('purchase_order_id');

            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('set null');
            $table->foreign('goods_receiving_note_id')->references('id')->on('goods_receiving_notes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropForeign(['purchase_order_id']);
            $table->dropForeign(['goods_receiving_note_id']);
            $table->dropColumn(['purchase_order_id', 'goods_receiving_note_id']);
        });

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropForeign(['purchase_unit_id']);
            $table->dropColumn(['average_cost', 'last_purchase_cost', 'purchase_unit_id', 'conversion_factor']);
        });
    }
};
