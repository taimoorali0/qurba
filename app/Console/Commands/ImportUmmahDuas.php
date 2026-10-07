<?php

namespace App\Console\Commands;

use App\Models\Adhkar;
use App\Models\AdhkarCategory;
use App\Models\ContentSource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class ImportUmmahDuas extends Command
{
    protected $signature = 'qurba:import-ummah-duas {--file= : Local API JSON response} {--dry-run : Validate without writing}';
    protected $description = 'Import UmmahAPI duas and categories as referenced drafts without changing existing content';

    public function handle(): int
    {
        try {
            $file = $this->option('file');
            $payload = $file
                ? json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR)
                : Http::acceptJson()->timeout(30)->retry(2, 500)->get('https://ummahapi.com/api/duas')->throw()->json();
            $validator = Validator::make($payload, [
                'success' => 'required|accepted', 'data.categories' => 'required|array|min:1',
                'data.categories.*.id' => 'required|string|max:100|distinct',
                'data.categories.*.name' => 'required|string|max:255',
                'data.duas' => 'required|array|min:1', 'data.total' => 'required|integer|min:1',
                'data.duas.*.id' => 'required|integer|distinct',
                'data.duas.*.category' => 'required|string',
                'data.duas.*.arabic' => 'required|string',
                'data.duas.*.translation' => 'required|string',
                'data.duas.*.source' => 'required|string|max:255',
                'data.duas.*.transliteration' => 'nullable|string',
                'data.duas.*.repeat' => 'required|integer|min:1|max:1000',
            ]);
            $validator->validate();
            $data = $payload['data'];
            if (count($data['duas']) !== $data['total']) {
                throw new \RuntimeException('Provider returned an incomplete collection.');
            }
            $categories = array_column($data['categories'], 'name', 'id');
            foreach ($data['duas'] as $row) {
                if (! isset($categories[$row['category']])) {
                    throw new \RuntimeException('Unknown category in provider response.');
                }
            }
        } catch (\Throwable $e) {
            $this->error('Import failed; nothing written. Check the JSON file or provider connection.');
            return self::FAILURE;
        }
        $this->info(count($data['duas']).' duas validated across '.count($categories).' categories.');
        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }
        $added = 0;
        DB::transaction(function () use ($data, $categories, &$added) {
            $source = ContentSource::firstOrCreate(
                ['type' => 'adhkar', 'name' => 'UmmahAPI Duas'],
                ['language_code' => 'en', 'source_url' => 'https://ummahapi.com/duas-api',
                    'license' => 'Redistribution terms require confirmation', 'status' => 'pending_review',
                    'redistribution_allowed' => false, 'offline_allowed' => false],
            );
            foreach ($data['duas'] as $row) {
                $arabic = trim($row['arabic']);
                // Avoid duplicates against existing imports and preserve reviewed translations.
                if (Adhkar::where('text_arabic', $arabic)->exists()) {
                    continue;
                }
                $category = AdhkarCategory::firstOrCreate(
                    ['slug' => 'ummah-'.$row['category']],
                    ['name' => ['en' => $categories[$row['category']]], 'sort' => 100],
                );
                $dua = Adhkar::create([
                    'adhkar_category_id' => $category->id, 'content_source_id' => $source->id,
                    'text_arabic' => $arabic, 'transliteration' => $row['transliteration'] ?? null,
                    'reference' => trim($row['source']), 'repeat_count' => $row['repeat'],
                    'sort' => $row['id'], 'status' => 'draft',
                ]);
                $dua->translations()->create(['language_code' => 'en', 'text' => $row['translation']]);
                $added++;
            }
        });
        $this->info("{$added} new drafts added; existing items preserved. Review source and duas in admin before publishing.");
        return self::SUCCESS;
    }
}
