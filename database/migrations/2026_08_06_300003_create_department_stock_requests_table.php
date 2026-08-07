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
        Schema::create('department_stock_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('marquee_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('department_id');
            $table->string('request_number');
            $table->date('request_date');
            $table->unsignedBigInteger('requested_by'); // references employees/users
            $table->string('status')->default('Draft'); // Draft, Submitted, Approved, Rejected, Partially Issued, Completed, Cancelled
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable(); // references users
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Constraints
            $table->foreign('marquee_id')->references('id')->on('marquees')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            
            $table->unique(['marquee_id', 'request_number']);
        });

        Schema::create('department_stock_request_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_stock_request_id');
            $table->unsignedBigInteger('item_id'); // references inventory_items
            $table->decimal('requested_qty', 15, 2);
            $table->decimal('approved_qty', 15, 2)->default(0.00);
            $table->decimal('issued_qty', 15, 2)->default(0.00);
            $table->timestamps();

            // Constraints
            $table->foreign('department_stock_request_id', 'dsr_id_foreign')->references('id')->on('department_stock_requests')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('inventory_items')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_stock_request_items');
        Schema::dropIfExists('department_stock_requests');
    }
};
