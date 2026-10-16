<?php
// ===== QURBA: word-by-word AI voice for adhkar =====
use App\Jobs\GenerateWordVoices;
use App\Models\Adhkar;
use App\Models\AdhkarCategory;
use App\Models\AdhkarWordAudio;
use Database\Seeders\QurbaSeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

function fakeGemini(int $failAfter = PHP_INT_MAX): void
{
    $n = 0;
    Http::fake(function () use (&$n, $failAfter) {
        if (++$n > $failAfter) return Http::response(['error' => ['message' => 'Quota exceeded']], 429);
        return Http::response(['candidates' => [['content' => ['parts' => [['inlineData' => ['mimeType' => 'audio/L16;codec=pcm;rate=24000', 'data' => base64_encode(str_repeat("\0\0", 2400))]]]]]]]);
    });
}

beforeEach(function () {
    (new QurbaSeeder())->run();
    Storage::fake('audio');
    config(['services.arabic_voice.provider' => 'gemini', 'services.gemini' => ['key' => 'k', 'voice' => 'Charon', 'tts_model' => 'gemini-2.5-flash-preview-tts']]);
});

it('makes one clip per unique word and reuses words shared between kalimas', function () {
    fakeGemini();
    $ids = Adhkar::whereHas('category', fn ($q) => $q->where('slug', 'kalimas'))->orderBy('sort')->take(2)->pluck('id')->all();
    $r = (new GenerateWordVoices($ids, 0))->handle();

    $unique = Adhkar::whereIn('id', $ids)->pluck('text_arabic')->flatMap(fn ($t) => AdhkarWordAudio::words($t))->unique()->count();
    expect($r['made'])->toBe($unique)->and(AdhkarWordAudio::count())->toBe($unique)
        ->and(AdhkarWordAudio::where('approved', true)->count())->toBe(0);
    // the fake audio is silent, so each dua's one-request attempt (2) falls back to one request per word
    Http::assertSentCount($unique + 2);

    (new GenerateWordVoices($ids, 0))->handle(); // nothing new to make
    Http::assertSentCount($unique + 2);
});

it('stops at the API limit and continues on the next run', function () {
    fakeGemini(failAfter: 3);
    $id = Adhkar::whereHas('category', fn ($q) => $q->where('slug', 'kalimas'))->orderBy('sort')->skip(1)->value('id');
    $r = (new GenerateWordVoices([$id], 0))->handle();
    expect($r['made'])->toBe(2)->and($r['stopped'])->toContain('Quota exceeded')->and(AdhkarWordAudio::count())->toBe(2);

    Http::swap(new \Illuminate\Http\Client\Factory()); // quota is back
    fakeGemini();
    $r = (new GenerateWordVoices([$id], 0))->handle();
    expect($r['stopped'])->toBeNull()->and(AdhkarWordAudio::count())->toBe(count(array_unique(AdhkarWordAudio::words(Adhkar::find($id)->text_arabic))));
});

it('shows word clips to users only after approval', function () {
    fakeGemini();
    $cat = AdhkarCategory::where('slug', 'kalimas')->first();
    $first = Adhkar::where('adhkar_category_id', $cat->id)->orderBy('sort')->first();
    Adhkar::$approving = true; $first->update(['status' => 'approved']); Adhkar::$approving = false;
    (new GenerateWordVoices([$first->id], 0))->handle();

    app()->detectEnvironment(fn () => 'production');
    $this->get('/zikr/adhkar/kalimas')->assertInertia(fn (Assert $p) => $p->where('items.0.words.0.w', 'لَا')->where('items.0.words.0.a', null));

    AdhkarWordAudio::query()->update(['approved' => true]);
    $this->get('/zikr/adhkar/kalimas')->assertInertia(fn (Assert $p) => $p->where('items.0.words.0.a', fn ($a) => str_contains($a, '/audio/words/')));
});

it('cuts one recording into word clips at the pauses', function () {
    $rate = 24000;
    $tone = fn (float $sec) => implode('', array_map(fn ($i) => pack('v', (int) (8000 * sin($i / 4)) & 0xFFFF), range(1, (int) ($sec * $rate))));
    $silence = fn (float $sec) => str_repeat("\0\0", (int) ($sec * $rate));
    $pcm = $silence(0.3).$tone(0.4).$silence(0.7).$tone(0.5).$silence(0.7).$tone(0.3).$silence(0.4);

    $clips = \App\Support\ArabicVoice::splitPcm($pcm, $rate, 3);
    expect($clips)->toHaveCount(3)
        ->and(\App\Support\ArabicVoice::splitPcm($pcm, $rate, 4))->toBeNull(); // count must match exactly
})->skip(fn () => ! (new \Symfony\Component\Process\ExecutableFinder())->find('ffmpeg'), 'needs ffmpeg');

it('runs the configured categories when no category is given', function () {
    fakeGemini();
    $this->artisan('qurba:word-voices', ['--pause' => 0])->assertExitCode(0);
    $words = Adhkar::whereHas('category', fn ($q) => $q->where('slug', 'kalimas'))->pluck('text_arabic')
        ->flatMap(fn ($t) => AdhkarWordAudio::words($t))->unique()->count();
    expect(AdhkarWordAudio::count())->toBe($words);
});
