<?php
use App\Models\ContentSource;
use App\Models\QuranAyah;
use App\Models\QuranTranslation;
use App\Models\QuranTafsir;
use App\Models\ContentEntry;
use Illuminate\Support\Facades\Storage;
use Database\Seeders\QurbaSeeder;

it('validates the uploaded bundle without touching the database', function () {
    $this->artisan('qurba:import-uploaded-content', ['--dry-run' => true])->assertExitCode(0);
    expect(QuranAyah::count())->toBe(0);
});

it('imports all editions separately and repeats without duplicate ayahs', function () {
    $this->seed(QurbaSeeder::class);
    $this->artisan('qurba:import-uploaded-content')->assertExitCode(0);
    $this->artisan('qurba:import-uploaded-content')->assertExitCode(0);
    expect(QuranAyah::count())->toBe(6236)
        ->and(QuranTranslation::count())->toBe(7 * 6236)
        ->and(QuranTafsir::count())->toBe(6236)
        ->and(ContentSource::whereIn('type', ['quran_text', 'translation', 'tafsir'])->where('status', 'approved')->count())->toBe(0);
});

it('prevents replacing a different installed Arabic text without an explicit option', function () {
    $this->seed(QurbaSeeder::class);
    $this->artisan('qurba:import-uploaded-content')->assertExitCode(0);
    \Illuminate\Support\Facades\DB::table('quran_ayahs')->where('ayah_key', '1:1')->update(['text_uthmani' => 'different edition']);
    $this->artisan('qurba:import-uploaded-content')->assertExitCode(1);
    expect(QuranAyah::where('ayah_key', '1:1')->value('text_uthmani'))->toBe('different edition');
});

it('only serves a PDF when its entry and source permit publication', function () {
    Storage::fake('local');
    Storage::disk('local')->put('content/books/test.pdf', '%PDF-1.4 test');
    $source = ContentSource::create(['type' => 'other', 'name' => 'Book source', 'status' => 'pending_review', 'redistribution_allowed' => false]);
    $entry = ContentEntry::create(['module' => 'books', 'slug' => 'test-book', 'title' => ['en' => 'Test'], 'body' => ['en' => 'Book'], 'status' => 'published', 'content_source_id' => $source->id, 'document_path' => 'content/books/test.pdf']);
    $url = '/api/v1/library/books/test-book/document';
    $this->get($url)->assertNotFound();
    $source->update(['status' => 'approved']);
    $this->get($url)->assertNotFound();
    $source->update(['redistribution_allowed' => true]);
    $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf');
    $entry->update(['status' => 'review']);
    $this->get($url)->assertNotFound();
});

it('imports translations and tafsir while preserving a different installed Arabic edition', function () {
    $this->seed(QurbaSeeder::class);
    $this->artisan('qurba:import-uploaded-content')->assertExitCode(0);
    \Illuminate\Support\Facades\DB::table('quran_ayahs')->where('ayah_key', '1:1')->update(['text_uthmani' => 'different edition']);
    $before = QuranAyah::orderBy('id')->get()->toArray();
    $this->artisan('qurba:import-uploaded-content', ['--keep-quran' => true])->assertExitCode(0);
    expect(QuranAyah::orderBy('id')->get()->toArray())->toBe($before)
        ->and(QuranTranslation::count())->toBe(7 * 6236)
        ->and(QuranTafsir::count())->toBe(6236);
});

it('rejects conflicting Arabic import options', function () {
    $this->artisan('qurba:import-uploaded-content', ['--keep-quran' => true, '--replace-quran' => true])->assertExitCode(1);
    expect(QuranAyah::count())->toBe(0);
});
