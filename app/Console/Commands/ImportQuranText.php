<?php
// ===== QURBA: verified Quran text importer =====
namespace App\Console\Commands;

use App\Models\ContentAuditLog;
use App\Models\ContentSource;
use App\Models\QuranAyah;
use App\Support\Quran\ArabicNormalizer;
use App\Support\Quran\QuranStructure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportQuranText extends Command
{
    protected $signature = 'qurba:import-quran
        {file : Path to a sura|aya|text file}
        {--name=Tanzil Quran Text : Source name}
        {--edition= : Edition / version, e.g. "Uthmani 1.1"}
        {--url= : Where the file was downloaded from}
        {--license= : Licence name or URL}
        {--dry-run : Validate only, write nothing}';

    protected $description = 'Validate and import the Arabic Quran text (structure + hash checked)';

    public function handle(): int
    {
        $file = $this->argument('file');
        if (! is_file($file)) {
            $this->error("File not found: {$file}");
            return self::FAILURE;
        }

        $this->info('Validating structure (114 surahs / 6236 ayahs)...');
        [$rows, $errors] = QuranStructure::readPipeFile($file);

        if ($errors) {
            $this->error(count($errors) . ' problem(s) found. Nothing imported.');
            foreach (array_slice($errors, 0, 20) as $e) {
                $this->line("  - {$e}");
            }
            return self::FAILURE;
        }

        $datasetHash = QuranStructure::datasetHash($rows);
        $this->info('Structure OK: ' . count($rows) . ' ayahs.');
        $this->line("Dataset SHA-256: {$datasetHash}");
        $this->line('Record this hash. Re-downloading the same edition must give the same value.');

        if ($this->option('dry-run')) {
            $this->warn('Dry run: nothing written.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($rows, $datasetHash) {
            $source = ContentSource::firstOrCreate(
                ['type' => 'quran_text', 'name' => $this->option('name'), 'edition' => $this->option('edition')],
                [
                    'language_code' => 'ar',
                    'source_url' => $this->option('url'),
                    'license' => $this->option('license'),
                    'status' => 'pending_review',
                ]
            );

            QuranAyah::$importMode = true;
            $now = now();
            foreach (array_chunk($rows, 500) as $chunk) {
                QuranAyah::upsert(array_map(fn ($r) => [
                    'surah_id' => $r['surah'],
                    'ayah_number' => $r['ayah'],
                    'ayah_key' => "{$r['surah']}:{$r['ayah']}",
                    'text_uthmani' => $r['text'],
                    'text_search' => ArabicNormalizer::forSearch($r['text']),
                    'content_hash' => ArabicNormalizer::hash($r['text']),
                    'content_source_id' => $source->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $chunk), ['ayah_key'], ['text_uthmani', 'text_search', 'content_hash', 'content_source_id', 'updated_at']);
            }
            QuranAyah::$importMode = false;

            DB::table('content_versions')->insert([
                'content_source_id' => $source->id,
                'content_type' => 'quran_text',
                'version' => $this->option('edition') ?: $now->format('Y.m.d'),
                'dataset_hash' => $datasetHash,
                'item_count' => count($rows),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            ContentAuditLog::record($source, 'imported', null, ['ayahs' => count($rows), 'dataset_hash' => $datasetHash]);
        });

        $this->info('Imported. Source status is "pending_review" until a Religious Content Reviewer approves it.');
        return self::SUCCESS;
    }
}
