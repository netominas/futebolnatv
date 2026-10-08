<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendPushNotification;
use App\Models\PushNotificationLog;
use App\Models\PushSubscriber;
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

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:80'],
            'body' => ['required', 'string', 'max:180'],
            'target_url' => ['nullable', 'string', 'max:1024'],
            'audience' => ['required', 'in:all,daily,reminders'],
            'icon' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024'],
            'image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:3072'],
        ], [
            'icon.image' => 'O ícone precisa ser um arquivo de imagem válido.',
            'icon.mimes' => 'O ícone deve estar em PNG, JPG ou WebP.',
            'icon.max' => 'O ícone deve ter no máximo 1 MB.',
            'image.image' => 'A imagem de destaque precisa ser um arquivo de imagem válido.',
            'image.mimes' => 'A imagem de destaque deve estar em PNG, JPG ou WebP.',
            'image.max' => 'A imagem de destaque deve ter no máximo 3 MB.',
        ]);

        $url = $validated['target_url'] ?: route('home');
        if (! $this->isInternalUrl($url)) {
            return back()->withErrors(['target_url' => 'Use uma URL do Futebol na TV.'])->withInput();
        }

        $iconPath = $request->file('icon')?->store('push/icons', 'public') ?: null;
        $imagePath = $request->file('image')?->store('push/images', 'public') ?: null;
        $log = PushNotificationLog::create([
            'type' => 'manual',
            'title' => $validated['title'],
            'body' => $validated['body'],
            'target_url' => $url,
            'audience' => $validated['audience'],
            'icon_path' => $iconPath,
            'image_path' => $imagePath,
        ]);

        SendPushNotification::dispatch($log->id);

        return back()->with('status', 'Notificação adicionada à fila de envio.');
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
