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
        if (!Schema::hasTable('leads')) {
            Schema::create('leads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('marquee_id')->constrained('marquees')->onDelete('cascade');
                $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
                $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
                $table->string('name');
                $table->string('phone');
                $table->string('alternate_phone')->nullable();
                $table->string('email')->nullable();
                $table->string('city')->default('Lahore');
                
                // Event specifications
                $table->foreignId('event_type_id')->nullable()->constrained('event_types')->onDelete('set null');
                $table->date('preferred_date')->nullable();
                $table->date('alternate_date')->nullable();
                $table->foreignId('slot_id')->nullable()->constrained('slots')->onDelete('set null');
                $table->foreignId('hall_id')->nullable()->constrained('halls')->onDelete('set null');
                $table->integer('guest_count')->nullable();
                $table->decimal('estimated_budget', 12, 2)->nullable();
                
                // Lead lifecycle & classification
                $table->string('lead_source')->default('walk_in'); // walk_in, call, whatsapp, facebook, instagram, website, referral, other
                $table->string('status')->default('new'); // new, contacted, site_visit, negotiation, converted, lost
                $table->string('priority')->default('warm'); // hot, warm, cold
                $table->date('follow_up_date')->nullable();
                $table->string('lost_reason')->nullable(); // date_unavailable, price_high, capacity_mismatch, chose_competitor, cancelled, other
                $table->text('notes')->nullable();
                
                // Assignment & Conversion
                $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('converted_booking_id')->nullable()->constrained('bookings')->onDelete('set null');
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
                
                $table->timestamps();
                $table->softDeletes();
                
                $table->index(['marquee_id', 'status']);
                $table->index(['marquee_id', 'preferred_date']);
                $table->index(['marquee_id', 'follow_up_date']);
            });
        }

        if (!Schema::hasTable('lead_activities')) {
            Schema::create('lead_activities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lead_id')->constrained('leads')->onDelete('cascade');
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
                $table->string('activity_type')->default('call'); // call, whatsapp, meeting, site_visit, quotation_sent, status_change, note
                $table->text('notes');
                $table->date('follow_up_date')->nullable();
                $table->timestamps();
                
                $table->index(['lead_id', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_activities');
        Schema::dropIfExists('leads');
    }
};
