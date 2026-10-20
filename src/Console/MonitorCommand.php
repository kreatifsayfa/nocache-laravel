<?php

namespace NocacheDev\Laravel\Console;

use Illuminate\Console\Command;
use NocacheDev\Laravel\NocacheClient;
use NocacheDev\Laravel\NocacheException;

class MonitorCommand extends Command
{
    protected $signature = 'nocache:monitor {urls?* : URLs (defaults to config nocache.monitor_urls)} {--list : List monitors}';
    protected $description = 'Register URLs for nocache.dev monitoring (needs NOCACHE_TOKEN)';

    public function handle(NocacheClient $client): int
    {
        try {
            if ($this->option('list')) {
                $rows = array_map(fn ($m) => [$m['grade'] ?? '-', $m['score'] ?? '', $m['url'], $m['id']], $client->monitors());
                $this->table(['Grade', 'Score', 'URL', 'ID'], $rows);
                return 0;
            }
            $urls = $this->argument('urls') ?: config('nocache.monitor_urls', []);
            if (! $urls) {
                $this->error('No URLs given and NOCACHE_MONITOR_URLS is empty.');
                return 2;
            }
            $failed = 0;
            foreach ($urls as $url) {
                try {
                    $r = $client->addMonitor($url);
                    $this->info("Monitoring {$r['url']}");
                } catch (NocacheException $e) {
                    if ($e->status === 409) {
                        $this->line("Already monitored: {$url}");
                        continue;
                    }
                    $failed++;
                    $this->error("{$url}: {$e->getMessage()}");
                }
            }
            return $failed ? 1 : 0;
        } catch (NocacheException $e) {
            $this->error($e->getMessage());
            return 2;
        }
    }
}
