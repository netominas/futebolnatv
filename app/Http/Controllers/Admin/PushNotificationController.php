<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PushNotificationLog;
use App\Models\PushSubscriber;
use App\Notifications\FutebolWebPush;
use App\Services\WebPushSender;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PushNotificationController extends Controller
{
    public function index(): View
    {
        $metrics = [
            'subscribers' => PushSubscriber::subscribed()->count(),
            'daily' => PushSubscriber::subscribed()->where('daily_summary', true)->count(),
            'reminders' => PushSubscriber::subscribed()->where('kickoff_reminders', true)->count(),
            'sent' => PushNotificationLog::where('status', 'sent')->sum('recipients_count'),
        ];
        $platforms = PushSubscriber::subscribed()
            ->selectRaw('platform, count(*) as total')
            ->groupBy('platform')
            ->orderByDesc('total')
            ->get();
        $logs = PushNotificationLog::latest()->limit(20)->get();

        return view('admin.push.index', compact('metrics', 'platforms', 'logs'));
    }

    public function store(Request $request, WebPushSender $sender): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:80'],
            'body' => ['required', 'string', 'max:180'],
            'target_url' => ['nullable', 'string', 'max:1024'],
            'audience' => ['required', 'in:all,daily,reminders'],
        ]);

        $url = $validated['target_url'] ?: route('home');
        if (! $this->isInternalUrl($url)) {
            return back()->withErrors(['target_url' => 'Use uma URL do Futebol na TV.'])->withInput();
        }

        $query = $sender->subscribedQuery();
        if ($validated['audience'] === 'daily') {
            $query->where('daily_summary', true);
        } elseif ($validated['audience'] === 'reminders') {
            $query->where('kickoff_reminders', true);
        }

        $log = PushNotificationLog::create([
            'type' => 'manual',
            'title' => $validated['title'],
            'body' => $validated['body'],
            'target_url' => $url,
            'audience' => $validated['audience'],
        ]);

        $result = $sender->send(
            $query->cursor(),
            new FutebolWebPush($validated['title'], $validated['body'], $url, 'manual-'.$log->id),
        );

        $log->update([
            'status' => 'sent',
            'recipients_count' => $result['sent'],
            'failed_count' => $result['failed'],
            'sent_at' => now(),
        ]);

        return back()->with('status', "Notificação enviada para {$result['sent']} dispositivo(s).");
    }

    private function isInternalUrl(string $url): bool
    {
        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return true;
        }

        $target = parse_url($url);
        $application = parse_url((string) config('app.url'));

        return isset($target['scheme'], $target['host'], $application['host'])
            && in_array(strtolower($target['scheme']), ['http', 'https'], true)
            && strtolower($target['host']) === strtolower($application['host']);
    }
}
