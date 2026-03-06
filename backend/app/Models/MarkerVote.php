<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarkerVote extends Model
{
    protected $fillable = [
        'marker_id',
        'user_id',
        'vote_type',
    ];

    public const VOTE_LIKE = 'like';
    public const VOTE_DISLIKE = 'dislike';

    public function marker(): BelongsTo
    {
        return $this->belongsTo(Marker::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
