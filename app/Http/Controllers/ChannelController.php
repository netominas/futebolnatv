<?php

namespace App\Http\Controllers;

use App\Models\BroadcastChannel;
use Illuminate\Contracts\View\View;

class ChannelController extends Controller
{
    public function index(): View
    {
        $channels = BroadcastChannel::query()
            ->whereHas('fixtures', fn ($query) => $query->where('is_listed', true))
            ->withCount(['fixtures' => fn ($query) => $query->where('is_listed', true)->where('starts_at', '>=', now())])
            ->orderBy('name')
            ->paginate(48);

        return view('channels.index', compact('channels'));
    }

    public function show(BroadcastChannel $channel): View
    {
        $baseQuery = fn () => $channel->fixtures()
            ->with(['competition:id,name,local_logo_path', 'homeTeam:id,name,slug,local_logo_path', 'awayTeam:id,name,slug,local_logo_path', 'channels:id,name,slug'])
            ->where('is_listed', true);

        $upcomingFixtures = $baseQuery()->where('starts_at', '>=', now())->orderBy('starts_at')->get();
        $pastFixtures = $baseQuery()->where('starts_at', '<', now())->orderByDesc('starts_at')->limit(10)->get();

        abort_if($upcomingFixtures->isEmpty() && $pastFixtures->isEmpty(), 404);

        return view('channels.show', compact('channel', 'upcomingFixtures', 'pastFixtures'));
    }
}
