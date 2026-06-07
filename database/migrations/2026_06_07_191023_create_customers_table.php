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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marquee_id')->constrained('marquees')->onDelete('cascade');
            $table->string('customer_code');
            $table->string('customer_type')->default('Individual'); // Individual, Corporate
            $table->string('first_name');
            $table->string('last_name');
            $table->string('company_name')->nullable();
            $table->string('gender')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('cnic_national_id')->nullable();
            $table->string('ntn_number')->nullable();
            $table->string('email')->nullable();
            $table->string('phone_number');
            $table->string('alternate_phone')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('profile_photo')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('Active'); // Active, Inactive, Blocked
            
            // Referral Fields
            $table->string('referred_by_type')->nullable();
            $table->string('referred_by_name')->nullable();
            $table->string('referred_by_contact')->nullable();
            
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->softDeletes();
            $table->timestamps();

            // Unique composite constraints for multi-tenant isolation
            $table->unique(['marquee_id', 'customer_code']);
            $table->unique(['marquee_id', 'cnic_national_id']);
            $table->unique(['marquee_id', 'email']);

            // Indexes for performance
            $table->index(['marquee_id', 'phone_number']);
            $table->index(['marquee_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
