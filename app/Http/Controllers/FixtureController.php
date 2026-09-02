<?php

namespace App\Http\Controllers;

use App\Models\Fixture;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class FixtureController extends Controller
{
    public function __invoke(string $slug, Fixture $fixture): View|RedirectResponse
    {
        abort_unless($fixture->is_listed && $fixture->channels()->exists(), 404);

        $fixture->load(['competition', 'homeTeam', 'awayTeam', 'channels']);

        if ($slug !== $fixture->seoSlug()) {
            return redirect()->to($fixture->publicUrl(), 301);
        }

        $relatedFixtures = Fixture::query()
            ->with(['homeTeam:id,name,slug,local_logo_path', 'awayTeam:id,name,slug,local_logo_path', 'channels:id,name'])
            ->where('competition_id', $fixture->competition_id)
            ->where('is_listed', true)
            ->whereKeyNot($fixture->id)
            ->whereBetween('starts_at', [$fixture->starts_at->startOfDay(), $fixture->starts_at->addDays(7)->endOfDay()])
            ->whereHas('channels')
            ->orderBy('starts_at')
            ->limit(4)
            ->get();

        return view('fixtures.show', compact('fixture', 'relatedFixtures'));
    }
}
