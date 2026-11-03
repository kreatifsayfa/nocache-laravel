<?php

return [
    // Personal API token from https://nocache.dev/app (only needed for monitors).
    'token' => env('NOCACHE_TOKEN'),
    'base_url' => env('NOCACHE_API', 'https://nocache.dev'),

    // Cache-Control values the middleware sends. Responses that already carry an
    // explicit Cache-Control (other than Laravel's default "no-cache, private") are left alone.
    'policy' => [
        // Browsers revalidate every time; shared caches keep it for 300 s.
        // No stale-while-revalidate here: s-maxage implies proxy-revalidate, so CDNs ignore it.
        'html' => 'public, max-age=0, s-maxage=300',
        // Sent as CDN-Cache-Control (RFC 9213). Cloudflare prefers it over Cache-Control,
        // so stale-while-revalidate actually works there. Set to null to disable.
        'cdn' => 'max-age=300, stale-while-revalidate=60',
        // Signed-in users, started sessions and cookie-setting responses.
        'private' => 'private, no-store',
    ],

    // URLs `php artisan nocache:monitor` registers when called without arguments.
    'monitor_urls' => array_filter(explode(',', (string) env('NOCACHE_MONITOR_URLS', ''))),
];
