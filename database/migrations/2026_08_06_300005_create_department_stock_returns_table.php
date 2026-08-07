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
        Schema::create('department_stock_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('marquee_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('department_id');
            $table->string('return_number');
            $table->date('return_date');
            $table->unsignedBigInteger('returned_by'); // references employees/users
            $table->unsignedBigInteger('received_by')->nullable(); // references users
            $table->string('status')->default('Pending'); // Pending, Received, Cancelled
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Constraints
            $table->foreign('marquee_id')->references('id')->on('marquees')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');

            $table->unique(['marquee_id', 'return_number']);
        });

        Schema::create('department_stock_return_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_stock_return_id');
            $table->unsignedBigInteger('item_id');
            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_price', 15, 2);
            $table->string('status')->default('Good'); // Good, Damaged, Wastage
            $table->timestamps();

            // Constraints
            $table->foreign('department_stock_return_id', 'dsr_item_return_id_foreign')->references('id')->on('department_stock_returns')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('inventory_items')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_stock_return_items');
        Schema::dropIfExists('department_stock_returns');
    }
};
