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
        // 1. Product Changelogs
        Schema::create('product_changelogs', function (Blueprint $table) {
            $table->id();
            $table->string('version', 30)->index(); // e.g. v1.38.0
            $table->string('title');
            $table->text('summary')->nullable();
            $table->longText('description');
            $table->date('release_date')->index();
            $table->string('release_type', 30)->default('improvement')->index(); // new_feature, improvement, bug_fix, security_update, performance, maintenance
            $table->json('affected_modules')->nullable();
            $table->boolean('is_published')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. System Announcements & Service Notices
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('message');
            $table->string('severity', 20)->default('info')->index(); // info, warning, critical, success
            $table->timestamp('start_date')->nullable()->index();
            $table->timestamp('end_date')->nullable()->index();
            $table->boolean('is_published')->default(true)->index();
            $table->string('target_audience', 30)->default('all')->index(); // all, selected_tenants, selected_roles
            $table->json('target_ids')->nullable(); // array of marquee IDs or role names
            $table->boolean('requires_acknowledgment')->default(false)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // 3. Announcement Acknowledgments
        Schema::create('announcement_acknowledgments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained('announcements')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('acknowledged_at');
            $table->timestamps();

            $table->unique(['announcement_id', 'user_id'], 'uq_announcement_user_ack');
        });

        // 4. Contact Requests
        Schema::create('contact_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marquee_id')->nullable()->constrained('marquees')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 30)->nullable();
            $table->string('company_name')->nullable();
            $table->string('category', 50)->default('general')->index(); // general, sales, billing, partnership, enterprise
            $table->string('subject');
            $table->longText('message');
            $table->string('preferred_contact_method', 20)->default('email'); // email, phone, whatsapp
            $table->string('status', 30)->default('new')->index(); // new, contacted, in_progress, completed, closed
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('internal_notes')->nullable();
            $table->date('follow_up_date')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_requests');
        Schema::dropIfExists('announcement_acknowledgments');
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('product_changelogs');
    }
};
