<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BroadcastChannel extends Model
{
    protected $fillable = ['wosti_id', 'name', 'slug', 'image', 'external_url'];

    public function publicUrl(): string
    {
        return route('channels.show', ['channel' => $this->slug]);
    }

    public function fixtures(): BelongsToMany
    {
        return $this->belongsToMany(Fixture::class)->withTimestamps();
    }
}
