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
        Schema::create('department_productions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('marquee_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('department_id');
            $table->string('batch_number');
            $table->date('production_date');
            $table->unsignedBigInteger('booking_id')->nullable(); // references bookings
            $table->unsignedBigInteger('recipe_id')->nullable();  // references recipes
            $table->decimal('produced_qty', 15, 2);
            $table->decimal('wastage_qty', 15, 2)->default(0.00);
            $table->unsignedBigInteger('prepared_by')->nullable(); // references employees
            $table->unsignedBigInteger('approved_by')->nullable(); // references users
            $table->string('production_time')->nullable(); // e.g. "04:30"
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Constraints
            $table->foreign('marquee_id')->references('id')->on('marquees')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('set null');
            $table->foreign('recipe_id')->references('id')->on('recipes')->onDelete('set null');
            $table->foreign('prepared_by')->references('id')->on('employees')->onDelete('set null');

            $table->unique(['marquee_id', 'batch_number']);
        });

        Schema::create('department_production_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_production_id');
            $table->unsignedBigInteger('item_id'); // references inventory_items (raw material)
            $table->decimal('quantity', 15, 2);
            $table->timestamps();

            // Constraints
            $table->foreign('department_production_id', 'dpi_prod_id_foreign')->references('id')->on('department_productions')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('inventory_items')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_production_items');
        Schema::dropIfExists('department_productions');
    }
};
