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
        Schema::create('recipe_version_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('recipe_version_id');
            $table->unsignedBigInteger('inventory_item_id');
            $table->decimal('quantity_per_head', 10, 4);
            $table->unsignedBigInteger('recipe_unit_id');
            $table->timestamps();

            // Constraints
            $table->foreign('recipe_version_id')->references('id')->on('recipe_versions')->onDelete('cascade');
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('restrict');
            $table->foreign('recipe_unit_id')->references('id')->on('inventory_units')->onDelete('restrict');

            // Unique index to prevent duplicate ingredient definition within the same version
            $table->unique(['recipe_version_id', 'inventory_item_id'], 'rvd_version_item_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipe_version_details');
    }
};
