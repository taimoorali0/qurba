<?php
// ===== QURBA: tafsir importer (one edition per run, from the spa5k/tafsir_api dataset) =====
namespace App\Console\Commands;

use App\Models\ContentAuditLog;
use App\Models\ContentSource;
use App\Models\QuranSurah;
use App\Models\QuranTafsir;
use App\Support\Quran\QuranStructure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ImportTafsir extends Command
{
    protected $signature = 'qurba:import-tafsir
        {slug : Edition slug, e.g. ur-tafsir-bayan-ul-quran}
        {--name= : Display name, e.g. "Bayan ul Quran"}
        {--author= : Author, e.g. "Dr. Israr Ahmad"}
        {--lang= : Language code, e.g. ur}
        {--base=https://raw.githubusercontent.com/spa5k/tafsir_api/main/tafsir : Dataset base URL (or a local folder)}
        {--license= : Licence / permission reference}
        {--dry-run : Download and validate only}';

    protected $description = 'Download, validate and import one tafsir edition (stays pending until approved in /admin)';

    public function handle(): int
    {
        $slug = $this->argument('slug');
        if (! preg_match('/^[a-z0-9-]+$/', $slug)) {
            $this->error('Invalid slug.');
            return self::FAILURE;
        }
        if (QuranSurah::count() !== QuranStructure::SURAH_COUNT) {
            $this->error('Import the Quran metadata first (qurba:import-quran-metadata).');
            return self::FAILURE;
        }

        $base = rtrim($this->option('base'), '/');
        $rows = [];
        $errors = [];
        $bar = $this->output->createProgressBar(QuranStructure::SURAH_COUNT);
        foreach (QuranStructure::AYAHS as $surah => $count) {
            $json = $this->fetch("{$base}/{$slug}/{$surah}.json");
            if ($json === null || ! is_array($json['ayahs'] ?? null)) {
                $errors[] = "Surah {$surah}: could not read file";
                $bar->advance();
                continue;
            }
            foreach ($json['ayahs'] as $a) {
                $s = (int) ($a['surah'] ?? 0);
                $n = (int) ($a['ayah'] ?? 0);
                $text = trim((string) ($a['text'] ?? ''));
                if ($s !== $surah || $n < 1 || $n > $count) {
                    $errors[] = "Surah {$surah}: unexpected ayah reference {$s}:{$n}";
                    continue;
                }
                // Some tafsirs explain several ayahs together and leave the others empty
                if ($text !== '') {
                    $rows["{$s}:{$n}"] = ['surah_id' => $s, 'ayah_number' => $n, 'text' => $text];
                }
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();

        if ($errors) {
            $this->error(count($errors) . ' problem(s) found. Nothing imported.');
            foreach (array_slice($errors, 0, 20) as $e) {
                $this->line("  - {$e}");
            }
            return self::FAILURE;
        }
        $this->info(count($rows) . ' ayahs with commentary.');

        if ($this->option('dry-run')) {
            $this->warn('Dry run: nothing written.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($slug, $rows, $base) {
            $source = ContentSource::firstOrCreate(
                ['type' => 'tafsir', 'name' => $this->option('name') ?: $slug, 'edition' => $slug],
                [
                    'language_code' => $this->option('lang'),
                    'author' => $this->option('author'),
                    'source_url' => "{$base}/{$slug}",
                    'license' => $this->option('license'),
                    'status' => 'pending_review',
                ],
            );
            QuranTafsir::where('content_source_id', $source->id)->delete();
            foreach (array_chunk(array_values($rows), 200) as $chunk) {
                $now = now();
                QuranTafsir::insert(array_map(fn ($r) => $r + ['content_source_id' => $source->id, 'created_at' => $now, 'updated_at' => $now], $chunk));
            }
            // New text always goes back to review, even if an earlier import was approved
            if ($source->status !== 'pending_review') {
                $source->update(['status' => 'pending_review']);
            }
            ContentAuditLog::record($source, 'imported', null, ['ayahs' => count($rows), 'from' => "{$base}/{$slug}"]);
            $this->info("Imported as source #{$source->id} (status: {$source->status}). Approve it in /admin → Content sources.");
        });

        return self::SUCCESS;
    }

    private function fetch(string $url): ?array
    {
        if (! str_starts_with($url, 'http')) {
            return is_file($url) ? json_decode(file_get_contents($url), true) : null;
        }
        $res = Http::retry(3, 1000, throw: false)->timeout(30)->get($url);
        return $res->successful() ? $res->json() : null;
    }
}
