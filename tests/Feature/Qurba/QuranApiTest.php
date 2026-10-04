<?php
// ===== QURBA: public Quran API + review gate =====
use function Tests\Feature\Qurba\seedQuranSample;

require_once __DIR__ . '/Helpers.php';

it('lists 114 surahs', function () {
    seedQuranSample();
    $this->getJson('/api/v1/quran/surahs')->assertOk()->assertJsonCount(114, 'data');
});

it('serves an approved surah', function () {
    seedQuranSample('approved');
    $this->getJson('/api/v1/quran/surahs/1')->assertOk()->assertJsonPath('data.ayahs.0.key', '1:1');
});

it('hides unreviewed Quran text outside local development', function () {
    seedQuranSample('pending_review');
    $this->getJson('/api/v1/quran/surahs/1')->assertStatus(503);
});

it('returns 404 for surah 115', function () {
    seedQuranSample();
    $this->getJson('/api/v1/quran/surahs/115')->assertNotFound();
});

it('finds Arabic without diacritics', function () {
    seedQuranSample();
    $this->getJson('/api/v1/quran/search?q=' . urlencode('الحمد'))->assertOk()->assertJsonPath('data.0.ayah_key', '1:2');
});
