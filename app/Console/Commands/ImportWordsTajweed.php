<?php
// ===== QURBA: import word-by-word and tajweed data (built by scripts/build_words_tajweed.py) =====
namespace App\Console\Commands;

use App\Models\ContentAuditLog;
use App\Models\ContentSource;
use App\Models\QuranAyahData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportWordsTajweed extends Command
{
    protected $signature = 'qurba:import-words-tajweed {--folder= : Folder with manifest.json (default: resources/content/2026-10-words-tajweed)}';
    protected $description = 'Import word-by-word meanings and tajweed annotations as content awaiting religious review';

    public function handle(): int
    {
        $folder = $this->option('folder') ?: resource_path('content/2026-10-words-tajweed');
        $manifest = json_decode((string) @file_get_contents($folder.'/manifest.json'), true);
        if (! is_array($manifest['editions'] ?? null)) {
            $this->error("No manifest.json in {$folder}.");
            return self::FAILURE;
        }

        foreach ($manifest['editions'] as $e) {
            $path = $folder.'/'.$e['file'];
            if (! is_file($path) || hash_file('sha256', $path) !== $e['sha256']) {
                $this->error("{$e['file']} is missing or its checksum does not match. Nothing written.");
                return self::FAILURE;
            }
        }

        try {
            DB::transaction(function () use ($manifest, $folder) {
                foreach ($manifest['editions'] as $e) {
                    $rows = json_decode(file_get_contents($folder.'/'.$e['file']), true, flags: JSON_THROW_ON_ERROR);
                    if (count($rows) !== (int) $e['items']) throw new \RuntimeException("{$e['file']}: expected {$e['items']} ayahs.");

                    $source = ContentSource::firstOrCreate(
                        ['type' => $e['kind'], 'name' => $e['name'], 'edition' => $e['edition']],
                        ['language_code' => $e['lang'], 'author' => $e['author'], 'source_url' => $e['url'], 'license' => $e['license'], 'status' => 'pending_review']
                    );
                    $now = now();
                    foreach (array_chunk($rows, 300, true) as $chunk) {
                        QuranAyahData::upsert(collect($chunk)->map(function ($data, $key) use ($source, $now) {
                            [$s, $a] = array_map('intval', explode(':', $key));
                            return ['content_source_id' => $source->id, 'surah_id' => $s, 'ayah_number' => $a,
                                'data' => json_encode($data, JSON_UNESCAPED_UNICODE), 'created_at' => $now, 'updated_at' => $now];
                        })->values()->all(), ['content_source_id', 'surah_id', 'ayah_number'], ['data', 'updated_at']);
                    }
                    // Any (re)import must be reviewed again before users see it
                    $source->update(['status' => 'pending_review', 'reviewed_at' => null]);
                    ContentAuditLog::record($source, 'imported', null, ['items' => count($rows), 'file_sha256' => $e['sha256']]);
                    $this->info("{$e['name']}: ".count($rows).' ayahs imported, awaiting review.');
                }
            });
        } catch (\Throwable $ex) {
            $this->error($ex->getMessage().' Database changes rolled back.');
            return self::FAILURE;
        }
        return self::SUCCESS;
    }
}
