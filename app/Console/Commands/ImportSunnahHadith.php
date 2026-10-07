<?php

namespace App\Console\Commands;

use App\Models\ContentEntry;
use App\Models\ContentSource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class ImportSunnahHadith extends Command
{
    protected $signature = 'qurba:import-sunnah-hadith {collection=bukhari} {--book= : Import one book} {--file= : Local paginated API JSON response} {--dry-run : Validate only}';
    protected $description = 'Import complete Sunnah.com API responses as Hadith drafts, preserving references';

    public function handle(): int
    {
        $collection = (string) $this->argument('collection');
        if (! preg_match('/^[a-z0-9-]+$/', $collection)) {
            $this->error('Invalid collection identifier.');
            return self::FAILURE;
        }
        $key = config('services.sunnah.key');
        if (! $this->option('file') && ! $key) {
            $this->error('Set SUNNAH_API_KEY in the server .env, or use --file with a complete authorised API export.');
            return self::FAILURE;
        }
        $rows = [];
        try {
            $page = 1;
            $seen = [];
            do {
                if (isset($seen[$page]) || count($seen) >= 1000) {
                    throw new \RuntimeException('Invalid pagination.');
                }
                $seen[$page] = true;
                $payload = $this->option('file')
                    ? json_decode(file_get_contents($this->option('file')), true, 512, JSON_THROW_ON_ERROR)
                    : Http::acceptJson()->withHeaders(['X-API-Key' => $key])->timeout(30)
                        ->get('https://api.sunnah.com/v1/hadiths', array_filter([
                            'collection' => $collection, 'bookNumber' => $this->option('book'),
                            'page' => $page, 'limit' => 100,
                        ], fn ($v) => $v !== null && $v !== ''))->throw()->json();
                Validator::make($payload, [
                    'data' => 'required|array|min:1', 'total' => 'required|integer|min:1',
                    'next' => 'present|nullable|integer|min:1',
                    'data.*.collection' => 'required|string|max:255',
                    'data.*.hadithNumber' => 'required|string|max:100',
                    'data.*.hadith' => 'required|array|min:1',
                    'data.*.hadith.*.lang' => 'required|string|in:ar,en,ur',
                    'data.*.hadith.*.body' => 'required|string',
                ])->validate();
                foreach ($payload['data'] as $row) {
                    if ($row['collection'] !== $collection) {
                        throw new \RuntimeException('Collection mismatch.');
                    }
                    $rows[] = $row;
                }
                $page = $payload['next'];
                if ($this->option('file') && $page !== null) {
                    throw new \RuntimeException('Export is incomplete.');
                }
            } while ($page !== null);
            if (count($rows) !== $payload['total']) {
                throw new \RuntimeException('Incomplete collection.');
            }
        } catch (\Throwable $e) {
            $this->error('Import stopped; nothing written. Check API access and complete response structure.');
            return self::FAILURE;
        }
        $this->info(count($rows).' Hadith records validated.');
        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }
        $added = 0;
        DB::transaction(function () use ($rows, $collection, &$added) {
            $source = ContentSource::firstOrCreate(['type' => 'other', 'name' => 'Sunnah.com '.$collection], [
                'source_url' => 'https://sunnah.com/'.$collection, 'language_code' => 'en',
                'license' => 'API access terms and redistribution require review', 'status' => 'pending_review',
                'redistribution_allowed' => false, 'offline_allowed' => false,
            ]);
            foreach ($rows as $index => $row) {
                $slug = $collection.'-'.$row['hadithNumber'];
                if (ContentEntry::where('module', 'hadith')->where('slug', $slug)->exists()) {
                    continue;
                }
                $body = $title = $grades = [];
                foreach ($row['hadith'] as $text) {
                    $lang = $text['lang'];
                    $body[$lang] = html_entity_decode(strip_tags(preg_replace('/<br\s*\/?\s*>|<\/p>/i', "\n", $text['body'])), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $title[$lang] = trim(strip_tags($text['chapterTitle'] ?? '')) ?: $collection.' '.$row['hadithNumber'];
                    foreach ($text['grades'] ?? [] as $grade) {
                        $grades[] = trim(($grade['grade'] ?? '').' — '.($grade['graded_by'] ?? ''));
                    }
                }
                ContentEntry::create([
                    'module' => 'hadith', 'slug' => $slug, 'collection' => $collection,
                    'chapter' => $row['chapterId'] ?? null, 'reference' => $collection.' '.$row['hadithNumber'],
                    'grading' => mb_substr(implode('; ', array_unique($grades)), 0, 255) ?: null,
                    'title' => $title, 'body' => $body, 'content_source_id' => $source->id,
                    'status' => 'draft', 'sort' => $index + 1,
                ]);
                $added++;
            }
        });
        $this->info("{$added} Hadith drafts imported; existing entries preserved. Review in Content library.");
        return self::SUCCESS;
    }
}
