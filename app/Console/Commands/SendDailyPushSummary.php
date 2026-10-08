<?php

namespace App\Console\Commands;

use App\Jobs\SendPushNotification;
use App\Models\Fixture;
use App\Models\PushNotificationLog;
use Illuminate\Console\Command;

class SendDailyPushSummary extends Command
{
    protected $signature = 'push:daily-summary';

    protected $description = 'Envia o resumo diário de jogos na TV';

    public function handle(): int
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

        SendPushNotification::dispatch($log->id);

        $this->info('Resumo diário adicionado à fila de envio.');

        return self::SUCCESS;
    }
}
