<?php

return [
    // Personal API token from https://nocache.dev/app (only needed for monitors).
    'token' => env('NOCACHE_TOKEN'),
    'base_url' => env('NOCACHE_API', 'https://nocache.dev'),

    // Cache-Control values the middleware sends. Responses that already carry an
    // explicit Cache-Control (other than Laravel's default "no-cache, private") are left alone.
    'policy' => [
        'html' => 'public, max-age=0, s-maxage=300, stale-while-revalidate=60',
        'private' => 'private, no-cache',
    ],

    // URLs `php artisan nocache:monitor` registers when called without arguments.
    'monitor_urls' => array_filter(explode(',', (string) env('NOCACHE_MONITOR_URLS', ''))),
];
