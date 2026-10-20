<?php

namespace NocacheDev\Laravel\Tests;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use NocacheDev\Laravel\NocacheServiceProvider;
use Orchestra\Testbench\TestCase;

class PackageTest extends TestCase
{
    protected function getPackageProviders($app) { return [NocacheServiceProvider::class]; }

    protected function defineRoutes($router)
    {
        $router->get('/page', fn () => 'hi')->middleware('nocache.headers');
        $router->get('/ttl', fn () => 'hi')->middleware('nocache.headers:900');
        $router->get('/own', fn () => response('x')->header('Cache-Control', 'no-store'))->middleware('nocache.headers');
        $router->get('/cookie', fn () => response('x')->cookie('cart', '1'))->middleware('nocache.headers');
        $router->post('/post', fn () => 'x')->middleware('nocache.headers');
    }

    public function test_anonymous_html_gets_cdn_policy()
    {
        $this->get('/page')->assertHeader('Cache-Control', 'max-age=0, public, s-maxage=300, stale-while-revalidate=60');
    }

    public function test_s_maxage_parameter()
    {
        $this->assertStringContainsString('s-maxage=900', $this->get('/ttl')->headers->get('Cache-Control'));
    }

    public function test_explicit_header_kept_and_cookie_makes_private()
    {
        $this->get('/own')->assertHeader('Cache-Control', 'no-store, private');
        $this->assertStringContainsString('private', $this->get('/cookie')->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-cache', $this->get('/cookie')->headers->get('Cache-Control'));
    }

    public function test_check_command_and_min_grade()
    {
        Http::fake(['nocache.dev/api/check' => Http::response(['url' => 'https://a.test/', 'grade' => 'D', 'score' => 55, 'issues' => [['severity' => 'info', 'title' => 'I'], ['severity' => 'critical', 'title' => 'C']]])]);
        $this->artisan('nocache:check', ['url' => 'a.test'])->expectsOutputToContain('55/100')->assertExitCode(0);
        $this->artisan('nocache:check', ['url' => 'a.test', '--min-grade' => 'B'])->assertExitCode(1);
        Http::assertSent(fn ($r) => ! $r->hasHeader('Authorization'));
    }

    public function test_monitor_command_sends_token_and_handles_409()
    {
        config(['nocache.token' => 'nc_test']);
        $this->app->forgetInstance(\NocacheDev\Laravel\NocacheClient::class);
        Http::fake([
            'nocache.dev/api/v1/monitors' => Http::sequence()->push(['id' => 'm1', 'url' => 'https://a.test/'], 201)->push(['error' => 'You already monitor this URL'], 409),
        ]);
        $this->artisan('nocache:monitor', ['urls' => ['a.test', 'a.test']])->expectsOutputToContain('Already monitored')->assertExitCode(0);
        Http::assertSent(fn ($r) => $r->hasHeader('Authorization', 'Bearer nc_test'));
    }

    public function test_monitor_without_token_fails_cleanly()
    {
        $this->artisan('nocache:monitor', ['urls' => ['a.test']])->expectsOutputToContain('NOCACHE_TOKEN')->assertExitCode(1);
    }
}
