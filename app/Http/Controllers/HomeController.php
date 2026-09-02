<?php

namespace App\Http\Controllers;

use App\Models\Fixture;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $fixtures = Fixture::query()
            ->with(['competition:id,name', 'homeTeam:id,name', 'awayTeam:id,name', 'channels:id,name'])
            ->where('is_listed', true)
            ->whereHas('channels')
            ->whereBetween('starts_at', [now()->startOfDay(), now()->addDays(7)->endOfDay()])
            ->orderBy('starts_at')
            ->get()
            ->groupBy(fn (Fixture $fixture): string => $fixture->starts_at->format('Y-m-d'));

        return view('home', ['fixturesByDate' => $fixtures]);
    }
}
