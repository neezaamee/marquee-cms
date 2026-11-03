<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class KbArticle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'summary',
        'content',
        'tags',
        'featured_image',
        'is_internal_only',
        'status',
        'views_count',
        'reading_time_minutes',
        'helpful_count',
        'unhelpful_count',
        'created_by',
        'published_at',
    ];

    protected $casts = [
        'is_internal_only' => 'boolean',
        'views_count' => 'integer',
        'reading_time_minutes' => 'integer',
        'helpful_count' => 'integer',
        'unhelpful_count' => 'integer',
        'published_at' => 'datetime',
    ];

    /**
     * Boot the model to auto-generate slug and reading time.
     */
    protected static function booted(): void
    {
        static::creating(function (KbArticle $article) {
            if (empty($article->slug)) {
                $article->slug = Str::slug($article->title) . '-' . Str::random(5);
            }
            if (empty($article->reading_time_minutes)) {
                $wordCount = str_word_count(strip_tags($article->content));
                $article->reading_time_minutes = max(1, (int) ceil($wordCount / 200));
            }
            if ($article->status === 'published' && empty($article->published_at)) {
                $article->published_at = now();
            }
        });

        static::updating(function (KbArticle $article) {
            if ($article->isDirty('content') && empty($article->reading_time_minutes)) {
                $wordCount = str_word_count(strip_tags($article->content));
                $article->reading_time_minutes = max(1, (int) ceil($wordCount / 200));
            }
            if ($article->isDirty('status') && $article->status === 'published' && empty($article->published_at)) {
                $article->published_at = now();
            }
        });
    }

    /**
     * Category this article belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(SupportCategory::class, 'category_id');
    }

    /**
     * User who authored the article.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Feedback votes on this article.
     */
    public function feedbacks(): HasMany
    {
        return $this->hasMany(KbArticleFeedback::class, 'article_id');
    }

    /**
     * Scope query to published articles.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope query for tenant audience (omitting internal-only articles).
     */
    public function scopePublicForTenants($query)
    {
        return $query->where('status', 'published')->where('is_internal_only', false);
    }

    /**
     * Record a view.
     */
    public function recordView(): void
    {
        $this->increment('views_count');
    }

    /**
     * Record helpful vote.
     */
    public function voteHelpful(): void
    {
        $this->increment('helpful_count');
    }

    /**
     * Record unhelpful vote.
     */
    public function voteUnhelpful(): void
    {
        $this->increment('unhelpful_count');
    }
}
