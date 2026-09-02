<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Competition extends Model
{
    protected $fillable = ['wosti_id', 'name', 'slug', 'image', 'local_logo_path'];

    public function logoSource(): ?string
    {
        return $this->local_logo_path ? asset('storage/'.$this->local_logo_path) : null;
    }

    public function fixtures(): HasMany
    {
        return $this->hasMany(Fixture::class);
    }
}
