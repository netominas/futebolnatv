<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PwaInstallation extends Model
{
    protected $fillable = [
        'installation_key',
        'status',
        'platform',
        'device_type',
        'installed_at',
    ];

    protected function casts(): array
    {
        return ['installed_at' => 'datetime'];
    }

    public function scopeInstalled(Builder $query): Builder
    {
        return $query->where('status', 'installed');
    }
}
