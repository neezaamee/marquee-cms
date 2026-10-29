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
        // 1. Drop the legacy global unique index on employee_id if it exists
        $indexes = Schema::getIndexes('employees');
        $hasGlobalUnique = collect($indexes)->contains(function ($index) {
            return $index['name'] === 'employees_employee_id_unique';
        });

        if ($hasGlobalUnique) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropUnique('employees_employee_id_unique');
            });
        }

        // 2. Add composite unique constraint scoped to marquee
        $hasMarqueeUnique = collect($indexes)->contains(function ($index) {
            return $index['name'] === 'employees_marquee_employee_id_unique';
        });

        if (!$hasMarqueeUnique) {
            Schema::table('employees', function (Blueprint $table) {
                $table->unique(['marquee_id', 'employee_id'], 'employees_marquee_employee_id_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $indexes = Schema::getIndexes('employees');

        $hasMarqueeUnique = collect($indexes)->contains(function ($index) {
            return $index['name'] === 'employees_marquee_employee_id_unique';
        });

        if ($hasMarqueeUnique) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropUnique('employees_marquee_employee_id_unique');
            });
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->unique('employee_id', 'employees_employee_id_unique');
        });
    }
};
