<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KbArticleFeedback extends Model
{
    use HasFactory;

    protected $table = 'kb_article_feedbacks';

    protected $fillable = [
        'article_id',
        'user_id',
        'ip_address',
        'is_helpful',
        'comments',
    ];

    protected $casts = [
        'is_helpful' => 'boolean',
    ];

    /**
     * Article this feedback refers to.
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(KbArticle::class, 'article_id');
    }

    /**
     * User who provided feedback.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
