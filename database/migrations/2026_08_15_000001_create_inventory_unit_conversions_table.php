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
        Schema::create('inventory_unit_conversions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('marquee_id');
            $table->unsignedBigInteger('inventory_item_id')->nullable();
            $table->unsignedBigInteger('from_unit_id');
            $table->unsignedBigInteger('to_unit_id');
            $table->decimal('factor', 12, 4)->default(1.0000);
            $table->timestamps();

            // Constraints
            $table->foreign('marquee_id')->references('id')->on('marquees')->onDelete('cascade');
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('cascade');
            $table->foreign('from_unit_id')->references('id')->on('inventory_units')->onDelete('restrict');
            $table->foreign('to_unit_id')->references('id')->on('inventory_units')->onDelete('restrict');

            // Unique index to prevent duplicate ambiguous definitions
            $table->unique(
                ['marquee_id', 'inventory_item_id', 'from_unit_id', 'to_unit_id'],
                'iuc_unique_mapping'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_unit_conversions');
    }
};
