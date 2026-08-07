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
        Schema::create('global_default_masters', function (Blueprint $table) {
            $table->id();
            $table->string('category_type'); // event_type, menu_category, inventory_category, inventory_unit, expense_category, department_type, vendor_type, customer_type, payment_method
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->json('extra_attributes')->nullable(); // short_code, color_code, sort_order, expense_type_names
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['category_type', 'is_active']);
            $table->unique(['category_type', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('global_default_masters');
    }
};
