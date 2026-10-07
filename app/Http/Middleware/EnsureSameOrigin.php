<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSameOrigin
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedOrigin = $this->origin((string) config('app.url'));
        $requestOrigin = $this->origin((string) $request->headers->get('Origin'));
        $refererOrigin = $this->origin((string) $request->headers->get('Referer'));

        abort_unless(
            $expectedOrigin !== null
                && ($requestOrigin === $expectedOrigin || ($requestOrigin === null && $refererOrigin === $expectedOrigin)),
            403,
        );

        return $next($request);
    }

    private function origin(string $url): ?string
    {
        if ($url === '') {
            return null;
        }

        $parts = parse_url($url);
        if (! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $scheme = strtolower($parts['scheme']);
        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        return $scheme.'://'.strtolower($parts['host']).$port;
    }
}
