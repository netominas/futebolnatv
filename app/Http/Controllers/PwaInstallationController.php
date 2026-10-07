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
        $installationKey = hash('sha256', $validated['installation_id']);
        $timestamp = now();

        if ($validated['event'] === 'installed') {
            PwaInstallation::upsert([[
                'installation_key' => $installationKey,
                'platform' => $platform,
                'device_type' => $deviceType,
                'status' => 'installed',
                'installed_at' => $timestamp,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]], ['installation_key'], ['platform', 'device_type', 'status', 'updated_at']);

            PwaInstallation::where('installation_key', $installationKey)
                ->whereNull('installed_at')
                ->update(['installed_at' => $timestamp]);
        } else {
            PwaInstallation::insertOrIgnore([
                'installation_key' => $installationKey,
                'platform' => $platform,
                'device_type' => $deviceType,
                'status' => 'interest',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);

            PwaInstallation::where('installation_key', $installationKey)
                ->where('status', '!=', 'installed')
                ->update([
                    'platform' => $platform,
                    'device_type' => $deviceType,
                    'status' => 'interest',
                    'updated_at' => $timestamp,
                ]);
        }

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
