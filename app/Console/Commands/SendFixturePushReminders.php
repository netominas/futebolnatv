<?php

namespace App\Console\Commands;

use App\Jobs\SendPushNotification;
use App\Models\Fixture;
use App\Models\PushNotificationLog;
use Illuminate\Console\Command;

class SendFixturePushReminders extends Command
{
    protected $signature = 'push:fixture-reminders';

    protected $description = 'Envia lembretes dos jogos prioritários que começam em cerca de 30 minutos';

    public function handle(): int
    {
        if (! config('webpush.vapid.public_key') || ! config('webpush.vapid.private_key')) {
            $this->warn('Web Push não configurado.');

            return self::SUCCESS;
        }

        $fixtures = Fixture::query()
            ->where('is_listed', true)
            ->whereBetween('starts_at', [now()->addMinutes(25), now()->addMinutes(35)])
            ->whereHas('channels')
            ->whereHas('competition', fn ($query) => $query->where('display_priority', '<=', 50))
            ->with(['competition', 'homeTeam', 'awayTeam', 'channels'])
            ->get();

        foreach ($fixtures as $fixture) {
            $dedupeKey = 'fixture-reminder-'.$fixture->id;
            if (PushNotificationLog::where('dedupe_key', $dedupeKey)->exists()) {
                continue;
            }

            $title = $fixture->homeTeam->name.' x '.$fixture->awayTeam->name.' em 30 minutos';
            $body = $fixture->starts_at->format('H:i').' · '.$fixture->competition->name.' · '.$fixture->channels->pluck('name')->join(', ');
            $log = PushNotificationLog::create([
                'type' => 'fixture_reminder',
                'title' => $title,
                'body' => $body,
                'target_url' => $fixture->publicUrl(),
                'audience' => 'reminders',
                'dedupe_key' => $dedupeKey,
            ]);

            SendPushNotification::dispatch($log->id);
        }

        $this->info("{$fixtures->count()} partida(s) processada(s).");

        return self::SUCCESS;
    }
}
