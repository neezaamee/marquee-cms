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
        Schema::create('marquees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('logo')->nullable();
            $table->string('address');
            $table->string('city');
            $table->string('province');
            $table->string('phone');
            $table->string('email');
            $table->string('ntn')->nullable();
            $table->string('strn')->nullable(); // Sales Tax Registration Number
            $table->string('tax_authority')->default('FBR'); // e.g., FBR, PRA, SRB, KPRA, BRA
            $table->string('status')->default('active'); // active, inactive, suspended
            $table->foreignId('subscription_plan_id')->constrained('subscription_plans')->onDelete('restrict');
            $table->timestamp('subscription_trial_ends_at')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marquees');
    }
};
