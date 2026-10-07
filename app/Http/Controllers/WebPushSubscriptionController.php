<?php

namespace App\Http\Controllers;

use App\Models\PushSubscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebPushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'installation_id' => ['required', 'string', 'max:100'],
            'subscription.endpoint' => ['required', 'url', 'max:1024'],
            'subscription.keys.p256dh' => ['required', 'string', 'max:512'],
            'subscription.keys.auth' => ['required', 'string', 'max:512'],
            'subscription.content_encoding' => ['nullable', 'in:aes128gcm,aesgcm'],
            'daily_summary' => ['required', 'boolean'],
            'kickoff_reminders' => ['required', 'boolean'],
        ]);

        [$platform, $deviceType] = $this->deviceDetails((string) $request->userAgent());
        $subscriber = PushSubscriber::updateOrCreate(
            ['installation_key' => hash('sha256', $validated['installation_id'])],
            [
                'platform' => $platform,
                'device_type' => $deviceType,
                'daily_summary' => $validated['daily_summary'],
                'kickoff_reminders' => $validated['kickoff_reminders'],
                'permission_granted_at' => now(),
            ],
        );

        $subscription = $validated['subscription'];
        $subscriber->updatePushSubscription(
            $subscription['endpoint'],
            $subscription['keys']['p256dh'],
            $subscription['keys']['auth'],
            $subscription['content_encoding'] ?? 'aes128gcm',
        );

        return response()->json(['subscribed' => true]);
    }

    public function status(Request $request): JsonResponse
    {
        $validated = $request->validate(['installation_id' => ['required', 'string', 'max:100']]);
        $subscriber = PushSubscriber::subscribed()
            ->where('installation_key', hash('sha256', $validated['installation_id']))
            ->first();

        return response()->json([
            'subscribed' => (bool) $subscriber,
            'daily_summary' => $subscriber?->daily_summary ?? true,
            'kickoff_reminders' => $subscriber?->kickoff_reminders ?? false,
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'installation_id' => ['required', 'string', 'max:100'],
            'endpoint' => ['required', 'url', 'max:1024'],
        ]);

        $subscriber = PushSubscriber::where('installation_key', hash('sha256', $validated['installation_id']))->first();
        $subscriber?->deletePushSubscription($validated['endpoint']);

        if ($subscriber && ! $subscriber->pushSubscriptions()->exists()) {
            $subscriber->delete();
        }

        return response()->json(['subscribed' => false]);
    }

    /** @return array{string, string} */
    private function deviceDetails(string $userAgent): array
    {
        $userAgent = strtolower($userAgent);

        if (preg_match('/iphone|ipad|ipod/', $userAgent)) {
            return ['ios', str_contains($userAgent, 'ipad') ? 'tablet' : 'mobile'];
        }
        if (str_contains($userAgent, 'android')) {
            return ['android', str_contains($userAgent, 'mobile') ? 'mobile' : 'tablet'];
        }
        if (str_contains($userAgent, 'windows')) {
            return ['windows', 'desktop'];
        }
        if (str_contains($userAgent, 'macintosh')) {
            return ['macos', 'desktop'];
        }
        if (str_contains($userAgent, 'linux')) {
            return ['linux', 'desktop'];
        }

        return ['other', 'unknown'];
    }
}
