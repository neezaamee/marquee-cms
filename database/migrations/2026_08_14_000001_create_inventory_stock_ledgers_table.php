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
        Schema::create('inventory_stock_ledgers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('marquee_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('item_id');
            $table->date('transaction_date');
            $table->string('transaction_type'); // Opening, GRN, Issue, Return, Adjustment, Wastage, Damage
            $table->string('reference_type')->nullable(); // Model name
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('qty_in', 15, 2)->default(0.00);
            $table->decimal('qty_out', 15, 2)->default(0.00);
            $table->decimal('running_balance', 15, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total_cost', 15, 2);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            // Constraints
            $table->foreign('marquee_id')->references('id')->on('marquees')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('inventory_items')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_stock_ledgers');
    }
};
