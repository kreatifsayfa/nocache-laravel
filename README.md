# nocache-dev/laravel

CDN-friendly `Cache-Control` for Laravel, and artisan commands for [nocache.dev](https://nocache.dev) checks and monitors. Laravel 10–12, PHP 8.1+.

```sh
composer require nocache-dev/laravel
```

```php
Route::middleware('nocache.headers')->group(function () {        // s-maxage=300
    Route::get('/', HomeController::class);
});
Route::get('/blog/{post}', ShowPost::class)->middleware('nocache.headers:900'); // s-maxage=900
```

Anonymous GET responses get `public, max-age=0, s-maxage=300, stale-while-revalidate=60`. Signed-in users and responses that set a cookie get `private, no-cache`. A `Cache-Control` you set yourself is kept.

```sh
php artisan nocache:check https://example.com --min-grade=B
NOCACHE_TOKEN=nc_... php artisan nocache:monitor https://example.com
php artisan nocache:monitor --list
php artisan vendor:publish --tag=nocache-config
```

Config: `NOCACHE_TOKEN`, `NOCACHE_API`, `NOCACHE_MONITOR_URLS` (comma-separated).
