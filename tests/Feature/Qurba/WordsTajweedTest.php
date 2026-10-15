<?php
// ===== QURBA: word-by-word and tajweed import, review gate and API =====
use App\Models\ContentSource;
use App\Models\QuranAyahData;
use Database\Seeders\QurbaSeeder;

it('imports word by word and tajweed for every ayah, awaiting review', function () {
    $this->seed(QurbaSeeder::class);
    $this->artisan('qurba:import-words-tajweed')->assertExitCode(0);
    $this->artisan('qurba:import-words-tajweed')->assertExitCode(0); // repeatable

    expect(ContentSource::whereIn('type', ['word_by_word', 'tajweed'])->pluck('status')->all())->toBe(['pending_review', 'pending_review'])
        ->and(QuranAyahData::count())->toBe(2 * 6236);

    $words = QuranAyahData::whereHas('source', fn ($q) => $q->where('type', 'word_by_word'))->where('surah_id', 1)->where('ayah_number', 1)->value('data');
    expect($words[0])->toBe(['بِسمِ', "bis'mi", 'In (the) name']);
});

it('refuses files whose checksum does not match', function () {
    $dir = sys_get_temp_dir().'/qurba-wt-'.uniqid();
    mkdir($dir);
    copy(resource_path('content/2026-10-words-tajweed/manifest.json'), "$dir/manifest.json");
    file_put_contents("$dir/quran-words-en.json", '{}');
    file_put_contents("$dir/quran-tajweed.json", '{}');
    $this->artisan('qurba:import-words-tajweed', ['--folder' => $dir])->assertExitCode(1);
    expect(QuranAyahData::count())->toBe(0);
});

it('serves the data only after approval in production', function () {
    $this->seed(QurbaSeeder::class);
    $this->artisan('qurba:import-words-tajweed')->assertExitCode(0);
    app()->detectEnvironment(fn () => 'production');

    $this->getJson('/api/v1/quran/extras/tajweed/1')->assertNotFound();
    ContentSource::where('type', 'tajweed')->update(['status' => 'approved']);
    $this->getJson('/api/v1/quran/extras/tajweed/1')->assertOk()
        ->assertJsonPath('data.1.text', json_decode(file_get_contents(resource_path('content/2026-10-words-tajweed/quran-tajweed.json')), true)['1:1']['text'])
        ->assertJsonCount(7, 'data');
    $this->getJson('/api/v1/quran/extras/words/1')->assertNotFound();
});
