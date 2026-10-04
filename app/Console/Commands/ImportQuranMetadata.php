<?php
// ===== QURBA: import Tanzil quran-data.xml (surah names, Makki/Madani, juz, pages) =====
namespace App\Console\Commands;

use App\Models\ContentAuditLog;
use App\Models\ContentSource;
use App\Support\Quran\QuranStructure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportQuranMetadata extends Command
{
    protected $signature = 'qurba:import-metadata {file : Path to Tanzil quran-data.xml}';
    protected $description = 'Import verified surah names, revelation place, juz and page numbers';

    public function handle(): int
    {
        $file = $this->argument('file');
        if (! is_file($file)) {
            $this->error("File not found: {$file}");
            return self::FAILURE;
        }
        $xml = @simplexml_load_file($file);
        if (! $xml || ! isset($xml->suras)) {
            $this->error('Not a valid Tanzil quran-data.xml file.');
            return self::FAILURE;
        }

        // 1) Validate surah list against the canonical structure
        $suras = [];
        foreach ($xml->suras->sura as $s) {
            $i = (int) $s['index'];
            $suras[$i] = [
                'ayas' => (int) $s['ayas'],
                'name' => trim((string) $s['name']),
                'ename' => trim((string) $s['ename']),
                'type' => strtolower((string) $s['type']),
            ];
        }
        if (count($suras) !== 114) {
            $this->error('Expected 114 surahs, found ' . count($suras));
            return self::FAILURE;
        }
        foreach (QuranStructure::AYAHS as $i => $count) {
            if (($suras[$i]['ayas'] ?? 0) !== $count) {
                $this->error("Surah {$i}: ayah count mismatch. Nothing imported.");
                return self::FAILURE;
            }
        }

        // 2) Build juz / page start points
        $starts = fn ($nodes) => collect($nodes)->map(fn ($n) => [
            'no' => (int) $n['index'], 'sura' => (int) $n['sura'], 'aya' => (int) $n['aya'],
        ])->sortBy('no')->values();
        $juzs = $starts($xml->juzs->juz);
        $pages = $starts($xml->pages->page);
        if ($juzs->count() !== 30) {
            $this->error('Expected 30 juz markers.');
            return self::FAILURE;
        }

        $pos = fn ($s, $a) => $s * 1000 + $a;
        $lookup = function ($list, $s, $a) use ($pos) {
            $current = 1;
            foreach ($list as $m) {
                if ($pos($m['sura'], $m['aya']) <= $pos($s, $a)) {
                    $current = $m['no'];
                } else {
                    break;
                }
            }
            return $current;
        };

        DB::transaction(function () use ($suras, $juzs, $pages, $lookup, $file) {
            foreach ($suras as $i => $s) {
                DB::table('quran_surahs')->where('id', $i)->update([
                    'name_arabic' => $s['name'],
                    'name_english' => $s['ename'],
                    'revelation_place' => $s['type'] === 'medinan' ? 'madinah' : 'makkah',
                    'updated_at' => now(),
                ]);
            }

            // Only juz/page columns are touched; Quran text is never modified here
            $bar = $this->output->createProgressBar(QuranStructure::AYAH_COUNT);
            foreach (QuranStructure::AYAHS as $s => $count) {
                for ($a = 1; $a <= $count; $a++) {
                    DB::table('quran_ayahs')->where('ayah_key', "{$s}:{$a}")->update([
                        'juz' => $lookup($juzs, $s, $a),
                        'page' => $lookup($pages, $s, $a),
                    ]);
                    $bar->advance();
                }
            }
            $bar->finish();
            $this->newLine();

            $source = ContentSource::firstOrCreate(
                ['type' => 'other', 'name' => 'Tanzil Quran Metadata'],
                ['source_url' => 'https://tanzil.net', 'status' => 'pending_review']
            );
            ContentAuditLog::record($source, 'imported', null, ['file_sha256' => hash_file('sha256', $file)]);
        });

        $this->info('Metadata imported: 114 surah names, revelation place, 30 juz, ' . $pages->count() . ' pages.');
        return self::SUCCESS;
    }
}
