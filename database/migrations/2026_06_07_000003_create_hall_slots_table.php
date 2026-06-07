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
        Schema::create('hall_slots', function (Blueprint $table) {
            $table->id();
            
            // Foreign Keys
            $table->foreignId('marquee_id')->constrained('marquees')->onDelete('cascade');
            $table->foreignId('hall_id')->constrained('halls')->onDelete('cascade');
            $table->foreignId('slot_id')->constrained('slots')->onDelete('cascade');
            
            // Status and tracking
            $table->string('status')->default('active'); // active, inactive
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();

            // Indexes for Performance
            $table->index('marquee_id');
            $table->unique(['hall_id', 'slot_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hall_slots');
    }
};
