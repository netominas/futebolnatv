<?php

namespace App\Http\Controllers;

use App\Models\BroadcastChannel;
use App\Models\Competition;
use App\Models\Team;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));
        $query = mb_substr($query, 0, 80);
        $canSearch = mb_strlen($query) >= 2;

        $teams = collect();
        $competitions = collect();
        $channels = collect();

        if ($canSearch) {
            $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $query).'%';

            $teams = Team::query()
                ->where('name', 'like', $term)
                ->where(fn ($builder) => $builder
                    ->whereHas('homeFixtures', fn ($fixtures) => $fixtures->where('is_listed', true)->whereHas('channels'))
                    ->orWhereHas('awayFixtures', fn ($fixtures) => $fixtures->where('is_listed', true)->whereHas('channels')))
                ->orderBy('name')
                ->limit(12)
                ->get();

            $competitions = Competition::query()
                ->where('name', 'like', $term)
                ->whereHas('fixtures', fn ($fixtures) => $fixtures->where('is_listed', true)->whereHas('channels'))
                ->orderBy('name')
                ->limit(12)
                ->get();

            $channels = BroadcastChannel::query()
                ->where('name', 'like', $term)
                ->whereHas('fixtures', fn ($fixtures) => $fixtures->where('is_listed', true))
                ->orderBy('name')
                ->limit(12)
                ->get();
        }

        return view('search', compact('canSearch', 'channels', 'competitions', 'query', 'teams'));
    }
}
