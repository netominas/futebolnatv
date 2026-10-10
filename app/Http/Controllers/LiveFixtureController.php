<?php

namespace App\Http\Controllers;

use App\Models\Fixture;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;

class LiveFixtureController extends Controller
{
    private const LIVE_WINDOW_MINUTES = 120;

    public function __invoke(): View
    {
        $now = CarbonImmutable::now();
        $windowStartsAt = $now->subMinutes(self::LIVE_WINDOW_MINUTES);

        $fixtures = Fixture::query()
            ->select('fixtures.*')
            ->join('competitions', 'competitions.id', '=', 'fixtures.competition_id')
            ->with([
                'competition:id,name,slug,local_logo_path',
                'homeTeam:id,name,slug,local_logo_path',
                'awayTeam:id,name,slug,local_logo_path',
                'channels:id,name,slug,local_logo_path',
            ])
            ->where('fixtures.is_listed', true)
            ->whereHas('channels')
            ->where('fixtures.starts_at', '<=', $now)
            ->where('fixtures.starts_at', '>', $windowStartsAt)
            ->orderBy('competitions.display_priority')
            ->orderBy('competitions.name')
            ->orderBy('fixtures.starts_at')
            ->get();

        return view('fixtures.live', [
            'fixtures' => $fixtures,
            'fixturesByCompetition' => $fixtures->groupBy('competition_id'),
            'liveWindowMinutes' => self::LIVE_WINDOW_MINUTES,
            'now' => $now,
        ]);
    }
}
