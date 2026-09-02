<?php

namespace App\Http\Controllers;

use App\Models\Fixture;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
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

    public function redirectToDate(Request $request): RedirectResponse
    {
        $validated = $request->validate(['data' => ['required', 'date_format:Y-m-d']]);

        return redirect()->route('fixtures.by-date', ['date' => $validated['data']]);
    }

    private function scheduleFor(CarbonImmutable $selectedDate): View
    {
        $fixtures = Fixture::query()
            ->with(['competition:id,name,local_logo_path', 'homeTeam:id,name,slug,local_logo_path', 'awayTeam:id,name,slug,local_logo_path', 'channels:id,name'])
            ->where('is_listed', true)
            ->whereHas('channels')
            ->whereBetween('starts_at', [$selectedDate->startOfDay(), $selectedDate->endOfDay()])
            ->orderBy('starts_at')
            ->get();

        return view('home', [
            'fixturesByCompetition' => $fixtures->groupBy('competition_id'),
            'selectedDate' => $selectedDate,
            'isToday' => $selectedDate->isToday(),
        ]);
    }
}
