<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CachePublicResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (in_array($request->method(), ['GET', 'HEAD'], true) && $response->getStatusCode() === 200) {
            $isLivePage = $request->routeIs('fixtures.live');
            $isSchedulePage = $request->routeIs('home', 'fixtures.tomorrow', 'fixtures.by-date');

            // The browser and the PWA must never reuse stale HTML. Cloudflare has
            // its own short-lived policy below, independent from this header.
            $response->headers->set('Cache-Control', 'private, no-store, max-age=0, must-revalidate');
            $response->headers->set(
                'Cloudflare-CDN-Cache-Control',
                match (true) {
                    $isLivePage => 'public, max-age=10, must-revalidate, stale-if-error=60',
                    $isSchedulePage => 'public, max-age=30, must-revalidate, stale-if-error=300',
                    default => 'public, max-age=60, stale-while-revalidate=300, stale-if-error=86400',
                },
            );
            $response->headers->set('X-Futebol-Cache', 'public');
            $response->headers->remove('Set-Cookie');
        }

        return $response;
    }
}
