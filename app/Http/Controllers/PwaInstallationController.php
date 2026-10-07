<?php

namespace App\Http\Controllers;

use App\Models\PwaInstallation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PwaInstallationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'installation_id' => ['required', 'string', 'max:100'],
            'event' => ['required', 'in:installed,interest'],
        ]);

        [$platform, $deviceType] = $this->deviceDetails((string) $request->userAgent());
        $installation = PwaInstallation::firstOrNew([
            'installation_key' => hash('sha256', $validated['installation_id']),
        ]);

        $installation->platform = $platform;
        $installation->device_type = $deviceType;

        if ($validated['event'] === 'installed') {
            $installation->status = 'installed';
            $installation->installed_at ??= now();
        } elseif (! $installation->exists || $installation->status !== 'installed') {
            $installation->status = 'interest';
        }

        $installation->save();

        return response()->json(['recorded' => true]);
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
