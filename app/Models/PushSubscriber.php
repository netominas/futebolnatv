<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use NotificationChannels\WebPush\HasPushSubscriptions;

class PushSubscriber extends Model
{
    use HasPushSubscriptions, Notifiable;

    protected $fillable = [
        'installation_key',
        'platform',
        'device_type',
        'daily_summary',
        'kickoff_reminders',
        'permission_granted_at',
        'last_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'daily_summary' => 'boolean',
            'kickoff_reminders' => 'boolean',
            'permission_granted_at' => 'datetime',
            'last_notified_at' => 'datetime',
        ];
    }

    public function scopeSubscribed(Builder $query): Builder
    {
        return $query->whereHas('pushSubscriptions');
    }
}
