<?php
// ===== QURBA test helpers =====
namespace Tests\Feature\Qurba;

use App\Models\ContentSource;
use App\Models\QuranAyah;
use App\Support\Quran\ArabicNormalizer;
use Database\Seeders\QurbaSeeder;

function seedQuranSample(string $status = 'approved'): ContentSource
{
    (new QurbaSeeder())->run();
    $src = ContentSource::create(['type' => 'quran_text', 'name' => 'Test text', 'status' => $status]);
    QuranAyah::$importMode = true;
    foreach ([[1, 1, 'بِسْمِ ٱللَّهِ ٱلرَّحْمَـٰنِ ٱلرَّحِيمِ'], [1, 2, 'ٱلْحَمْدُ لِلَّهِ رَبِّ ٱلْعَـٰلَمِينَ']] as [$s, $a, $t]) {
        QuranAyah::create(['surah_id' => $s, 'ayah_number' => $a, 'ayah_key' => "$s:$a", 'text_uthmani' => $t,
            'text_search' => ArabicNormalizer::forSearch($t), 'content_hash' => ArabicNormalizer::hash($t), 'content_source_id' => $src->id]);
    }
    QuranAyah::$importMode = false;
    return $src;
}
