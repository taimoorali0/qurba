<?php
// ===== QURBA: bulk-load adhkar from JSON as DRAFT (a reviewer must approve each one) =====
// File format: [{"category":"morning","arabic":"...","transliteration":"...","reference":"Muslim 2723",
//                "repeat":1,"translations":{"en":"...","ur":"..."}}, ...]
namespace App\Console\Commands;

use App\Models\Adhkar;
use App\Models\AdhkarCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportAdhkar extends Command
{
    protected $signature = 'qurba:import-adhkar {file}';
    protected $description = 'Import adhkar from a JSON file as drafts for religious review';

    public function handle(): int
    {
        $rows = json_decode(@file_get_contents($this->argument('file')), true);
        if (! is_array($rows)) { $this->error('File not found or not valid JSON.'); return self::FAILURE; }
        $cats = AdhkarCategory::pluck('id', 'slug');
        $errors = [];
        foreach ($rows as $i => $r) {
            if (empty($r['arabic'])) $errors[] = "#{$i}: arabic is empty";
            if (empty($r['reference'])) $errors[] = "#{$i}: reference is required";
            if (! isset($cats[$r['category'] ?? ''])) $errors[] = "#{$i}: unknown category '" . ($r['category'] ?? '') . "'";
        }
        if ($errors) { foreach (array_slice($errors, 0, 20) as $e) $this->line("  - {$e}"); $this->error('Nothing imported.'); return self::FAILURE; }

        DB::transaction(function () use ($rows, $cats) {
            foreach ($rows as $i => $r) {
                $a = Adhkar::create(['adhkar_category_id' => $cats[$r['category']], 'text_arabic' => trim($r['arabic']),
                    'transliteration' => $r['transliteration'] ?? null, 'reference' => trim($r['reference']),
                    'repeat_count' => max(1, (int) ($r['repeat'] ?? 1)), 'status' => 'draft', 'sort' => $i + 1]);
                foreach ((array) ($r['translations'] ?? []) as $lang => $text) {
                    if ($text) $a->translations()->create(['language_code' => substr($lang, 0, 10), 'text' => $text]);
                }
            }
        });
        $this->info(count($rows) . ' adhkar imported as drafts. Review them in /admin → Adhkar & duas.');
        return self::SUCCESS;
    }
}
