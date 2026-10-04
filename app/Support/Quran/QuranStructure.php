<?php
// ===== QURBA: canonical Quran structure used to validate every import =====
namespace App\Support\Quran;

class QuranStructure
{
    public const SURAH_COUNT = 114;
    public const AYAH_COUNT = 6236;

    /** Ayah count per surah (index 1..114). */
    public const AYAHS = [1 => 7, 286, 200, 176, 120, 165, 206, 75, 129, 109, 123, 111, 43, 52, 99, 128, 111, 110, 98, 135,
        112, 78, 118, 64, 77, 227, 93, 88, 69, 60, 34, 30, 73, 54, 45, 83, 182, 88, 75, 85, 54, 53, 89, 59, 37, 35, 38, 29,
        18, 45, 60, 49, 62, 55, 78, 96, 29, 22, 24, 13, 14, 11, 11, 18, 12, 12, 30, 52, 52, 44, 28, 28, 20, 56, 40, 31, 50,
        40, 46, 42, 29, 19, 36, 25, 22, 17, 19, 26, 30, 20, 15, 21, 11, 8, 8, 19, 5, 8, 8, 11, 11, 8, 3, 9, 5, 4, 7, 3, 6,
        3, 5, 4, 5, 6];

    /**
     * Reads a "sura|aya|text" file (Tanzil text format) and validates structure.
     * Returns [rows, errors].
     */
    public static function readPipeFile(string $path): array
    {
        $rows = [];
        $errors = [];
        $fh = fopen($path, 'r');
        $line = 0;
        while (($raw = fgets($fh)) !== false) {
            $line++;
            $raw = rtrim($raw, "\r\n");
            if ($raw === '' || str_starts_with($raw, '#')) {
                continue; // blank lines and licence comments
            }
            if ($line === 1) {
                $raw = preg_replace('/^\x{FEFF}/u', '', $raw); // strip BOM
            }
            $parts = explode('|', $raw, 3);
            if (count($parts) !== 3 || ! ctype_digit($parts[0]) || ! ctype_digit($parts[1])) {
                $errors[] = "Line {$line}: not in sura|aya|text format";
                continue;
            }
            [$s, $a, $text] = [(int) $parts[0], (int) $parts[1], trim($parts[2])];
            if (! isset(self::AYAHS[$s]) || $a < 1 || $a > self::AYAHS[$s]) {
                $errors[] = "Line {$line}: {$s}:{$a} does not exist";
                continue;
            }
            if ($text === '') {
                $errors[] = "Line {$line}: {$s}:{$a} has empty text";
                continue;
            }
            if (isset($rows["{$s}:{$a}"])) {
                $errors[] = "Line {$line}: duplicate {$s}:{$a}";
                continue;
            }
            $rows["{$s}:{$a}"] = ['surah' => $s, 'ayah' => $a, 'text' => $text];
        }
        fclose($fh);

        foreach (self::AYAHS as $s => $count) {
            for ($a = 1; $a <= $count; $a++) {
                if (! isset($rows["{$s}:{$a}"])) {
                    $errors[] = "Missing {$s}:{$a}";
                }
            }
        }

        return [$rows, $errors];
    }

    public static function datasetHash(array $rows): string
    {
        ksort($rows, SORT_NATURAL);
        return hash('sha256', implode("\n", array_map(fn ($r) => "{$r['surah']}|{$r['ayah']}|{$r['text']}", $rows)));
    }
}
