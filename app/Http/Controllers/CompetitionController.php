<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use Illuminate\Contracts\View\View;

class CompetitionController extends Controller
{
    public function index(): View
    {
        $competitions = Competition::query()
            ->whereHas('fixtures', fn ($query) => $query->where('is_listed', true)->whereHas('channels'))
            ->withCount(['fixtures' => fn ($query) => $query->where('is_listed', true)->whereHas('channels')->where('starts_at', '>=', now())])
            ->orderBy('name')
            ->paginate(48);

        return view('competitions.index', compact('competitions'));
    }

    public function show(Competition $competition): View
    {
        $baseQuery = fn () => $competition->fixtures()
            ->with(['competition:id,name,slug,local_logo_path', 'homeTeam:id,name,slug,local_logo_path', 'awayTeam:id,name,slug,local_logo_path', 'channels:id,name,slug,local_logo_path'])
            ->where('is_listed', true)
            ->whereHas('channels');

        $upcomingFixtures = $baseQuery()->where('starts_at', '>=', now())->orderBy('starts_at')->get();
        $pastFixtures = $baseQuery()->where('starts_at', '<', now())->orderByDesc('starts_at')->limit(10)->get();

        abort_if($upcomingFixtures->isEmpty() && $pastFixtures->isEmpty(), 404);

        return view('competitions.show', compact('competition', 'upcomingFixtures', 'pastFixtures'));
    }
}
