<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PwaInstallation;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PwaInstallationController extends Controller
{
    public function index(): View
    {
        $today = now()->startOfDay();
        $metrics = [
            'total' => PwaInstallation::installed()->count(),
            'today' => PwaInstallation::installed()->where('installed_at', '>=', $today)->count(),
            'sevenDays' => PwaInstallation::installed()->where('installed_at', '>=', $today->copy()->subDays(6))->count(),
            'thirtyDays' => PwaInstallation::installed()->where('installed_at', '>=', $today->copy()->subDays(29))->count(),
            'interests' => PwaInstallation::where('status', 'interest')->count(),
        ];

        $platforms = PwaInstallation::installed()
            ->select('platform', DB::raw('count(*) as total'))
            ->groupBy('platform')
            ->orderByDesc('total')
            ->get();

        $dailyTotals = PwaInstallation::installed()
            ->where('installed_at', '>=', $today->copy()->subDays(13))
            ->selectRaw('DATE(installed_at) as day, count(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $daily = collect(range(13, 0))->map(function (int $daysAgo) use ($dailyTotals): array {
            $date = Carbon::today()->subDays($daysAgo);

            return [
                'label' => $date->translatedFormat('d M'),
                'total' => (int) ($dailyTotals[$date->toDateString()] ?? 0),
            ];
        });

        return view('admin.pwa-installations.index', compact('metrics', 'platforms', 'daily'));
    }
}
