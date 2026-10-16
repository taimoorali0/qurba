<?php
// ===== QURBA: generate missing word clips for some duas, slowly, so free API limits are respected =====
namespace App\Jobs;

use App\Models\Adhkar;
use App\Models\AdhkarWordAudio;
use App\Models\ContentAuditLog;
use App\Support\ArabicVoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateWordVoices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $timeout = 3000;
    public int $tries = 1;

    /** @param int[] $adhkarIds */
    public function __construct(public array $adhkarIds, public int $pauseSeconds = 4) {}

    /** @return array{made: int, missing: int, stopped: ?string} */
    public function handle(): array
    {
        $made = 0;
        $calls = 0;
        $missing = fn () => Adhkar::whereIn('id', $this->adhkarIds)->pluck('text_arabic')
            ->flatMap(fn ($t) => AdhkarWordAudio::words($t))->unique()
            ->reject(fn ($w) => AdhkarWordAudio::where('word_hash', AdhkarWordAudio::hashOf($w))->exists())->values();
        $save = function (string $word, string $audio, string $ext) use (&$made) {
            $hash = AdhkarWordAudio::hashOf($word);
            $path = "words/{$hash}.{$ext}";
            Storage::disk('audio')->put($path, $audio);
            AdhkarWordAudio::updateOrCreate(['word_hash' => $hash], ['word' => mb_substr($word, 0, 120), 'audio_url' => $path, 'approved' => false]);
            $made++;
        };
        $pause = function () use (&$calls) { if ($calls++ && $this->pauseSeconds) sleep($this->pauseSeconds); };

        try {
            // 1) One request per dua: read with pauses, cut at the silences (saves the free quota)
            foreach (Adhkar::whereIn('id', $this->adhkarIds)->get() as $dua) {
                $words = AdhkarWordAudio::words($dua->text_arabic);
                $needed = collect($words)->reject(fn ($w) => AdhkarWordAudio::where('word_hash', AdhkarWordAudio::hashOf($w))->exists());
                if ($needed->isEmpty() || count($words) > 40) continue;
                $pause();
                $pcm = ArabicVoice::synthesizeWithPauses($words);
                $clips = $pcm ? ArabicVoice::splitPcm($pcm[0], $pcm[1], count($words)) : null;
                if (! $clips) continue; // pauses did not match the words: done one by one below
                foreach ($words as $i => $w) {
                    if (! AdhkarWordAudio::where('word_hash', AdhkarWordAudio::hashOf($w))->exists()) $save($w, $clips[$i][0], $clips[$i][1]);
                }
            }
            // 2) Anything still missing: one request per word
            foreach ($missing() as $word) {
                $pause();
                [$audio, $ext] = ArabicVoice::synthesize($word);
                $save($word, $audio, $ext);
            }
        } catch (\Throwable $e) {
            // Usually the free limit; the next run continues from here
            Log::warning('Word voice stopped: '.$e->getMessage());
            return ['made' => $made, 'missing' => $missing()->count(), 'stopped' => $e->getMessage()];
        }

        foreach (Adhkar::whereIn('id', $this->adhkarIds)->get() as $a) {
            ContentAuditLog::record($a, 'word_voices_generated', null, ['new_words' => $made, 'voice' => ArabicVoice::voiceName()]);
        }
        return ['made' => $made, 'missing' => 0, 'stopped' => null];
    }
}
