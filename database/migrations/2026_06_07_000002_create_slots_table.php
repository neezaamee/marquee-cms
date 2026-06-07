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
        Schema::create('slots', function (Blueprint $table) {
            $table->id();
            
            // Foreign Keys
            $table->foreignId('marquee_id')->constrained('marquees')->onDelete('cascade');
            
            // Slot details
            $table->string('slot_name');
            $table->time('start_time');
            $table->time('end_time');
            $table->text('description')->nullable();
            
            // Status and tracking
            $table->string('status')->default('active'); // active, inactive
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->softDeletes();
            $table->timestamps();

            // Indexes for Performance
            $table->index(['marquee_id', 'status']);
            $table->unique(['marquee_id', 'slot_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slots');
    }
};
