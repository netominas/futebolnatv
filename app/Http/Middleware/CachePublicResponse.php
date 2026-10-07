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
            $response->headers->set('Cache-Control', 'public, max-age=0, must-revalidate');
            $response->headers->set(
                'Cloudflare-CDN-Cache-Control',
                'public, max-age=60, stale-while-revalidate=300, stale-if-error=86400',
            );
            $response->headers->set('X-Futebol-Cache', 'public');
            $response->headers->remove('Set-Cookie');
        }

        return $response;
    }
}
