<?php

namespace NocacheDev\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends a CDN-friendly Cache-Control for anonymous GET HTML responses and
 * "private, no-cache" when a user is signed in or a session cookie is set.
 * Usage: Route::middleware('nocache.headers') or ->middleware('nocache.headers:600') for s-maxage=600.
 */
class CacheHeaders
{
    public function handle(Request $request, Closure $next, ?string $sMaxage = null): Response
    {
        $response = $next($request);
        if (! in_array($request->getMethod(), ['GET', 'HEAD'], true) || $response->getStatusCode() >= 500) {
            return $response;
        }
        $current = strtolower((string) $response->headers->get('Cache-Control'));
        $isDefault = $current === '' || $current === 'no-cache, private' || $current === 'private, no-cache';
        if (! $isDefault) {
            return $response; // the app set its own policy on purpose
        }
        $policy = config('nocache.policy');
        if ($this->isPrivate($request, $response)) {
            $response->headers->set('Cache-Control', $policy['private']);
            return $response;
        }
        $value = $policy['html'];
        if ($sMaxage !== null && ctype_digit($sMaxage)) {
            $value = preg_replace('/s-maxage=\d+/', 's-maxage='.$sMaxage, $value);
        }
        $response->headers->set('Cache-Control', $value);
        return $response;
    }

    private function isPrivate(Request $request, Response $response): bool
    {
        if ($request->user()) {
            return true;
        }
        foreach ($response->headers->getCookies() as $c) {
            if (! $c->isCleared()) {
                return true; // response sets a cookie: never let a shared cache store it
            }
        }
        return false;
    }
}
