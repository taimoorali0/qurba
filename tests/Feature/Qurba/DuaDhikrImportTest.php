<?php
// ===== QURBA: duas & dhikr dataset import =====
use App\Models\Adhkar;
use Database\Seeders\QurbaSeeder;

function duaFixture(): string
{
    $base = sys_get_temp_dir() . '/qurba-dua-' . uniqid();
    $item = fn ($ar, $src, $notes = null) => ['title' => 't', 'arabic' => $ar, 'latin' => 'latin', 'translation' => 'meaning', 'notes' => $notes, 'source' => $src];
    $data = [
        'morning-dhikr' => [$item('أَصْبَحْنَا', 'HR. Muslim', 'Recite 3x')],
        'evening-dhikr' => [$item('أَمْسَيْنَا', 'HR. Muslim')],
        'dhikr-after-salah' => [$item('أَسْتَغْفِرُ اللَّهَ', 'HR. Muslim', 'Read 3x'), $item('سُبْحَانَ اللهِ', null, 'Read 33x')],
        'daily-dua' => [$item('بِاسْمِكَ اللَّهُمَّ', 'HR. al-Bukhari')],
        'selected-dua' => [$item('بِاسْمِكَ اللَّهُمَّ', 'HR. al-Bukhari')],
    ];
    foreach ($data as $folder => $items) {
        mkdir("{$base}/{$folder}", 0777, true);
        file_put_contents("{$base}/{$folder}/en.json", json_encode($items));
    }
    return $base;
}

it('imports duas as drafts, skips unsourced items and never duplicates', function () {
    (new QurbaSeeder())->run();
    $base = duaFixture();

    $this->artisan('qurba:import-dua-dhikr', ['--base' => $base])->assertSuccessful();
    expect(Adhkar::count())->toBe(4)
        ->and(Adhkar::where('status', '!=', 'draft')->count())->toBe(0)
        ->and(Adhkar::where('text_arabic', 'أَصْبَحْنَا')->value('repeat_count'))->toBe(3)
        ->and(Adhkar::where('text_arabic', 'سُبْحَانَ اللهِ')->exists())->toBeFalse();

    $this->artisan('qurba:import-dua-dhikr', ['--base' => $base])->assertSuccessful();
    expect(Adhkar::count())->toBe(4);
});
