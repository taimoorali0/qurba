<?php
// ===== QURBA: integrity check — run before every release and on a schedule =====
namespace App\Console\Commands;

use App\Models\QuranAyah;
use App\Support\Quran\ArabicNormalizer;
use App\Support\Quran\QuranStructure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifyQuran extends Command
{
    protected $signature = 'qurba:verify-quran';
    protected $description = 'Check Quran text structure and per-ayah hashes against stored values';

    public function handle(): int
    {
        $problems = [];

        $total = QuranAyah::count();
        if ($total !== QuranStructure::AYAH_COUNT) {
            $problems[] = "Expected 6236 ayahs, found {$total}";
        }

        $counts = QuranAyah::select('surah_id', DB::raw('count(*) as c'))->groupBy('surah_id')->pluck('c', 'surah_id');
        foreach (QuranStructure::AYAHS as $s => $expected) {
            if ((int) ($counts[$s] ?? 0) !== $expected) {
                $problems[] = "Surah {$s}: expected {$expected}, found " . ($counts[$s] ?? 0);
            }
        }

        QuranAyah::select('id', 'ayah_key', 'text_uthmani', 'content_hash')->chunk(1000, function ($ayahs) use (&$problems) {
            foreach ($ayahs as $a) {
                if (! hash_equals($a->content_hash, ArabicNormalizer::hash($a->text_uthmani))) {
                    $problems[] = "Hash mismatch at {$a->ayah_key}";
                }
            }
        });

        if ($problems) {
            $this->error('INTEGRITY CHECK FAILED');
            foreach (array_slice($problems, 0, 30) as $p) {
                $this->line("  - {$p}");
            }
            return self::FAILURE;
        }

        $this->info('Quran text OK: 114 surahs, 6236 ayahs, all hashes match.');
        return self::SUCCESS;
    }
}
