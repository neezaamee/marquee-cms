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
        // 1. Support Categories
        Schema::create('support_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->index();
            $table->string('type')->default('general')->index(); // 'ticket', 'faq', 'article', 'contact', 'general'
            $table->text('description')->nullable();
            $table->string('icon')->default('fas fa-question-circle');
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. FAQs
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('support_categories')->onDelete('cascade');
            $table->string('question');
            $table->longText('answer');
            $table->string('keywords')->nullable();
            $table->string('tags')->nullable();
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_published')->default(true)->index();
            $table->integer('display_order')->default(0);
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('helpful_count')->default(0);
            $table->unsignedInteger('unhelpful_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // 3. Knowledge Base Articles
        Schema::create('kb_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('support_categories')->onDelete('cascade');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->longText('content');
            $table->string('tags')->nullable();
            $table->string('featured_image')->nullable();
            $table->boolean('is_internal_only')->default(false)->index();
            $table->string('status')->default('published')->index(); // 'draft', 'review', 'published', 'archived'
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedTinyInteger('reading_time_minutes')->default(2);
            $table->unsignedInteger('helpful_count')->default(0);
            $table->unsignedInteger('unhelpful_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 4. Knowledge Base Article Feedback
        Schema::create('kb_article_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('kb_articles')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->boolean('is_helpful')->default(true);
            $table->text('comments')->nullable();
            $table->timestamps();
        });

        // 5. Support Settings
        Schema::create('support_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kb_article_feedbacks');
        Schema::dropIfExists('kb_articles');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('support_categories');
        Schema::dropIfExists('support_settings');
    }
};
