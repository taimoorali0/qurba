<?php
// ===== QURBA: import duas & dhikr from the fitrahive/dua-dhikr dataset (MIT) as DRAFTS =====
namespace App\Console\Commands;

use App\Models\Adhkar;
use App\Models\AdhkarCategory;
use App\Models\ContentAuditLog;
use App\Models\ContentSource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ImportDuaDhikr extends Command
{
    protected $signature = 'qurba:import-dua-dhikr
        {--base=https://raw.githubusercontent.com/fitrahive/dua-dhikr/main/data/dua-dhikr : Dataset base URL (or a local folder)}
        {--dry-run : Download and validate only}';

    protected $description = 'Import duas & dhikr (Arabic, transliteration, English, source) as drafts for religious review';

    /** Dataset folder => Qurba adhkar category */
    private const MAP = [
        'morning-dhikr' => 'morning',
        'evening-dhikr' => 'evening',
        'dhikr-after-salah' => 'after-salah',
        'daily-dua' => 'daily-duas',
        'selected-dua' => 'daily-duas',
    ];

    public function handle(): int
    {
        $cats = AdhkarCategory::pluck('id', 'slug');
        foreach (array_unique(self::MAP) as $slug) {
            if (! isset($cats[$slug])) {
                $this->error("Adhkar category '{$slug}' is missing. Run the Qurba seeder first.");
                return self::FAILURE;
            }
        }

        $base = rtrim($this->option('base'), '/');
        $rows = [];
        $errors = [];
        $skipped = [];
        foreach (self::MAP as $folder => $cat) {
            $items = $this->fetch("{$base}/{$folder}/en.json");
            if (! is_array($items)) {
                $errors[] = "{$folder}: could not read en.json";
                continue;
            }
            foreach ($items as $i => $x) {
                $arabic = trim((string) ($x['arabic'] ?? ''));
                $source = trim((string) ($x['source'] ?? ''));
                if ($arabic === '') {
                    $errors[] = "{$folder} #{$i}: arabic is empty";
                    continue;
                }
                // Every item shown in Qurba needs a reference; leave unsourced ones for a reviewer to add by hand
                if ($source === '') {
                    $skipped[] = "{$folder} #{$i} (" . ($x['title'] ?? 'untitled') . ')';
                    continue;
                }
                // "Recite 3x" → 3
                $repeat = preg_match('/(\d+)\s*x/i', (string) ($x['notes'] ?? ''), $m) ? max(1, min(1000, (int) $m[1])) : 1;
                $rows[] = [
                    'category' => $cat,
                    'arabic' => $arabic,
                    'translit' => trim((string) ($x['latin'] ?? '')) ?: null,
                    'en' => trim((string) ($x['translation'] ?? '')),
                    'reference' => mb_substr($source, 0, 255),
                    'repeat' => $repeat,
                ];
            }
        }
        if ($errors) {
            foreach (array_slice($errors, 0, 20) as $e) {
                $this->line("  - {$e}");
            }
            $this->error('Nothing imported.');
            return self::FAILURE;
        }
        $this->info(count($rows) . ' duas & dhikr found.');
        if ($skipped) {
            $this->warn(count($skipped) . ' skipped (no source given): ' . implode(', ', $skipped));
        }
        if ($this->option('dry-run')) {
            $this->warn('Dry run: nothing written.');
            return self::SUCCESS;
        }

        $added = 0;
        DB::transaction(function () use ($rows, $cats, $base, &$added) {
            $src = ContentSource::firstOrCreate(
                ['type' => 'adhkar', 'name' => 'Dua & Dhikr (fitrahive)'],
                ['language_code' => 'en', 'source_url' => 'https://github.com/fitrahive/dua-dhikr', 'license' => 'MIT', 'status' => 'pending_review'],
            );
            foreach ($rows as $r) {
                $catId = $cats[$r['category']];
                // Re-running the import never duplicates an item
                if (Adhkar::where('adhkar_category_id', $catId)->where('text_arabic', $r['arabic'])->exists()) {
                    continue;
                }
                $sort = (int) Adhkar::where('adhkar_category_id', $catId)->max('sort') + 1;
                $a = Adhkar::create(['adhkar_category_id' => $catId, 'text_arabic' => $r['arabic'], 'transliteration' => $r['translit'],
                    'reference' => $r['reference'], 'repeat_count' => $r['repeat'], 'content_source_id' => $src->id,
                    'status' => 'draft', 'sort' => $sort]);
                if ($r['en'] !== '') {
                    $a->translations()->create(['language_code' => 'en', 'text' => $r['en']]);
                }
                $added++;
            }
            ContentAuditLog::record($src, 'imported', null, ['items' => $added, 'from' => $base]);
        });
        $this->info("{$added} new items imported as drafts (" . (count($rows) - $added) . ' already present). Approve them in /admin → Adhkar & duas.');
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
