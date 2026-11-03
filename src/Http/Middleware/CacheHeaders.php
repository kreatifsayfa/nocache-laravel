<?php

namespace NocacheDev\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends a CDN-friendly Cache-Control for anonymous GET responses, and
 * "private, no-store" when the response could be personal: a signed-in user,
 * a started session (e.g. the route sits in the `web` group with StartSession),
 * or a response that sets a cookie.
 *
 * For shared caches it sends two headers:
 *   Cache-Control:     public, max-age=0, s-maxage=300
 *   CDN-Cache-Control: max-age=300, stale-while-revalidate=60
 * s-maxage implies proxy-revalidate (RFC 9111), so CDNs such as Cloudflare will
 * not serve stale content when it is combined with stale-while-revalidate.
 * CDN-Cache-Control takes precedence on Cloudflare (and other CDNs that support
 * it, RFC 9213) and carries the stale-while-revalidate window without s-maxage.
 *
 * Usage: Route::middleware('nocache.headers') or ->middleware('nocache.headers:600') for a 600 s edge TTL.
 */
class CacheHeaders
{
    public function handle(Request $request, Closure $next, ?string $ttl = null): Response
    {
        $response = $next($request);
        if (! in_array($request->getMethod(), ['GET', 'HEAD'], true) || $response->getStatusCode() >= 500) {
            return $response;
        }
        $current = strtolower((string) $response->headers->get('Cache-Control'));
        $isDefault = $current === '' || $current === 'no-cache, private' || $current === 'private, no-cache';
        $policy = config('nocache.policy');

        if ($this->isPrivate($request, $response)) {
            // Even an app-set "public" policy is unsafe here: the response is tied to a session/cookie.
            if ($isDefault || str_contains($current, 'public') || str_contains($current, 's-maxage')) {
                $response->headers->set('Cache-Control', $policy['private']);
                $response->headers->remove('CDN-Cache-Control');
                $response->headers->remove('Cloudflare-CDN-Cache-Control');
                $response->setVary(array_unique(array_merge($response->getVary(), ['Cookie'])));
            }
            return $response;
        }
        if (! $isDefault) {
            return $response; // the app set its own policy on purpose
        }

        $value = $policy['html'];
        $cdn = $policy['cdn'] ?? null;
        if ($ttl !== null && ctype_digit($ttl)) {
            $value = preg_replace('/s-maxage=\d+/', 's-maxage='.$ttl, $value);
            if ($cdn) {
                $cdn = preg_replace('/(?<![-\w])max-age=\d+/', 'max-age='.$ttl, $cdn);
            }
        }
        $response->headers->set('Cache-Control', $value);
        if ($cdn) {
            $response->headers->set('CDN-Cache-Control', $cdn);
        }
        return $response;
    }

    private function isPrivate(Request $request, Response $response): bool
    {
        if ($request->user()) {
            return true;
        }
        // StartSession runs outside this middleware and adds its cookie *after* we return,
        // so a started session must be detected on the request, not via Set-Cookie.
        if ($request->hasSession() && $request->session()->isStarted()) {
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
