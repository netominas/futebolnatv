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

        if ($featured->isEmpty()) {
            $featured = $fixtures->take(4);
        }

        $topChannelId = $channelCounts->keys()->first();
        $topChannel = $topChannelId ? $channels->get($topChannelId) : null;
        $topChannelCount = $topChannelId ? (int) $channelCounts->get($topChannelId) : 0;
        $topCompetitionId = $competitionCounts->keys()->first();
        $topCompetition = $topCompetitionId ? $competitions->get($topCompetitionId) : null;
        $timeGroups = $fixtures->groupBy(fn (Fixture $fixture) => $fixture->starts_at->format('H:i'));
        $busiestTime = $timeGroups->sortByDesc->count()->first();
        $busiestTimeLabel = $timeGroups->sortByDesc->count()->keys()->first();
        $dayLabel = $date->locale('pt_BR')->translatedFormat('l');
        $dateLabel = $date->locale('pt_BR')->translatedFormat('d \d\e F \d\e Y');
        $title = $this->title($fixtures->count(), $dayLabel, $topChannel?->name, $topChannelCount, $topCompetition?->name);
        $lastChecked = $fixtures->max('last_seen_at') ?? now();

        $snapshot = [
            'coverage_date' => $date->format('Y-m-d'),
            'fixture_count' => $fixtures->count(),
            'competition_count' => $competitions->count(),
            'channel_count' => $channels->count(),
            'top_channel' => $topChannel ? ['id' => $topChannel->id, 'name' => $topChannel->name, 'count' => $topChannelCount] : null,
            'top_competition' => $topCompetition ? ['id' => $topCompetition->id, 'name' => $topCompetition->name, 'count' => (int) $competitionCounts->get($topCompetitionId)] : null,
            'busiest_time' => $busiestTimeLabel ? ['time' => $busiestTimeLabel, 'count' => $busiestTime?->count() ?? 0] : null,
            'first_kickoff' => $fixtures->first()->starts_at->format('H:i'),
            'last_kickoff' => $fixtures->last()->starts_at->format('H:i'),
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
            'meta_title' => Str::limit($title, 60, ''),
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

    private function title(int $fixtures, string $day, ?string $channel, int $channelCount, ?string $competition): string
    {
        if ($channel && $channelCount >= 2) {
            return "{$channel} concentra {$channelCount} dos {$fixtures} jogos na TV desta {$day}";
        }

        if ($competition) {
            return "{$competition} é destaque entre os {$fixtures} jogos na TV desta {$day}";
        }

        return "Jogos na TV desta {$day}: horários, canais e destaques da programação";
    }

    private function excerpt(int $fixtures, int $competitions, int $channels, ?string $topChannel, int $topChannelCount): string
    {
        $lead = "Levantamento do Futebol na TV identifica {$fixtures} partidas, {$competitions} campeonatos e {$channels} canais ou plataformas na programação do dia.";

        return $topChannel && $topChannelCount >= 2
            ? $lead." {$topChannel} aparece em {$topChannelCount} transmissões."
            : $lead;
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
        $dateLabel = $date->locale('pt_BR')->translatedFormat('l, d \d\e F');
        $competitionCount = $fixtures->pluck('competition_id')->unique()->count();
        $channelCount = $channels->count();
        $topChannelId = $channelCounts->keys()->first();
        $topChannel = $topChannelId ? $channels->get($topChannelId) : null;
        $topChannelCount = $topChannelId ? (int) $channelCounts->get($topChannelId) : 0;

        $paragraphs = [
            "A grade desta {$dateLabel} tem **{$fixtures->count()} jogos com transmissão confirmada para o Brasil**, distribuídos por {$competitionCount} campeonatos e {$channelCount} canais ou plataformas. Os horários vão de **{$fixtures->first()->starts_at->format('H:i')}** a **{$fixtures->last()->starts_at->format('H:i')}**, sempre no horário de Brasília.",
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
