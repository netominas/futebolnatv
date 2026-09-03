<?php

namespace App\Http\Controllers;

use App\Models\Fixture;
use App\Models\Team;
use Illuminate\Contracts\View\View;

class TeamController extends Controller
{
    public function index(): View
    {
        $teams = Team::query()
            ->where(fn ($query) => $query->whereHas('homeFixtures', $this->televised(...))->orWhereHas('awayFixtures', $this->televised(...)))
            ->orderBy('name')
            ->paginate(60);

        return view('teams.index', compact('teams'));
    }

    public function show(Team $team): View
    {
        $baseQuery = fn () => Fixture::query()
            ->with(['competition:id,name,slug,local_logo_path', 'homeTeam:id,name,slug,local_logo_path', 'awayTeam:id,name,slug,local_logo_path', 'channels:id,name,slug,local_logo_path'])
            ->where('is_listed', true)
            ->whereHas('channels')
            ->where(fn ($query) => $query->where('home_team_id', $team->id)->orWhere('away_team_id', $team->id));

        $upcomingFixtures = $baseQuery()->where('starts_at', '>=', now())->orderBy('starts_at')->get();
        $pastFixtures = $baseQuery()->where('starts_at', '<', now())->orderByDesc('starts_at')->limit(10)->get();

        abort_if($upcomingFixtures->isEmpty() && $pastFixtures->isEmpty(), 404);

        return view('teams.show', compact('team', 'upcomingFixtures', 'pastFixtures'));
    }

    private function televised($query): void
    {
        $query->where('is_listed', true)->whereHas('channels');
    }
}
