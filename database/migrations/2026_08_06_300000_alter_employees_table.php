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
        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedBigInteger('department_id')->nullable()->after('branch_id');
            $table->unsignedBigInteger('reporting_manager_id')->nullable()->after('department_id');

            // Note: Constraints will be added after departments table is created
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['department_id', 'reporting_manager_id']);
        });
    }
};
