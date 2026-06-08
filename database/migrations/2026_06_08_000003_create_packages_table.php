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
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marquee_id')->constrained('marquees')->onDelete('cascade');
            $table->string('package_name');
            $table->string('package_code');
            $table->text('description')->nullable();
            $table->string('package_type')->default('Custom'); // Silver, Gold, Platinum, VIP, Custom
            $table->integer('minimum_guests')->default(0);
            $table->integer('maximum_guests')->nullable();
            $table->decimal('base_price', 12, 2)->nullable(); // Flat base package cost (optional)
            $table->decimal('per_plate_price', 12, 2);
            $table->boolean('seasonal_package')->default(false);
            $table->date('season_start_date')->nullable();
            $table->date('season_end_date')->nullable();
            $table->string('status')->default('Draft'); // Draft, Active, Inactive, Archived
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->softDeletes();
            $table->timestamps();

            // Unique package code per marquee tenant
            $table->unique(['marquee_id', 'package_code']);
            $table->index(['marquee_id', 'status', 'seasonal_package']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
