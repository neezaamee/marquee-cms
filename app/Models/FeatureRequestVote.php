<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeatureRequestVote extends Model
{
    use HasFactory;

    protected $fillable = [
        'feature_request_id',
        'user_id',
        'marquee_id',
    ];

    /**
     * Parent feature request.
     */
    public function featureRequest(): BelongsTo
    {
        return $this->belongsTo(FeatureRequest::class, 'feature_request_id');
    }

    /**
     * User who voted.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Marquee company of voter.
     */
    public function marquee(): BelongsTo
    {
        return $this->belongsTo(Marquee::class, 'marquee_id');
    }
}
