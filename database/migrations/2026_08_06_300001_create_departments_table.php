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
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('marquee_id');
            $table->unsignedBigInteger('branch_id');
            $table->string('department_code');
            $table->string('name');
            $table->string('department_type'); // Kitchen Production, Operations, Administration
            $table->unsignedBigInteger('manager_id')->nullable(); // references employees.id
            $table->text('description')->nullable();
            $table->string('status')->default('Active'); // Active, Inactive
            $table->integer('display_order')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Constraints
            $table->foreign('marquee_id')->references('id')->on('marquees')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            
            $table->unique(['branch_id', 'department_code']);
        });

        // Add constraints to employees table now that departments exists
        Schema::table('employees', function (Blueprint $table) {
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            $table->foreign('reporting_manager_id')->references('id')->on('employees')->onDelete('set null');
        });

        // Now link manager_id constraint in departments
        Schema::table('departments', function (Blueprint $table) {
            $table->foreign('manager_id')->references('id')->on('employees')->onDelete('set null');
        });

        // Historical tracking table
        Schema::create('department_employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('employee_id');
            $table->string('designation')->nullable();
            $table->unsignedBigInteger('reporting_to')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status')->default('Active'); // Active, Inactive, Transferred
            $table->timestamps();

            // Constraints
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('reporting_to')->references('id')->on('employees')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_employees');
        
        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['reporting_manager_id']);
        });

        Schema::dropIfExists('departments');
    }
};
