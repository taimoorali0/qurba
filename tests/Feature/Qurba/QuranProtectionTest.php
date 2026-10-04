<?php
// ===== QURBA: Quran text can only change through the verified importer =====
use App\Models\QuranAyah;
use App\Support\Quran\QuranStructure;
use function Tests\Feature\Qurba\seedQuranSample;

require_once __DIR__ . '/Helpers.php';

it('refuses to edit Quran text outside the importer', function () {
    seedQuranSample();
    $ayah = QuranAyah::where('ayah_key', '1:2')->first();
    $ayah->text_uthmani = 'changed';
    expect(fn () => $ayah->save())->toThrow(RuntimeException::class);
    expect(QuranAyah::where('ayah_key', '1:2')->value('text_uthmani'))->not->toBe('changed');
});

it('refuses to delete an ayah', function () {
    seedQuranSample();
    expect(fn () => QuranAyah::first()->delete())->toThrow(RuntimeException::class);
});

it('reports an incomplete Quran as failed', function () {
    seedQuranSample();
    $this->artisan('qurba:verify-quran')->assertFailed();
});

it('rejects a text file with a missing ayah', function () {
    $f = tempnam(sys_get_temp_dir(), 'q');
    $lines = [];
    foreach (QuranStructure::AYAHS as $s => $n) for ($a = 1; $a <= $n; $a++) if (! ($s === 2 && $a === 255)) $lines[] = "$s|$a|x";
    file_put_contents($f, implode("\n", $lines));
    [$rows, $errors] = QuranStructure::readPipeFile($f);
    expect($errors)->toContain('Missing 2:255');
});

it('accepts a complete text file', function () {
    $f = tempnam(sys_get_temp_dir(), 'q');
    $lines = [];
    foreach (QuranStructure::AYAHS as $s => $n) for ($a = 1; $a <= $n; $a++) $lines[] = "$s|$a|x";
    file_put_contents($f, "\u{FEFF}" . implode("\n", $lines) . "\n# licence comment\n");
    [$rows, $errors] = QuranStructure::readPipeFile($f);
    expect($errors)->toBe([])->and(count($rows))->toBe(6236);
});
