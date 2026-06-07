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
        Schema::create('event_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marquee_id')->constrained('marquees')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade');
            
            $table->string('event_type_name');
            $table->string('event_type_code');
            $table->text('description')->nullable();
            $table->decimal('default_duration_hours', 4, 2)->nullable();
            $table->string('default_slot_preference')->nullable();
            $table->decimal('base_price', 12, 2)->nullable();
            $table->string('status')->default('Active'); // Active, Inactive
            $table->integer('sort_order')->default(0);
            $table->boolean('is_system_default')->default(false);
            
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->softDeletes();
            $table->timestamps();

            // Indexes & Unique constraints
            $table->unique(['marquee_id', 'event_type_code']);
            $table->index(['marquee_id', 'branch_id', 'status']);
            $table->index(['marquee_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_types');
    }
};
