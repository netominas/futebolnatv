<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    protected $fillable = ['wosti_id', 'name', 'slug', 'image', 'local_logo_path'];

    public function logoSource(): ?string
    {
        return $this->local_logo_path ? asset('storage/'.$this->local_logo_path) : null;
    }

    public function publicUrl(): string
    {
        return route('teams.show', ['team' => $this->slug]);
    }

    public function homeFixtures(): HasMany
    {
        return $this->hasMany(Fixture::class, 'home_team_id');
    }

    public function awayFixtures(): HasMany
    {
        return $this->hasMany(Fixture::class, 'away_team_id');
    }
}
