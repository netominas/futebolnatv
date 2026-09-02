<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BroadcastChannel;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChannelController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('busca'));
        $channels = BroadcastChannel::query()
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->withCount(['fixtures' => fn ($query) => $query->where('is_listed', true)])
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        return view('admin.channels.index', compact('channels', 'search'));
    }

    public function update(Request $request, BroadcastChannel $channel): RedirectResponse
    {
        $validated = $request->validate(['external_url' => ['nullable', 'url:http,https', 'max:2048']]);
        $channel->update(['external_url' => filled($validated['external_url'] ?? null) ? $validated['external_url'] : null]);

        return back()->with('status', "Link de {$channel->name} atualizado.");
    }
}
