<?php

namespace NocacheDev\Laravel;

use Illuminate\Http\Client\Factory as Http;

/** Thin server-to-server client for the nocache.dev API. */
class NocacheClient
{
    public function __construct(private Http $http, private ?string $token, private string $baseUrl = 'https://nocache.dev') {}

    /** One-off check; no token needed. */
    public function check(string $url): array
    {
        return $this->send('post', '/api/check', ['url' => $url], false);
    }

    public function me(): array { return $this->send('get', '/api/v1/me'); }

    public function monitors(): array { return $this->send('get', '/api/v1/monitors')['monitors'] ?? []; }

    public function addMonitor(string $url, ?string $label = null): array
    {
        return $this->send('post', '/api/v1/monitors', array_filter(['url' => $url, 'label' => $label]));
    }

    public function removeMonitor(string $id): array { return $this->send('delete', '/api/v1/monitors/'.rawurlencode($id)); }

    private function send(string $method, string $path, ?array $body = null, bool $auth = true): array
    {
        $req = $this->http->acceptJson()->timeout(30)->withUserAgent('nocache-dev-laravel/1.0');
        if ($auth) {
            if (! $this->token) {
                throw new NocacheException(0, 'No API token. Set NOCACHE_TOKEN (create one at https://nocache.dev/app).');
            }
            $req = $req->withToken($this->token);
        }
        $res = $body === null ? $req->{$method}(rtrim($this->baseUrl, '/').$path) : $req->{$method}(rtrim($this->baseUrl, '/').$path, $body);
        $json = $res->json() ?? [];
        if (! $res->successful()) {
            throw new NocacheException($res->status(), is_array($json) && isset($json['error']) ? (string) $json['error'] : 'HTTP '.$res->status());
        }
        return is_array($json) ? $json : [];
    }
}
