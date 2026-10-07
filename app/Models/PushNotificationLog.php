<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushNotificationLog extends Model
{
    protected $fillable = [
        'type',
        'title',
        'body',
        'target_url',
        'audience',
        'status',
        'recipients_count',
        'failed_count',
        'dedupe_key',
        'sent_at',
    ];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }
}
