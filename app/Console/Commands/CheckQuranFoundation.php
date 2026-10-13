<?php

namespace App\Console\Commands;

use App\Services\QuranFoundation;
use Illuminate\Console\Command;

class CheckQuranFoundation extends Command
{
    protected $signature = 'qurba:qf-check {--resources : List available translation and tafsir resources}';
    protected $description = 'Check Quran Foundation server credentials without printing secrets or tokens';

    public function handle(QuranFoundation $api): int
    {
        try {
            $result = $api->get('/chapters');
            if (! isset($result['chapters']) || ! is_array($result['chapters'])) {
                throw new \RuntimeException('Unexpected chapters response.');
            }
            $this->info('Connected to Quran Foundation ('.config('services.quran_foundation.environment').'). Chapters returned: '.count($result['chapters']));
            if (config('services.quran_foundation.environment') === 'prelive') {
                $this->warn('Prelive is for testing; full production content requires approved production access.');
            }
            if ($this->option('resources')) {
                foreach (['translations', 'tafsirs'] as $kind) {
                    $data = $api->get('/resources/'.$kind);
                    $rows = $data[$kind] ?? null;
                    if (! is_array($rows)) throw new \RuntimeException('Unexpected resource response.');
                    $this->line(ucfirst($kind));
                    $this->table(['ID', 'Name', 'Language'], array_map(fn ($r) => [
                        $r['id'] ?? '', $r['name'] ?? '', $r['language_name'] ?? '',
                    ], $rows));
                }
            }
        } catch (\Throwable $e) {
            $this->error($e instanceof \RuntimeException ? $e->getMessage() : 'Connection check failed; no content imported.');
            return self::FAILURE;
        }
        return self::SUCCESS;
    }
}
