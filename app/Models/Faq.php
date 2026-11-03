<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Faq extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'question',
        'answer',
        'keywords',
        'tags',
        'is_featured',
        'is_published',
        'display_order',
        'views_count',
        'helpful_count',
        'unhelpful_count',
        'created_by',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
        'display_order' => 'integer',
        'views_count' => 'integer',
        'helpful_count' => 'integer',
        'unhelpful_count' => 'integer',
    ];

    /**
     * Category this FAQ belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(SupportCategory::class, 'category_id');
    }

    /**
     * User who authored the FAQ.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope query to published FAQs.
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true)->orderBy('display_order');
    }

    /**
     * Scope query to featured FAQs.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
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
