<?php
// ===== QURBA: tafsir import + visibility =====
use App\Models\ContentSource;
use App\Models\QuranSurah;
use App\Models\QuranTafsir;
use App\Support\Quran\QuranStructure;

function tafsirFixture(): string
{
    foreach (QuranStructure::AYAHS as $i => $c) {
        QuranSurah::firstOrCreate(['id' => $i], ['name_simple' => "Surah {$i}", 'ayah_count' => $c]);
    }
    $dir = sys_get_temp_dir() . '/qurba-tafsir-' . uniqid() . '/test-tafsir';
    mkdir($dir, 0777, true);
    foreach (QuranStructure::AYAHS as $s => $count) {
        // Ayah 2 left empty, like editions that explain several ayahs together
        $ayahs = array_map(fn ($n) => ['surah' => $s, 'ayah' => $n, 'text' => $n === 2 ? '' : "Commentary {$s}:{$n}"], range(1, $count));
        file_put_contents("{$dir}/{$s}.json", json_encode(['ayahs' => $ayahs]));
    }
    return dirname($dir);
}

it('imports a tafsir edition as pending review and skips empty ayahs', function () {
    $base = tafsirFixture();
    $this->artisan('qurba:import-tafsir', ['slug' => 'test-tafsir', '--base' => $base, '--name' => 'Test Tafsir', '--lang' => 'en'])
        ->assertSuccessful();

    $src = ContentSource::where('type', 'tafsir')->sole();
    expect($src->status)->toBe('pending_review')
        ->and(QuranTafsir::count())->toBe(QuranStructure::AYAH_COUNT - 114)
        ->and(QuranTafsir::where('surah_id', 2)->where('ayah_number', 255)->value('text'))->toBe('Commentary 2:255');
});

it('rejects a malformed edition without writing anything', function () {
    $base = tafsirFixture();
    file_put_contents("{$base}/test-tafsir/5.json", json_encode(['ayahs' => [['surah' => 5, 'ayah' => 999, 'text' => 'x']]]));
    $this->artisan('qurba:import-tafsir', ['slug' => 'test-tafsir', '--base' => $base])->assertFailed();
    expect(QuranTafsir::count())->toBe(0);
});

it('only serves approved tafsir outside local', function () {
    $base = tafsirFixture();
    $this->artisan('qurba:import-tafsir', ['slug' => 'test-tafsir', '--base' => $base])->assertSuccessful();
    $src = ContentSource::where('type', 'tafsir')->sole();

    $this->getJson("/api/v1/quran/tafsir/{$src->id}/1")->assertNotFound();

    $src->update(['status' => 'approved']);
    $this->getJson("/api/v1/quran/tafsir/{$src->id}/1")->assertOk()
        ->assertJsonPath('data.0.n', 1)->assertJsonPath('data.0.text', 'Commentary 1:1')
        ->assertJsonCount(6, 'data');
});
