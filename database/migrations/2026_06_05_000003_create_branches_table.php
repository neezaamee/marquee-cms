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
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marquee_id')->constrained('marquees')->onDelete('cascade');
            $table->string('name');
            $table->string('address');
            $table->string('city');
            $table->string('province');
            $table->string('phone');
            $table->string('status')->default('active'); // active, inactive
            $table->string('fbr_pos_id')->nullable(); // FBR POS Device ID registered with FBR
            $table->string('fbr_pos_key')->nullable(); // POS authorization key / API key
            $table->boolean('fbr_sandbox_mode')->default(true); // Default to sandbox/testing
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
