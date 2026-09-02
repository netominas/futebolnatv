<?php

namespace App\Http\Controllers;

use App\Models\Fixture;
use App\Models\Team;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $fixtures = Fixture::query()
            ->with(['homeTeam:id,name', 'awayTeam:id,name'])
            ->where('is_listed', true)
            ->whereHas('channels')
            ->orderByDesc('starts_at')
            ->get(['id', 'home_team_id', 'away_team_id', 'starts_at', 'updated_at']);

        $teams = Team::query()
            ->where(fn ($query) => $query->whereHas('homeFixtures', fn ($fixture) => $fixture->where('is_listed', true)->whereHas('channels'))
                ->orWhereHas('awayFixtures', fn ($fixture) => $fixture->where('is_listed', true)->whereHas('channels')))
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'updated_at']);

        return response()
            ->view('sitemap', compact('fixtures', 'teams'))
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
