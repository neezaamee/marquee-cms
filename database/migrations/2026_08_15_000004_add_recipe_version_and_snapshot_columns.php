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
        // 1. Modify department_productions
        Schema::table('department_productions', function (Blueprint $table) {
            $table->unsignedBigInteger('recipe_version_id')->nullable()->after('recipe_id');
            
            // Rename column (Laravel Schema supports renameColumn natively, but we also handle it safely)
            $table->renameColumn('wastage_qty', 'legacy_finished_good_wastage_qty');

            // Constraint
            $table->foreign('recipe_version_id')->references('id')->on('recipe_versions')->onDelete('set null');
        });

        // 2. Modify department_production_items
        Schema::table('department_production_items', function (Blueprint $table) {
            $table->decimal('planned_recipe_qty', 15, 4)->nullable()->after('quantity');
            $table->unsignedBigInteger('recipe_unit_id')->nullable()->after('planned_recipe_qty');
            $table->decimal('planned_stock_qty', 15, 4)->nullable()->after('recipe_unit_id');
            $table->unsignedBigInteger('stock_unit_id')->nullable()->after('planned_stock_qty');
            $table->decimal('consumed_qty', 15, 4)->nullable()->after('stock_unit_id');
            $table->decimal('wastage_qty', 15, 4)->nullable()->after('consumed_qty');
            $table->decimal('unit_cost', 15, 2)->nullable()->after('wastage_qty');
            $table->unsignedBigInteger('cost_unit_id')->nullable()->after('unit_cost');
            $table->decimal('total_cost', 15, 2)->nullable()->after('cost_unit_id');

            // Constraints
            $table->foreign('recipe_unit_id')->references('id')->on('inventory_units')->onDelete('restrict');
            $table->foreign('stock_unit_id')->references('id')->on('inventory_units')->onDelete('restrict');
            $table->foreign('cost_unit_id')->references('id')->on('inventory_units')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop constraints and columns from department_production_items
        Schema::table('department_production_items', function (Blueprint $table) {
            $table->dropForeign(['recipe_unit_id']);
            $table->dropForeign(['stock_unit_id']);
            $table->dropForeign(['cost_unit_id']);

            $table->dropColumn([
                'planned_recipe_qty',
                'recipe_unit_id',
                'planned_stock_qty',
                'stock_unit_id',
                'consumed_qty',
                'wastage_qty',
                'unit_cost',
                'cost_unit_id',
                'total_cost'
            ]);
        });

        // Drop constraints, rename columns and drop columns on department_productions
        Schema::table('department_productions', function (Blueprint $table) {
            $table->dropForeign(['recipe_version_id']);
            $table->dropColumn('recipe_version_id');
            
            $table->renameColumn('legacy_finished_good_wastage_qty', 'wastage_qty');
        });
    }
};
