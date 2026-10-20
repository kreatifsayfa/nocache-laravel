<?php

namespace NocacheDev\Laravel;

use Illuminate\Http\Client\Factory as Http;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use NocacheDev\Laravel\Http\Middleware\CacheHeaders;

class NocacheServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/nocache.php', 'nocache');
        $this->app->singleton(NocacheClient::class, fn ($app) => new NocacheClient($app->make(Http::class), config('nocache.token'), (string) config('nocache.base_url')));
    }

    public function boot(Router $router): void
    {
        $router->aliasMiddleware('nocache.headers', CacheHeaders::class);
        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../config/nocache.php' => config_path('nocache.php')], 'nocache-config');
            $this->commands([Console\CheckCommand::class, Console\MonitorCommand::class]);
        }
    }
}
