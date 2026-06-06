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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id')->unique();
            $table->foreignId('marquee_id')->constrained('marquees')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            
            $table->string('name');
            $table->string('cnic');
            $table->string('mobile_number');
            $table->string('designation');
            $table->date('joining_date');
            $table->decimal('salary', 12, 2);
            $table->string('employment_type');
            $table->string('status')->default('active');
            $table->string('photo')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
