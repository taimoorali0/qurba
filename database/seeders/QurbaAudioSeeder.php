<?php
// ===== QURBA: development audio provider (EveryAyah). Status stays pending_review. =====
// Production only plays a reciter after its content source is set to "approved"
// once written streaming/offline permission has been obtained.
namespace Database\Seeders;

use App\Models\ContentSource;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QurbaAudioSeeder extends Seeder
{
    public function run(): void
    {
        $source = ContentSource::firstOrCreate(
            ['type' => 'audio', 'name' => 'EveryAyah (development)'],
            ['source_url' => 'https://everyayah.com', 'status' => 'pending_review',
             'review_notes' => 'Confirm streaming + offline permission before approving.']
        );

        $base = 'https://everyayah.com/data/';
        $reciters = [
            'alafasy' => 'Alafasy_128kbps',
            'abdul-basit' => 'Abdul_Basit_Murattal_192kbps',
            'maher-al-muaiqly' => 'MaherAlMuaiqly128kbps',
        ];
        foreach ($reciters as $slug => $folder) {
            DB::table('quran_reciters')->where('slug', $slug)->update([
                'ayah_url_pattern' => $base . $folder . '/{sss}{aaa}.mp3',
                'content_source_id' => $source->id,
                'active' => true,
                'updated_at' => now(),
            ]);
        }
    }
}
