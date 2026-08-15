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
        Schema::create('inventory_stock_takes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('marquee_id');
            $table->unsignedBigInteger('branch_id');
            $table->string('stock_take_number');
            $table->date('count_date');
            $table->string('status')->default('Draft'); // Draft, Approved, Cancelled
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            // Constraints
            $table->foreign('marquee_id')->references('id')->on('marquees')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            
            $table->unique(['marquee_id', 'stock_take_number']);
        });

        Schema::create('inventory_stock_take_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_stock_take_id');
            $table->unsignedBigInteger('item_id');
            $table->decimal('system_qty', 15, 2);
            $table->decimal('physical_qty', 15, 2);
            $table->decimal('difference', 15, 2);
            $table->string('reason')->nullable();
            $table->timestamps();

            // Constraints
            $table->foreign('inventory_stock_take_id', 'ist_items_take_id_foreign')->references('id')->on('inventory_stock_takes')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('inventory_items')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_stock_take_items');
        Schema::dropIfExists('inventory_stock_takes');
    }
};
