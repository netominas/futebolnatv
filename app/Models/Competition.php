<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Competition extends Model
{
    protected $fillable = ['wosti_id', 'name', 'slug', 'image'];

    public function fixtures(): HasMany
    {
        return $this->hasMany(Fixture::class);
    }
}
