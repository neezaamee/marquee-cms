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
        // 1. Support Tickets
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique()->index();
            $table->foreignId('marquee_id')->nullable()->constrained('marquees')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('support_categories')->nullOnDelete();
            $table->string('subject');
            $table->longText('description');
            $table->string('priority', 20)->default('normal')->index(); // low, normal, high, urgent
            $table->string('status', 30)->default('open')->index(); // open, assigned, in_progress, awaiting_customer, awaiting_internal, resolved, closed, reopened
            $table->string('related_module', 50)->nullable()->index(); // bookings, accounts, inventory, billing, general
            $table->unsignedBigInteger('related_record_id')->nullable()->index();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('sla_due_at')->nullable();
            $table->boolean('is_sla_breached')->default(false)->index();
            $table->unsignedTinyInteger('satisfaction_rating')->nullable(); // 1 to 5
            $table->text('satisfaction_comment')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Ticket Messages
        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->longText('message');
            $table->boolean('is_internal_note')->default(false)->index();
            $table->boolean('is_customer_read')->default(false)->index();
            $table->boolean('is_agent_read')->default(false)->index();
            $table->timestamps();
        });

        // 3. Ticket Attachments
        Schema::create('ticket_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('ticket_message_id')->nullable()->constrained('ticket_messages')->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();
        });

        // 4. Ticket Status Histories
        Schema::create('ticket_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_status_histories');
        Schema::dropIfExists('ticket_attachments');
        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('support_tickets');
    }
};
