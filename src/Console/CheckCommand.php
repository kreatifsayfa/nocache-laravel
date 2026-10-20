<?php

namespace NocacheDev\Laravel\Console;

use Illuminate\Console\Command;
use NocacheDev\Laravel\NocacheClient;
use NocacheDev\Laravel\NocacheException;

class CheckCommand extends Command
{
    protected $signature = 'nocache:check {url : URL to check} {--min-grade= : Fail (exit 1) below this grade} {--json : Print raw JSON}';
    protected $description = 'Check a URL\'s HTTP caching with nocache.dev';

    public function handle(NocacheClient $client): int
    {
        try {
            $r = $client->check((string) $this->argument('url'));
        } catch (NocacheException $e) {
            $this->error($e->getMessage());
            return 2;
        }
        if ($this->option('json')) {
            $this->line(json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->line(sprintf('<options=bold>%s</> %d/100  %s', $r['grade'] ?? '?', $r['score'] ?? 0, $r['url'] ?? ''));
            $rank = ['critical' => 0, 'warning' => 1, 'info' => 2];
            $issues = $r['issues'] ?? [];
            usort($issues, fn ($a, $b) => ($rank[$a['severity']] ?? 3) <=> ($rank[$b['severity']] ?? 3));
            foreach ($issues as $i) {
                $this->line(sprintf('  %-8s %s', $i['severity'], $i['title']));
            }
            if (! empty($r['shareUrl'])) {
                $this->line('  report: '.$r['shareUrl']);
            }
        }
        $min = strtoupper((string) $this->option('min-grade'));
        if ($min !== '' && strpos('ABCDF', $r['grade'] ?? 'F') > strpos('ABCDF', $min)) {
            $this->error("Grade {$r['grade']} is below {$min}");
            return 1;
        }
        return 0;
    }
}
