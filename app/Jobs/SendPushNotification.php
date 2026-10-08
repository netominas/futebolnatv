<?php

namespace App\Jobs;

use App\Models\PushNotificationLog;
use App\Notifications\FutebolWebPush;
use App\Services\WebPushSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SendPushNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 180;

    public bool $failOnTimeout = true;

    public function __construct(public readonly int $notificationLogId)
    {
        $this->onConnection('redis_push')->onQueue('push');
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(WebPushSender $sender): void
    {
        $log = PushNotificationLog::find($this->notificationLogId);

        if (! $log || $log->status === 'sent') {
            return;
        }

        $log->update(['status' => 'processing']);

        $query = $sender->subscribedQuery();
        if ($log->audience === 'daily') {
            $query->where('daily_summary', true);
        } elseif ($log->audience === 'reminders') {
            $query->where('kickoff_reminders', true);
        }

        $result = $sender->send(
            $query->cursor(),
            new FutebolWebPush(
                $log->title,
                $log->body,
                $log->target_url,
                $log->dedupe_key ?: 'manual-'.$log->id,
                $log->iconUrl(),
                $log->imageUrl(),
            ),
        );

        $log->update([
            'status' => 'sent',
            'recipients_count' => $result['sent'],
            'failed_count' => $result['failed'],
            'sent_at' => now(),
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        PushNotificationLog::query()
            ->whereKey($this->notificationLogId)
            ->where('status', '!=', 'sent')
            ->update(['status' => 'failed']);
    }
}
