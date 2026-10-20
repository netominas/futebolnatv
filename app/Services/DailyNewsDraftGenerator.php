<?php

namespace App\Services;

use App\Models\Fixture;
use App\Models\NewsArticle;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DailyNewsDraftGenerator
{
    public function __construct(private FeaturedFixtures $featuredFixtures) {}

    public function generate(CarbonImmutable $date, User $author): NewsArticle
    {
        $date = $date->startOfDay();
        $fixtures = $this->fixturesFor($date);

        if ($fixtures->isEmpty()) {
            throw ValidationException::withMessages([
                'coverage_date' => 'Não há jogos com transmissão confirmada para esta data.',
            ]);
        }

        $channelCounts = $fixtures
            ->flatMap(fn (Fixture $fixture) => $fixture->channels)
            ->countBy('id')
            ->sortDesc();
        $channels = $fixtures->flatMap(fn (Fixture $fixture) => $fixture->channels)->unique('id')->keyBy('id');
        $competitionCounts = $fixtures->countBy('competition_id')->sortDesc();
        $competitions = $fixtures->pluck('competition')->unique('id')->keyBy('id');
        $featured = $this->featuredFixtures->select($fixtures);
        $featured = $featured
            ->concat($fixtures->reject(fn (Fixture $fixture) => $featured->contains('id', $fixture->id)))
            ->take(4)
            ->values();

        $topChannelId = $channelCounts->keys()->first();
        $topChannel = $topChannelId ? $channels->get($topChannelId) : null;
        $topChannelCount = $topChannelId ? (int) $channelCounts->get($topChannelId) : 0;
        $topCompetitionId = $competitionCounts->keys()->first();
        $topCompetition = $topCompetitionId ? $competitions->get($topCompetitionId) : null;
        $timeGroups = $fixtures->groupBy(fn (Fixture $fixture) => $fixture->starts_at->format('H:i'));
        $chronologicalFixtures = $fixtures
            ->sortBy(fn (Fixture $fixture) => $fixture->starts_at->getTimestamp())
            ->values();
        $busiestTime = $timeGroups->sortByDesc->count()->first();
        $busiestTimeLabel = $timeGroups->sortByDesc->count()->keys()->first();
        $dayLabel = $date->locale('pt_BR')->translatedFormat('l');
        $dateLabel = $date->locale('pt_BR')->translatedFormat('d \d\e F \d\e Y');
        [$title, $titleAngle] = $this->title(
            $date,
            $fixtures->count(),
            $dayLabel,
            $topChannel?->name,
            $topChannelCount,
            $topCompetition?->name,
            $topCompetitionId ? (int) $competitionCounts->get($topCompetitionId) : 0,
            $featured->first(),
            $busiestTimeLabel,
            $busiestTime?->count() ?? 0,
        );
        $lastChecked = $fixtures->max('last_seen_at') ?? now();

        $snapshot = [
            'coverage_date' => $date->format('Y-m-d'),
            'fixture_count' => $fixtures->count(),
            'competition_count' => $competitions->count(),
            'title_angle' => $titleAngle,
            'channel_count' => $channels->count(),
            'top_channel' => $topChannel ? ['id' => $topChannel->id, 'name' => $topChannel->name, 'count' => $topChannelCount] : null,
            'top_competition' => $topCompetition ? ['id' => $topCompetition->id, 'name' => $topCompetition->name, 'count' => (int) $competitionCounts->get($topCompetitionId)] : null,
            'busiest_time' => $busiestTimeLabel ? ['time' => $busiestTimeLabel, 'count' => $busiestTime?->count() ?? 0] : null,
            'first_kickoff' => $chronologicalFixtures->first()->starts_at->format('H:i'),
            'last_kickoff' => $chronologicalFixtures->last()->starts_at->format('H:i'),
            'generated_at' => now()->toIso8601String(),
        ];

        $article = NewsArticle::query()
            ->where('type', 'daily_schedule')
            ->whereDate('coverage_date', $date)
            ->first();

        if ($article?->status === 'published') {
            throw ValidationException::withMessages([
                'coverage_date' => 'A notícia desta data já foi publicada e não será sobrescrita automaticamente.',
            ]);
        }

        $attributes = [
            'type' => 'daily_schedule',
            'status' => $article?->status ?? 'draft',
            'coverage_date' => $date,
            'title' => $title,
            'slug' => $article?->slug ?? $this->uniqueSlug($title),
            'subtitle' => "A programação de {$dateLabel} reúne {$fixtures->count()} partidas em {$competitions->count()} campeonatos, com horários e canais confirmados para o Brasil.",
            'excerpt' => $this->excerpt($fixtures->count(), $competitions->count(), $channels->count(), $topChannel?->name, $topChannelCount),
            'body' => $this->body($date, $fixtures, $featured, $channelCounts, $channels, $timeGroups, $lastChecked),
            'meta_title' => $this->metaTitle(
                $title,
                $titleAngle,
                $date,
                $dayLabel,
                $fixtures->count(),
                $topChannel?->name,
                $topChannelCount,
                $topCompetition?->name,
                $topCompetitionId ? (int) $competitionCounts->get($topCompetitionId) : 0,
                $featured->first(),
                $busiestTimeLabel,
            ),
            'meta_description' => Str::limit($this->excerpt($fixtures->count(), $competitions->count(), $channels->count(), $topChannel?->name, $topChannelCount), 155, ''),
            'source_snapshot' => $snapshot,
            'created_by' => $article?->created_by ?? $author->id,
            'last_fact_checked_at' => $lastChecked,
        ];

        $article = $article
            ? tap($article)->update($attributes)
            : NewsArticle::create($attributes);

        $article->fixtures()->sync($fixtures->pluck('id'));

        return $article->fresh(['author', 'fixtures']);
    }

    /** @return Collection<int, Fixture> */
    private function fixturesFor(CarbonImmutable $date): Collection
    {
        return Fixture::query()
            ->select('fixtures.*')
            ->join('competitions', 'competitions.id', '=', 'fixtures.competition_id')
            ->with(['competition', 'homeTeam', 'awayTeam', 'channels'])
            ->where('fixtures.is_listed', true)
            ->whereHas('channels')
            ->whereBetween('fixtures.starts_at', [$date->startOfDay(), $date->endOfDay()])
            ->orderBy('competitions.display_priority')
            ->orderBy('fixtures.starts_at')
            ->get();
    }

    /** @return array{0: string, 1: string} */
    private function title(
        CarbonImmutable $coverageDate,
        int $fixtures,
        string $day,
        ?string $channel,
        int $channelCount,
        ?string $competition,
        int $competitionCount,
        ?Fixture $featuredFixture,
        ?string $busiestTime,
        int $busiestTimeCount,
    ): array
    {
        $date = $coverageDate->format('d/m');
        $candidates = collect();

        if ($competition && $competitionCount >= 3) {
            $candidates->put('competition', "{$competition} lidera a {$day} ({$date}) com {$competitionCount} jogos na TV");
        }

        if ($featuredFixture) {
            $candidates->put('match', "{$featuredFixture->homeTeam->name} x {$featuredFixture->awayTeam->name} puxa os destaques de {$fixtures} jogos na TV nesta {$day} ({$date})");
        }

        if ($busiestTime && $busiestTimeCount >= 3) {
            $candidates->put('time_slot', "Rodada cheia às {$busiestTime}: veja os {$fixtures} jogos na TV desta {$day} ({$date})");
        }

        if ($channel && $channelCount >= 2) {
            $candidates->put('channel', "{$channel} exibe {$channelCount} dos {$fixtures} jogos na TV desta {$day} ({$date})");
        }

        $candidates->put('overview', "Jogos na TV desta {$day} ({$date}): horários, canais e destaques");

        $previous = NewsArticle::query()
            ->where('type', 'daily_schedule')
            ->whereDate('coverage_date', '<', $coverageDate)
            ->latest('coverage_date')
            ->first();
        $previousAngle = data_get($previous?->source_snapshot, 'title_angle');
        if (! $previousAngle && $previous && data_get($previous->source_snapshot, 'top_channel.name') && str_starts_with($previous->title, data_get($previous->source_snapshot, 'top_channel.name'))) {
            $previousAngle = 'channel';
        }

        $selected = $candidates->first(fn (string $title, string $angle) => $angle !== $previousAngle);
        $angle = $candidates->search($selected, strict: true);

        return [$selected, is_string($angle) ? $angle : 'overview'];
    }

    private function excerpt(int $fixtures, int $competitions, int $channels, ?string $topChannel, int $topChannelCount): string
    {
        $lead = "Levantamento do Futebol na TV identifica {$fixtures} partidas, {$competitions} campeonatos e {$channels} canais ou plataformas na programação do dia.";

        return $topChannel && $topChannelCount >= 2
            ? $lead." {$topChannel} aparece em {$topChannelCount} transmissões."
            : $lead;
    }

    private function wordSafeLimit(string $value, int $limit): string
    {
        if (mb_strlen($value) <= $limit) {
            return $value;
        }

        $truncated = mb_substr($value, 0, $limit + 1);
        $lastSpace = mb_strrpos($truncated, ' ');

        return rtrim($lastSpace === false ? mb_substr($value, 0, $limit) : mb_substr($truncated, 0, $lastSpace), " \t\n\r\0\x0B,;:-");
    }

    private function metaTitle(
        string $title,
        string $angle,
        CarbonImmutable $date,
        string $day,
        int $fixtures,
        ?string $channel,
        int $channelCount,
        ?string $competition,
        int $competitionCount,
        ?Fixture $featuredFixture,
        ?string $busiestTime,
    ): string {
        $dateLabel = $date->format('d/m');
        $candidate = match ($angle) {
            'competition' => "{$competition}: {$competitionCount} jogos na TV nesta {$day} ({$dateLabel})",
            'match' => $featuredFixture
                ? "{$featuredFixture->homeTeam->name} x {$featuredFixture->awayTeam->name}: {$fixtures} jogos na TV ({$dateLabel})"
                : $title,
            'time_slot' => "Jogos na TV às {$busiestTime}: programação de {$dateLabel}",
            'channel' => "{$channel}: {$channelCount} dos {$fixtures} jogos na TV em {$dateLabel}",
            default => $title,
        };

        return $this->wordSafeLimit($candidate, 60);
    }

    /**
     * @param  Collection<int, Fixture>  $fixtures
     * @param  Collection<int, Fixture>  $featured
     * @param  Collection<int|string, int>  $channelCounts
     * @param  Collection<int|string, mixed>  $channels
     * @param  Collection<string, Collection<int, Fixture>>  $timeGroups
     */
    private function body(CarbonImmutable $date, Collection $fixtures, Collection $featured, Collection $channelCounts, Collection $channels, Collection $timeGroups, mixed $lastChecked): string
    {
        $chronologicalFixtures = $fixtures
            ->sortBy(fn (Fixture $fixture) => $fixture->starts_at->getTimestamp())
            ->values();
        $dateLabel = $date->locale('pt_BR')->translatedFormat('l, d \d\e F');
        $competitionCount = $fixtures->pluck('competition_id')->unique()->count();
        $channelCount = $channels->count();
        $topChannelId = $channelCounts->keys()->first();
        $topChannel = $topChannelId ? $channels->get($topChannelId) : null;
        $topChannelCount = $topChannelId ? (int) $channelCounts->get($topChannelId) : 0;

        $paragraphs = [
            "A grade desta {$dateLabel} tem **{$fixtures->count()} jogos com transmissão confirmada para o Brasil**, distribuídos por {$competitionCount} campeonatos e {$channelCount} canais ou plataformas. Os horários vão de **{$chronologicalFixtures->first()->starts_at->format('H:i')}** a **{$chronologicalFixtures->last()->starts_at->format('H:i')}**, sempre no horário de Brasília.",
        ];

        if ($topChannel && $topChannelCount >= 2) {
            $paragraphs[] = "O canal com maior presença no levantamento é **{$topChannel->name}**, relacionado a {$topChannelCount} partidas. A concentração ajuda o torcedor a identificar onde está a maior parte da oferta do dia, mas a programação pode sofrer alterações pelas emissoras.";
        }

        $paragraphs[] = "## Os destaques da programação\n";
        foreach ($featured as $fixture) {
            $channelsLabel = $fixture->channels->pluck('name')->join(', ', ' e ');
            $paragraphs[] = "- **{$fixture->homeTeam->name} x {$fixture->awayTeam->name}**, às {$fixture->starts_at->format('H:i')}, pela {$fixture->competition->name}. Transmissão informada: {$channelsLabel}.";
        }

        $paragraphs[] = "## Como os jogos se distribuem pelos canais\n";
        foreach ($channelCounts->take(8) as $channelId => $count) {
            $channel = $channels->get($channelId);
            $paragraphs[] = "- **{$channel->name}:** {$count} ".($count === 1 ? 'jogo' : 'jogos').'.';
        }

        $simultaneous = $timeGroups->filter(fn (Collection $group) => $group->count() > 1)->sortKeys();
        if ($simultaneous->isNotEmpty()) {
            $paragraphs[] = "## Horários com partidas simultâneas\n";
            foreach ($simultaneous as $time => $group) {
                $matches = $group->map(fn (Fixture $fixture) => "{$fixture->homeTeam->name} x {$fixture->awayTeam->name}")->join('; ', ' e ');
                $paragraphs[] = "- **{$time}:** {$matches}.";
            }
        }

        $checkedAt = $lastChecked?->timezone('America/Sao_Paulo')->format('d/m/Y \à\s H:i') ?? now()->format('d/m/Y \à\s H:i');
        $paragraphs[] = "## Como este levantamento foi feito\n\nA análise considera exclusivamente os jogos com pelo menos uma transmissão de TV ou streaming informada pela Wosti Futebol TV Brasil e armazenada na base do Futebol na TV. A grade foi conferida em **{$checkedAt}**. Horários e canais podem ser alterados pelos detentores dos direitos.";

        return implode("\n\n", $paragraphs);
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $suffix = 2;

        while (NewsArticle::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
