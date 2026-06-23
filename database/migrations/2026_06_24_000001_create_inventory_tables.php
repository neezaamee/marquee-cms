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
        // 1. Inventory Categories
        Schema::create('inventory_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marquee_id')->constrained('marquees')->onDelete('cascade');
            $table->string('name');
            $table->foreignId('parent_id')->nullable()->constrained('inventory_categories')->onDelete('set null');
            $table->text('description')->nullable();
            $table->string('status')->default('Active'); // Active, Inactive
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['marquee_id', 'name']);
        });

        // 2. Inventory Units
        Schema::create('inventory_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marquee_id')->constrained('marquees')->onDelete('cascade');
            $table->string('name');
            $table->string('short_code');
            $table->text('description')->nullable();
            $table->string('status')->default('Active'); // Active, Inactive
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['marquee_id', 'name']);
            $table->unique(['marquee_id', 'short_code']);
        });

        // 3. Inventory Brands
        Schema::create('inventory_brands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marquee_id')->constrained('marquees')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('Active'); // Active, Inactive
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['marquee_id', 'name']);
        });

        // 4. Inventory Items
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marquee_id')->constrained('marquees')->onDelete('cascade');
            $table->string('item_code');
            $table->string('name');
            $table->foreignId('category_id')->constrained('inventory_categories')->onDelete('restrict');
            $table->foreignId('unit_id')->constrained('inventory_units')->onDelete('restrict');
            $table->foreignId('brand_id')->nullable()->constrained('inventory_brands')->onDelete('set null');
            $table->text('description')->nullable();
            $table->decimal('minimum_stock_level', 15, 2)->default(0.00);
            $table->decimal('reorder_level', 15, 2)->default(0.00);
            $table->decimal('default_purchase_rate', 15, 2)->default(0.00);
            $table->string('status')->default('Active'); // Active, Inactive
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['marquee_id', 'item_code']);
            $table->unique(['marquee_id', 'name']);
        });

        // 5. Inventory Settings (Account mappings per Marquee company/tenant)
        Schema::create('inventory_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marquee_id')->unique()->constrained('marquees')->onDelete('cascade');
            $table->foreignId('inventory_asset_account_id')->nullable()->constrained('accounts')->onDelete('set null');
            $table->foreignId('accounts_payable_account_id')->nullable()->constrained('accounts')->onDelete('set null');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_settings');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('inventory_brands');
        Schema::dropIfExists('inventory_units');
        Schema::dropIfExists('inventory_categories');
    }
};
