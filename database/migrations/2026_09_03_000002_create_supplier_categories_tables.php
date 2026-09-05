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
        // 1. Supplier Categories Master Table
        Schema::create('supplier_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marquee_id')->constrained('marquees')->onDelete('cascade');
            $table->string('name');
            $table->string('code');
            $table->text('description')->nullable();
            $table->string('status')->default('Active'); // Active, Inactive
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['marquee_id', 'code']);
            $table->unique(['marquee_id', 'name']);
            $table->index(['marquee_id', 'status']);
        });

        // 2. Supplier ↔ Category Many-to-Many Pivot Table
        Schema::create('supplier_supplier_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->foreignId('supplier_category_id')->constrained('supplier_categories')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['supplier_id', 'supplier_category_id'], 'supp_supp_cat_unique');
            $table->index(['supplier_category_id', 'supplier_id'], 'supp_cat_supp_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_supplier_category');
        Schema::dropIfExists('supplier_categories');
    }
};
