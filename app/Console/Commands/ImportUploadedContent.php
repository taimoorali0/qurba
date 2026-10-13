<?php

namespace App\Console\Commands;

use App\Models\ContentAuditLog;
use App\Models\ContentSource;
use App\Models\QuranAyah;
use App\Models\QuranSurah;
use App\Models\QuranTafsir;
use App\Support\Quran\QuranStructure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportUploadedContent extends Command
{
    protected $signature = 'qurba:import-uploaded-content {--dry-run : Validate every edition without writing} {--replace-quran : Explicitly replace a different installed Arabic edition}';
    protected $description = 'Import the October uploaded Quran, seven translations and Jalalayn locally, pending review';

    public function handle(): int
    {
        $folder = resource_path('content/2026-10-upload');
        $manifest = json_decode(file_get_contents($folder.'/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
        $datasets = [];
        // Preflight ALL editions before any database writes.
        foreach ($manifest['editions'] as $edition) {
            $path = $folder.'/'.$edition['file'];
            if (! is_file($path) || hash_file('sha256', $path) !== $edition['sha256']) {
                $this->error('Missing or changed file: '.$edition['file']);
                return self::FAILURE;
            }
            [$rows, $errors] = QuranStructure::readPipeFile($path);
            if ($errors) {
                $this->error($edition['id'].': '.implode('; ', array_slice($errors, 0, 5)));
                return self::FAILURE;
            }
            $datasets[$edition['id']] = $rows;
            $this->line($edition['id'].': '.count($rows).' valid ayah records');
        }
        if ($this->option('dry-run')) {
            $this->info('All nine editions validated. Nothing written.');
            return self::SUCCESS;
        }
        if (QuranSurah::count() !== 114) {
            $this->error('Missing surah records. Run php artisan db:seed --class=QurbaSeeder first.');
            return self::FAILURE;
        }
        $incoming = $datasets['quran-uthmani-min'];
        $installed = QuranAyah::get(['surah_id', 'ayah_number', 'text_uthmani'])->mapWithKeys(fn ($a) => [
            $a->surah_id.':'.$a->ayah_number => ['surah' => $a->surah_id, 'ayah' => $a->ayah_number, 'text' => $a->text_uthmani],
        ])->all();
        $sameQuran = count($installed) === 6236 && QuranStructure::datasetHash($installed) === QuranStructure::datasetHash($incoming);
        if ($installed && ! $sameQuran && ! $this->option('replace-quran')) {
            $this->error('A different Arabic edition is installed. Compare editions before using --replace-quran. Nothing written.');
            return self::FAILURE;
        }
        try {
            DB::transaction(function () use ($manifest, $folder, $datasets, $sameQuran) {
                foreach ($manifest['editions'] as $e) {
                    if ($e['kind'] === 'quran' && $sameQuran) {
                        $this->line('Installed Arabic text is identical; keeping its source and review status.');
                        continue;
                    }
                    if ($e['kind'] === 'tafsir') {
                        $source = ContentSource::firstOrCreate(
                            ['type' => 'tafsir', 'name' => $e['name'], 'edition' => $e['edition']],
                            ['language_code' => $e['lang'], 'author' => $e['author'], 'source_url' => $e['url'], 'license' => $e['license'], 'status' => 'pending_review']
                        );
                        foreach (array_chunk(array_values($datasets[$e['id']]), 200) as $chunk) {
                            $now = now();
                            QuranTafsir::upsert(array_map(fn ($r) => [
                                'content_source_id' => $source->id, 'surah_id' => $r['surah'], 'ayah_number' => $r['ayah'],
                                'text' => $r['text'], 'created_at' => $now, 'updated_at' => $now,
                            ], $chunk), ['content_source_id', 'surah_id', 'ayah_number'], ['text', 'updated_at']);
                        }
                        $source->update(['status' => 'pending_review', 'reviewed_at' => null]);
                        ContentAuditLog::record($source, 'imported', null, ['items' => 6236, 'file_sha256' => $e['sha256']]);
                        continue;
                    }
                    $options = ['file' => $folder.'/'.$e['file'], '--name' => $e['name'], '--edition' => $e['edition'], '--url' => $e['url'], '--license' => $e['license']];
                    if ($e['kind'] === 'translation') {
                        $options += ['--lang' => $e['lang'], '--author' => $e['author']];
                    }
                    if ($this->call($e['kind'] === 'quran' ? 'qurba:import-quran' : 'qurba:import-translation', $options) !== self::SUCCESS) {
                        throw new \RuntimeException('Import failed: '.$e['id']);
                    }
                    ContentSource::where('type', $e['kind'] === 'quran' ? 'quran_text' : 'translation')
                        ->where('name', $e['name'])->where('edition', $e['edition'])
                        ->update(['status' => 'pending_review', 'reviewed_at' => null]);
                }
            });
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage().' Database changes rolled back.');
            return self::FAILURE;
        } finally {
            QuranAyah::$importMode = false;
        }
        $this->info('Imported locally. Review sources and permissions in /admin before publication.');
        return self::SUCCESS;
    }
}
