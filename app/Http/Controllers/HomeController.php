<?php

namespace App\Http\Controllers;

use App\Models\Fixture;
use App\Services\FeaturedFixtures;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __construct(private FeaturedFixtures $featuredFixtures) {}

    public function __invoke(): View
    {
        return $this->scheduleFor(CarbonImmutable::today());
    }

    public function byDate(string $date): View
    {
        $selectedDate = CarbonImmutable::createFromFormat('Y-m-d', $date)->startOfDay();

        abort_unless($selectedDate->format('Y-m-d') === $date, 404);

        return $this->scheduleFor($selectedDate);
    }

    public function tomorrow(): View
    {
        return $this->scheduleFor(CarbonImmutable::tomorrow());
    }

    public function redirectToDate(Request $request): RedirectResponse
    {
        $validated = $request->validate(['data' => ['required', 'date_format:Y-m-d']]);

        return redirect()->route('fixtures.by-date', ['date' => $validated['data']]);
    }

    private function scheduleFor(CarbonImmutable $selectedDate): View
    {
        $fixtures = Fixture::query()
            ->select('fixtures.*')
            ->join('competitions', 'competitions.id', '=', 'fixtures.competition_id')
            ->with(['competition:id,name,slug,local_logo_path', 'homeTeam:id,name,slug,local_logo_path', 'awayTeam:id,name,slug,local_logo_path', 'channels:id,name,slug,local_logo_path'])
            ->where('fixtures.is_listed', true)
            ->whereHas('channels')
            ->whereBetween('fixtures.starts_at', [$selectedDate->startOfDay(), $selectedDate->endOfDay()])
            ->orderBy('competitions.display_priority')
            ->orderBy('competitions.name')
            ->orderBy('fixtures.starts_at')
            ->get();

        $isToday = $selectedDate->isToday();
        $isTomorrow = $selectedDate->isTomorrow();
        $formattedDate = $selectedDate->format('d/m/Y');

        return view('home', [
            'canonicalUrl' => $this->scheduleUrl($selectedDate),
            'fixturesByCompetition' => $fixtures->groupBy('competition_id'),
            'featuredFixtures' => $this->featuredFixtures->select($fixtures),
            'selectedDate' => $selectedDate,
            'isToday' => $isToday,
            'isTomorrow' => $isTomorrow,
            'metaDescription' => match (true) {
                $isToday => 'Futebol na TV hoje: veja os jogos ao vivo, horários e onde assistir cada partida na televisão e no streaming no Brasil.',
                $isTomorrow => 'Jogos de amanhã na TV: confira partidas, horários e onde assistir futebol ao vivo na televisão e no streaming no Brasil.',
                default => "Jogos de futebol na TV em {$formattedDate}: veja horários e onde assistir ao vivo no Brasil.",
            },
            'nextDateUrl' => $this->scheduleUrl($selectedDate->addDay()),
            'pageHeading' => match (true) {
                $isToday => 'Jogos na TV hoje',
                $isTomorrow => 'Jogos de amanhã na TV',
                default => 'Jogos na TV em '.$selectedDate->translatedFormat('d \\d\\e F'),
            },
            'pageTitle' => match (true) {
                $isToday => 'Futebol na TV hoje: jogos e onde assistir ao vivo',
                $isTomorrow => 'Jogos de amanhã na TV: horários e onde assistir ao vivo',
                default => "Futebol na TV em {$formattedDate}: jogos e onde assistir",
            },
            'previousDateUrl' => $this->scheduleUrl($selectedDate->subDay()),
            'seoDayLabel' => $isToday ? 'hoje' : ($isTomorrow ? 'amanhã' : $formattedDate),
        ]);
    }

    private function scheduleUrl(CarbonImmutable $date): string
    {
        return match (true) {
            $date->isToday() => route('home'),
            $date->isTomorrow() => route('fixtures.tomorrow'),
            default => route('fixtures.by-date', ['date' => $date->format('Y-m-d')]),
        };
    }
}
