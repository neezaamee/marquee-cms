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
        Schema::create('halls', function (Blueprint $table) {
            $table->id();
            
            // Foreign Keys
            $table->foreignId('marquee_id')->constrained('marquees')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            
            // Hall Details
            $table->string('hall_name');
            $table->string('hall_code');
            $table->integer('capacity');
            $table->string('hall_type'); // Marquee, Banquet, Lawn, etc.
            $table->decimal('default_booking_price', 12, 2);
            $table->text('description')->nullable();
            
            // Status and tracking
            $table->string('status')->default('active'); // active, inactive
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->softDeletes();
            $table->timestamps();

            // Indexes for Performance
            $table->index(['branch_id', 'status']);
            $table->unique(['branch_id', 'hall_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('halls');
    }
};
