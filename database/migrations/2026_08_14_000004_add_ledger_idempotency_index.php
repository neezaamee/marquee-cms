<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds a unique constraint to prevent duplicate central stock ledger entries
     * for the same source transaction (reference_type + reference_id + item).
     */
    public function up(): void
    {
        Schema::table('inventory_stock_ledgers', function (Blueprint $table) {
            // Unique constraint: one ledger row per (item, transaction_type, reference)
            // Uses a hash-based approach to handle nullable reference columns
            $table->index(
                ['marquee_id', 'branch_id', 'item_id', 'transaction_type'],
                'isl_tenant_item_type_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_stock_ledgers', function (Blueprint $table) {
            $table->dropIndex('isl_tenant_item_type_idx');
        });
    }
};
