<?php

namespace Tests\Feature\Qurba;

use App\Models\ContentEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SunnahHadithImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_preserves_text_and_references_without_duplicates(): void
    {
        config(['services.sunnah.key' => 'fixture-key']);
        Http::fake(['api.sunnah.com/*' => Http::response([
            'total' => 1, 'next' => null, 'data' => [[
                'collection' => 'bukhari', 'hadithNumber' => '1', 'chapterId' => '1',
                'hadith' => [['lang' => 'en', 'body' => '<p>Fixture &amp; text</p>',
                    'grades' => [['grade' => 'Fixture grade', 'graded_by' => 'Fixture scholar']]]],
            ]],
        ])]);
        $this->artisan('qurba:import-sunnah-hadith')->assertSuccessful();
        $this->artisan('qurba:import-sunnah-hadith')->assertSuccessful();
        $this->assertSame(1, ContentEntry::count());
        $entry = ContentEntry::first();
        $this->assertSame('bukhari 1', $entry->reference);
        $this->assertSame("Fixture & text\n", $entry->body['en']);
        $this->assertSame('draft', $entry->status);
        $this->assertFalse($entry->source->redistribution_allowed);
    }

    public function test_missing_key_writes_nothing(): void
    {
        config(['services.sunnah.key' => null]);
        Http::fake();
        $this->artisan('qurba:import-sunnah-hadith')->assertFailed();
        Http::assertNothingSent();
        $this->assertDatabaseCount('content_entries', 0);
    }
}
