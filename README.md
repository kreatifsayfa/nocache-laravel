# nocache-dev/laravel

CDN-friendly `Cache-Control` for Laravel, and artisan commands for [nocache.dev](https://nocache.dev) checks and monitors. Laravel 10–13, PHP 8.1+ (Laravel 13 needs PHP 8.3+).

```sh
composer require nocache-dev/laravel
```

## Cache headers

Put cacheable pages in a route group **without sessions**. The default `web` group runs `StartSession`, which sets a session cookie on every response; a page that carries a session cookie must never be stored by a CDN, or one visitor's page (CSRF token, flash messages, "logged in as …") can be served to another.

```php
// routes/web.php — keep these out of the `web` middleware group
Route::withoutMiddleware('web')->middleware('nocache.headers')->group(function () {
    Route::get('/', HomeController::class);                          // edge TTL 300 s
    Route::get('/blog/{post}', ShowPost::class)->middleware('nocache.headers:900'); // edge TTL 900 s
});
```

Or register a dedicated file in `bootstrap/app.php` (Laravel 11+):

```php
->withRouting(web: __DIR__.'/../routes/web.php', then: function () {
    Route::middleware('nocache.headers')->group(base_path('routes/public.php'));
})
```

Anonymous GET responses get:

```
Cache-Control:     public, max-age=0, s-maxage=300
CDN-Cache-Control: max-age=300, stale-while-revalidate=60
```

Browsers always revalidate; shared caches keep the page for 300 s. `stale-while-revalidate` lives only in `CDN-Cache-Control`, because `s-maxage` implies `proxy-revalidate` (RFC 9111) and Cloudflare will not serve stale content when the two are combined. Cloudflare uses `CDN-Cache-Control` instead of `Cache-Control` for its own caching decisions, so SWR works there. (On Cloudflare, HTML is only cached if a Cache Rule makes it eligible.)

The middleware sends `private, no-store` (plus `Vary: Cookie`) and no `CDN-Cache-Control` when:

- a user is signed in,
- a session was started for the request (route still in the `web` group), or
- the response sets a cookie.

This also overrides a `public`/`s-maxage` policy you set yourself in those cases. Any other `Cache-Control` you set yourself is kept.

Change the values with `php artisan vendor:publish --tag=nocache-config` (`policy.html`, `policy.cdn` — set `null` to skip `CDN-Cache-Control` — and `policy.private`).

## Artisan

```sh
php artisan nocache:check https://example.com --min-grade=B
NOCACHE_TOKEN=nc_... php artisan nocache:monitor https://example.com
php artisan nocache:monitor --list
```

Config: `NOCACHE_TOKEN`, `NOCACHE_API`, `NOCACHE_MONITOR_URLS` (comma-separated).

## Changelog

- **1.1.0** — Laravel 13 support. Started sessions now force `private, no-store`. Default policy no longer combines `s-maxage` with `stale-while-revalidate`; SWR moved to `CDN-Cache-Control`. Private policy is now `private, no-store` (was `private, no-cache`).
