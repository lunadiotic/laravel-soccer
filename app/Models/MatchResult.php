<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MatchResult extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['match_id', 'home_score', 'away_score'];

    public function match(): BelongsTo
    {
        return $this->belongsTo(SoccerMatch::class, 'match_id');
    }
}
