<?php
// ===== QURBA: Arabic search normalizer =====
namespace App\Support\Quran;

class ArabicNormalizer
{
    /** Build the search copy of an ayah. The original text is never changed. */
    public static function forSearch(string $text): string
    {
        // Remove harakat, Quranic annotation marks, superscript alef, tatweel
        $text = preg_replace('/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}\x{0640}]/u', '', $text);
        // Unify letter forms
        $text = strtr($text, [
            'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا',
            'ى' => 'ي', 'ئ' => 'ي', 'ؤ' => 'و', 'ة' => 'ه',
        ]);
        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    public static function hash(string $text): string
    {
        return hash('sha256', $text);
    }
}
