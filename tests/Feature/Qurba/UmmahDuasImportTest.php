<?php

namespace Tests\Feature\Qurba;

use App\Models\Adhkar;
use App\Models\ContentSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UmmahDuasImportTest extends TestCase
{
    use RefreshDatabase;

    private function payload(): array
    {
        return ['success' => true, 'data' => ['total' => 1,
            'categories' => [['id' => 'sleep', 'name' => 'Sleep']],
            'duas' => [['id' => 1, 'category' => 'sleep', 'arabic' => 'fixture Arabic',
                'translation' => 'fixture translation', 'source' => 'fixture reference', 'repeat' => 1]],
        ]];
    }

    public function test_import_is_repeatable_and_does_not_approve_content(): void
    {
        Http::fake(['ummahapi.com/*' => Http::response($this->payload())]);
        $this->artisan('qurba:import-ummah-duas')->assertSuccessful();
        $this->artisan('qurba:import-ummah-duas')->assertSuccessful();
        $this->assertSame(1, Adhkar::count());
        $this->assertSame('draft', Adhkar::first()->status);
        $this->assertSame('sleep-wake', Adhkar::first()->category->slug);
        $this->assertSame('fixture translation', Adhkar::first()->translations()->first()->text);
        $this->assertFalse(ContentSource::first()->redistribution_allowed);
    }

    public function test_invalid_collection_and_dry_run_write_nothing(): void
    {
        Http::fake(['ummahapi.com/*' => Http::response($this->payload())]);
        $this->artisan('qurba:import-ummah-duas --dry-run')->assertSuccessful();
        $this->assertDatabaseCount('adhkar', 0);
        $bad = $this->payload();
        $bad['data']['duas'][0]['category'] = 'unknown';
        Http::fake(['ummahapi.com/*' => Http::response($bad)]);
        $this->artisan('qurba:import-ummah-duas')->assertFailed();
        $this->assertDatabaseCount('content_sources', 0);
    }
}
