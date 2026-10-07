<?php

namespace App\Services;

use App\Models\PushSubscriber;
use App\Notifications\FutebolWebPush;
use Throwable;

class WebPushSender
{
    /** @return array{sent: int, failed: int} */
    public function send(iterable $subscribers, FutebolWebPush $notification): array
    {
        $sent = 0;
        $failed = 0;

        foreach ($subscribers as $subscriber) {
            try {
                $subscriber->notify($notification);
                $subscriber->forceFill(['last_notified_at' => now()])->save();
                $sent++;
            } catch (Throwable $exception) {
                report($exception);
                $failed++;
            }
        }

        return compact('sent', 'failed');
    }

    public function subscribedQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return PushSubscriber::subscribed()->with('pushSubscriptions');
    }
}
