<?php

namespace App\Services;

use App\Models\BroadcastChannel;
use App\Models\Competition;
use App\Models\Fixture;
use App\Models\Team;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WostiEventSynchronizer
{
    public function __construct(private readonly WostiClient $client) {}

    /** @return array{received:int, imported:int, skipped:int, channels:int} */
    public function sync(): array
    {
        $events = $this->client->events();
        $result = ['received' => count($events), 'imported' => 0, 'skipped' => 0, 'channels' => 0];
        $seenIds = [];

        foreach ($events as $event) {
            if (! $this->isValidTelevisedEvent($event)) {
                $result['skipped']++;

                continue;
            }

            DB::transaction(function () use ($event, &$result, &$seenIds): void {
                $competition = $this->competition($event['Competition']);
                $homeTeam = $this->team($event['LocalTeam']);
                $awayTeam = $this->team($event['AwayTeam']);
                $startsAt = CarbonImmutable::parse($event['Date'])->timezone(config('app.timezone'));

                $fixture = Fixture::updateOrCreate(
                    ['wosti_id' => (int) $event['Id']],
                    [
                        'competition_id' => $competition->id,
                        'home_team_id' => $homeTeam->id,
                        'away_team_id' => $awayTeam->id,
                        'starts_at' => $startsAt,
                        'is_listed' => true,
                        'last_seen_at' => now(),
                    ],
                );

                $channelIds = collect($event['Channels'])
                    ->filter(fn (mixed $channel): bool => is_array($channel) && isset($channel['Id'], $channel['Name']))
                    ->map(function (array $channel) use (&$result): int {
                        $model = BroadcastChannel::updateOrCreate(
                            ['wosti_id' => (int) $channel['Id']],
                            [
                                'name' => (string) $channel['Name'],
                                'slug' => $this->slug((string) $channel['Name'], (int) $channel['Id']),
                                'image' => $this->nullableString($channel['Image'] ?? null),
                            ],
                        );
                        $result['channels']++;

                        return $model->id;
                    })
                    ->unique()
                    ->values()
                    ->all();

                $fixture->channels()->sync($channelIds);
                $seenIds[] = (int) $event['Id'];
                $result['imported']++;
            });
        }

        if ($seenIds !== []) {
            Fixture::query()
                ->where('starts_at', '>=', now()->subDay())
                ->whereNotIn('wosti_id', $seenIds)
                ->update(['is_listed' => false]);
        }

        return $result;
    }

    /** @param array<string, mixed> $event */
    private function isValidTelevisedEvent(array $event): bool
    {
        return isset(
            $event['Id'],
            $event['Date'],
            $event['LocalTeam']['Id'],
            $event['LocalTeam']['Name'],
            $event['AwayTeam']['Id'],
            $event['AwayTeam']['Name'],
            $event['Competition']['Id'],
            $event['Competition']['Name'],
        ) && is_array($event['Channels'] ?? null)
            && collect($event['Channels'])->contains(
                fn (mixed $channel): bool => is_array($channel) && isset($channel['Id'], $channel['Name']),
            );
    }

    /** @param array<string, mixed> $data */
    private function competition(array $data): Competition
    {
        return Competition::updateOrCreate(
            ['wosti_id' => (int) $data['Id']],
            [
                'name' => (string) $data['Name'],
                'slug' => $this->slug((string) $data['Name'], (int) $data['Id']),
                'image' => $this->nullableString($data['Image'] ?? null),
            ],
        );
    }

    /** @param array<string, mixed> $data */
    private function team(array $data): Team
    {
        return Team::updateOrCreate(
            ['wosti_id' => (int) $data['Id']],
            [
                'name' => (string) $data['Name'],
                'slug' => $this->slug((string) $data['Name'], (int) $data['Id']),
                'image' => $this->nullableString($data['Image'] ?? null),
            ],
        );
    }

    private function slug(string $name, int $id): string
    {
        return Str::slug($name).'-'.$id;
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
