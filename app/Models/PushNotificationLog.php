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
        'icon_path',
        'image_path',
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

    public function iconUrl(): ?string
    {
        return $this->icon_path ? asset('storage/'.$this->icon_path) : null;
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? asset('storage/'.$this->image_path) : null;
    }
}
