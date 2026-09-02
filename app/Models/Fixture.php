<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Fixture extends Model
{
    protected $fillable = [
        'wosti_id',
        'competition_id',
        'home_team_id',
        'away_team_id',
        'starts_at',
        'is_listed',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'is_listed' => 'boolean',
            'last_seen_at' => 'immutable_datetime',
        ];
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function channels(): BelongsToMany
    {
        return $this->belongsToMany(BroadcastChannel::class)->withTimestamps();
    }
}
