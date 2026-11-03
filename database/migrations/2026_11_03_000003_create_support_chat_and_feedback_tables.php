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
        // 1. Support Conversations (Live Chat)
        Schema::create('support_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('conversation_number')->unique()->index();
            $table->foreignId('marquee_id')->nullable()->constrained('marquees')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('channel', 30)->default('web_chat');
            $table->string('status', 30)->default('active')->index(); // active, waiting_customer, waiting_agent, closed
            $table->string('subject')->nullable();
            $table->foreignId('converted_ticket_id')->nullable()->constrained('support_tickets')->nullOnDelete();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        // 2. Chat Messages
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('support_conversations')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('message');
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->boolean('is_read')->default(false)->index();
            $table->timestamps();
        });

        // 3. Customer Feedbacks
        Schema::create('support_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marquee_id')->nullable()->constrained('marquees')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('feedback_type', 30)->default('general')->index(); // general, bug_report, suggestion, feature_request, ux, service_rating
            $table->string('subject');
            $table->longText('description');
            $table->unsignedTinyInteger('rating')->nullable(); // 1 to 5
            $table->string('module', 50)->nullable()->index();
            $table->string('status', 30)->default('new')->index(); // new, under_review, planned, in_progress, completed, declined, archived
            $table->text('admin_response')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 4. Feature Requests (Community Roadmap)
        Schema::create('feature_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marquee_id')->nullable()->constrained('marquees')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->longText('description');
            $table->string('module', 50)->nullable()->index();
            $table->string('status', 30)->default('submitted')->index(); // submitted, under_review, planned, in_development, released, declined, duplicate
            $table->text('admin_notes')->nullable(); // internal staff note
            $table->text('public_notes')->nullable(); // roadmap public comment
            $table->unsignedInteger('votes_count')->default(1)->index();
            $table->string('target_release', 50)->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 5. Feature Request Votes (Deduplicated)
        Schema::create('feature_request_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feature_request_id')->constrained('feature_requests')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('marquee_id')->nullable()->constrained('marquees')->nullOnDelete();
            $table->timestamps();

            $table->unique(['feature_request_id', 'user_id'], 'uq_feature_vote_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_request_votes');
        Schema::dropIfExists('feature_requests');
        Schema::dropIfExists('support_feedbacks');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('support_conversations');
    }
};
