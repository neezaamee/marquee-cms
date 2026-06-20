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
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marquee_id')->constrained('marquees')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('menu_categories')->onDelete('cascade');
            $table->string('item_name');
            $table->string('item_code');
            $table->text('description')->nullable();
            $table->string('unit')->default('Per Plate');
            $table->decimal('base_cost', 12, 2)->nullable();
            $table->decimal('selling_price', 12, 2);
            $table->string('image')->nullable();
            $table->string('status')->default('Active'); // Active, Inactive
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->softDeletes();
            $table->timestamps();

            // Unique item code per marquee tenant
            $table->unique(['marquee_id', 'item_code']);
            $table->index(['marquee_id', 'category_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
