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
        Schema::create('department_stock_issues', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('marquee_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('department_stock_request_id')->nullable();
            $table->string('issue_number');
            $table->date('issue_date');
            $table->unsignedBigInteger('issued_by'); // references users
            $table->unsignedBigInteger('received_by')->nullable(); // references employees
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Constraints
            $table->foreign('marquee_id')->references('id')->on('marquees')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->foreign('department_stock_request_id', 'dsi_request_id_foreign')->references('id')->on('department_stock_requests')->onDelete('set null');

            $table->unique(['marquee_id', 'issue_number']);
        });

        Schema::create('department_stock_issue_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_stock_issue_id');
            $table->unsignedBigInteger('item_id');
            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_price', 15, 2); // central cost price
            $table->timestamps();

            // Constraints
            $table->foreign('department_stock_issue_id', 'dsi_item_issue_id_foreign')->references('id')->on('department_stock_issues')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('inventory_items')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_stock_issue_items');
        Schema::dropIfExists('department_stock_issues');
    }
};
