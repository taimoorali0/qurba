<?php
namespace App\Console\Commands;

use App\Models\ContentAuditLog;
use App\Models\ContentEntry;
use App\Models\ContentSource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ImportKanzBooks extends Command
{
    protected $signature = 'qurba:import-kanz-books {folder : Folder containing the two uploaded PDFs} {--dry-run}';
    protected $description = 'Stage the uploaded Kanz-ul-Iman PDFs as private library books for review';

    public function handle(): int
    {
        $books = json_decode(file_get_contents(resource_path('content/2026-10-upload/books.json')), true, 512, JSON_THROW_ON_ERROR);
        $folder = rtrim($this->argument('folder'), '/');
        foreach ($books as $book) {
            $file = $folder.'/'.$book['file'];
            if (! is_file($file) || hash_file('sha256', $file) !== $book['sha256']) {
                $this->error('Missing or changed PDF: '.$book['file']);
                return self::FAILURE;
            }
            $this->line($book['title'].' — '.$book['pages'].' pages; hash verified');
        }
        if ($this->option('dry-run')) return self::SUCCESS;
        foreach ($books as $book) {
            $path = 'content/books/'.$book['sha256'].'.pdf';
            $stream = fopen($folder.'/'.$book['file'], 'rb');
            try {
                if (! Storage::disk('local')->put($path, $stream)) {
                    $this->error('Could not store '.$book['file']);
                    return self::FAILURE;
                }
            } finally { fclose($stream); }
        }
        DB::transaction(function () use ($books) {
            foreach ($books as $book) {
                $source = ContentSource::firstOrCreate(['type' => 'other', 'name' => $book['title'], 'edition' => $book['sha256']], [
                    'language_code' => $book['lang'], 'source_url' => $book['url'],
                    'license' => 'Publisher redistribution permission must be confirmed', 'status' => 'pending_review',
                ]);
                $entry = ContentEntry::firstOrNew(['module' => 'books', 'slug' => $book['slug']]);
                $changed = ! $entry->exists || $entry->document_path !== 'content/books/'.$book['sha256'].'.pdf';
                $entry->fill(['content_source_id' => $source->id, 'title' => ['en' => $book['title']],
                    'body' => ['en' => 'Read the original scanned book using the PDF link.'],
                    'summary' => ['en' => $book['pages'].' pages · Original scanned edition'],
                    'reference' => 'Dawat-e-Islami / Maktaba-tul-Madinah',
                    'document_path' => 'content/books/'.$book['sha256'].'.pdf']);
                if ($changed) $entry->status = 'review';
                $entry->save();
                ContentAuditLog::record($source, 'imported', null, ['file_sha256' => $book['sha256'], 'pages' => $book['pages']]);
            }
        });
        $this->info('Books staged for review. Their files remain private until the entries and sources are approved.');
        return self::SUCCESS;
    }
}
