<?php
// ===== QURBA: translation importer (one edition per run) =====
namespace App\Console\Commands;

use App\Models\ContentAuditLog;
use App\Models\ContentSource;
use App\Models\QuranAyah;
use App\Support\Quran\QuranStructure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportQuranTranslation extends Command
{
    protected $signature = 'qurba:import-translation
        {file : Path to a sura|aya|text file}
        {--lang= : Language code, e.g. en or ur}
        {--name= : Translation name, e.g. "Saheeh International"}
        {--author= : Translator}
        {--edition= : Edition / version}
        {--url= : Where it was downloaded from}
        {--license= : Licence name or URL}
        {--dry-run : Validate only}';

    protected $description = 'Validate and import one Quran translation edition';

    public function handle(): int
    {
        foreach (['lang', 'name'] as $required) {
            if (! $this->option($required)) {
                $this->error("--{$required} is required");
                return self::FAILURE;
            }
        }
        if (QuranAyah::count() !== QuranStructure::AYAH_COUNT) {
            $this->error('Import the Arabic Quran text first (qurba:import-quran).');
            return self::FAILURE;
        }

        [$rows, $errors] = QuranStructure::readPipeFile($this->argument('file'));
        if ($errors) {
            $this->error(count($errors) . ' problem(s) found. Nothing imported.');
            foreach (array_slice($errors, 0, 20) as $e) {
                $this->line("  - {$e}");
            }
            return self::FAILURE;
        }
        $datasetHash = QuranStructure::datasetHash($rows);
        $this->info('Structure OK: ' . count($rows) . " ayahs. SHA-256: {$datasetHash}");

        if ($this->option('dry-run')) {
            $this->warn('Dry run: nothing written.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($rows, $datasetHash) {
            $source = ContentSource::firstOrCreate(
                ['type' => 'translation', 'name' => $this->option('name'), 'edition' => $this->option('edition')],
                [
                    'language_code' => $this->option('lang'),
                    'author' => $this->option('author'),
                    'source_url' => $this->option('url'),
                    'license' => $this->option('license'),
                    'status' => 'pending_review',
                ]
            );

            $ids = QuranAyah::pluck('id', 'ayah_key');
            $now = now();
            foreach (array_chunk($rows, 500, true) as $chunk) {
                $data = [];
                foreach ($chunk as $key => $r) {
                    $data[] = [
                        'quran_ayah_id' => $ids[$key],
                        'content_source_id' => $source->id,
                        'language_code' => $this->option('lang'),
                        'text' => $r['text'],
                        'content_hash' => hash('sha256', $r['text']),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                DB::table('quran_translations')->upsert($data, ['quran_ayah_id', 'content_source_id'], ['text', 'content_hash', 'updated_at']);
            }

            DB::table('content_versions')->insert([
                'content_source_id' => $source->id,
                'content_type' => 'translation',
                'version' => $this->option('edition') ?: $now->format('Y.m.d'),
                'dataset_hash' => $datasetHash,
                'item_count' => count($rows),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            ContentAuditLog::record($source, 'imported', null, ['ayahs' => count($rows), 'dataset_hash' => $datasetHash]);
        });

        $this->info('Imported as "pending_review".');
        return self::SUCCESS;
    }
}
