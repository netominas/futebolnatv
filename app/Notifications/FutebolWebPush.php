<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class FutebolWebPush extends Notification
{
    public function __construct(
        public readonly string $title,
        public readonly string $body,
        public readonly string $url,
        public readonly string $tag = 'futebol-na-tv',
        public readonly ?string $iconUrl = null,
        public readonly ?string $imageUrl = null,
    ) {}

    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        $message = (new WebPushMessage)
            ->title($this->title)
            ->body($this->body)
            ->icon($this->iconUrl ?: asset('images/pwa/icon-192.png'))
            ->badge('/images/pwa/icon-192.png')
            ->lang('pt-BR')
            ->tag($this->tag)
            ->vibrate([180, 80, 180])
            ->action('Ver detalhes', 'open')
            ->data(['url' => $this->url])
            ->options(['TTL' => 3600, 'urgency' => 'normal']);

        if ($this->imageUrl) {
            $message->image($this->imageUrl);
        }

        return $message;
    }
}
