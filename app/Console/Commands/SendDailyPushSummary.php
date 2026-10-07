<?php

namespace App\Console\Commands;

use App\Models\Fixture;
use App\Models\PushNotificationLog;
use App\Models\PushSubscriber;
use App\Notifications\FutebolWebPush;
use App\Services\WebPushSender;
use Illuminate\Console\Command;

class SendDailyPushSummary extends Command
{
    protected $signature = 'push:daily-summary';

    protected $description = 'Envia o resumo diário de jogos na TV';

    public function handle(WebPushSender $sender): int
    {
        if (! config('webpush.vapid.public_key') || ! config('webpush.vapid.private_key')) {
            $this->warn('Web Push não configurado.');

            return self::SUCCESS;
        }

        $dedupeKey = 'daily-summary-'.today()->toDateString();
        if (PushNotificationLog::where('dedupe_key', $dedupeKey)->exists()) {
            $this->info('Resumo diário já enviado.');

            return self::SUCCESS;
        }

        $fixtures = Fixture::query()
            ->where('is_listed', true)
            ->whereBetween('starts_at', [today()->startOfDay(), today()->endOfDay()])
            ->whereHas('channels')
            ->with(['homeTeam', 'awayTeam'])
            ->orderBy('starts_at')
            ->get();

        if ($fixtures->isEmpty()) {
            $this->info('Nenhum jogo televisionado hoje.');

            return self::SUCCESS;
        }

        $title = $fixtures->count().' jogos na TV hoje';
        $highlights = $fixtures->take(3)->map(
            fn (Fixture $fixture) => $fixture->starts_at->format('H:i').' '.$fixture->homeTeam->name.' x '.$fixture->awayTeam->name,
        )->join(' · ');
        $log = PushNotificationLog::create([
            'type' => 'daily_summary',
            'title' => $title,
            'body' => $highlights,
            'target_url' => route('home'),
            'audience' => 'daily',
            'dedupe_key' => $dedupeKey,
        ]);

        $subscribers = PushSubscriber::subscribed()
            ->where('daily_summary', true)
            ->with('pushSubscriptions')
            ->cursor();
        $result = $sender->send(
            $subscribers,
            new FutebolWebPush($title, $highlights, route('home'), $dedupeKey),
        );

        $log->update([
            'status' => 'sent',
            'recipients_count' => $result['sent'],
            'failed_count' => $result['failed'],
            'sent_at' => now(),
        ]);

        $this->info("Resumo enviado para {$result['sent']} dispositivo(s).");

        return self::SUCCESS;
    }
}
