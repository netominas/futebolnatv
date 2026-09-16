<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CompetitionController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('busca'));
        $competitions = Competition::query()
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->withCount(['fixtures' => fn ($query) => $query->where('is_listed', true)->whereHas('channels')])
            ->orderBy('display_priority')
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        return view('admin.competitions.index', compact('competitions', 'search'));
    }

    public function update(Request $request, Competition $competition): RedirectResponse
    {
        $validated = $request->validate(['display_priority' => ['required', 'integer', 'min:1', 'max:9999']]);
        $competition->update($validated);

        return back()->with('status', "Prioridade de {$competition->name} atualizada.");
    }
}
