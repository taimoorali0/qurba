<?php
// ===== QURBA: Arabic search normalisation =====
use App\Support\Quran\ArabicNormalizer;

it('removes diacritics and unifies letter forms for search', function () {
    expect(ArabicNormalizer::forSearch('ٱلْحَمْدُ لِلَّهِ رَبِّ ٱلْعَـٰلَمِينَ'))->toBe('الحمد لله رب العلمين');
    expect(ArabicNormalizer::forSearch('إِيَّاكَ'))->toBe('اياك');
    expect(ArabicNormalizer::forSearch('رَحْمَةً'))->toBe('رحمه');
});

it('hashes deterministically', function () {
    expect(ArabicNormalizer::hash('abc'))->toBe(hash('sha256', 'abc'));
});
