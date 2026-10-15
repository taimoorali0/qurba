<?php
// ===== QURBA: duas by situation, adhkar search, zakat page =====
use App\Models\Adhkar;
use App\Models\AdhkarCategory;
use Database\Seeders\QurbaSeeder;
use Inertia\Testing\AssertableInertia as Assert;

it('adds the situational dua categories', function () {
    expect(AdhkarCategory::whereIn('slug', ['illness', 'distress', 'knowledge', 'weather'])->count())->toBe(4);
});

it('searches approved adhkar only', function () {
    (new QurbaSeeder())->run();
    $cat = AdhkarCategory::where('slug', 'travel')->value('id');
    Adhkar::$approving = true;
    $ok = Adhkar::create(['adhkar_category_id' => $cat, 'text_arabic' => 'سبحان الذي سخر لنا هذا', 'reference' => 'Muslim 1342', 'status' => 'approved']);
    Adhkar::$approving = false;
    Adhkar::create(['adhkar_category_id' => $cat, 'text_arabic' => 'سبحان مسودة', 'reference' => 'Draft', 'status' => 'draft']);
    app()->detectEnvironment(fn () => 'production');

    $this->get('/zikr/adhkar?q=سبحان')->assertOk()->assertInertia(fn (Assert $p) => $p
        ->component('AdhkarIndex')->where('q', 'سبحان')->has('results', 1)->where('results.0.id', $ok->id));
    $this->get('/zikr/adhkar')->assertOk()->assertInertia(fn (Assert $p) => $p->where('results', null));
});

it('serves the zakat calculator', function () {
    $this->get('/zakat')->assertOk()->assertSee('Zakat Calculator');
});

it('serves the hifz and playlists pages', function () {
    $this->get('/hifz')->assertOk()->assertSee('Hifz Revision');
    $this->get('/playlists')->assertOk()->assertSee('Quran Audio Playlists');
});

it('serves the kids corner and the more page', function () {
    $this->get('/kids')->assertOk()->assertSee('Kids Corner');
    $this->get('/more')->assertOk();
});

it('lists only valid qaida recordings in the audio manifest', function () {
    \Illuminate\Support\Facades\Storage::fake('audio');
    foreach (['qaida/2.mp3', 'qaida/2-a.mp3', 'qaida/28-u.mp3', 'qaida/29.mp3', 'qaida/invalid-x.mp3', 'qaida/2-z.mp3'] as $f) {
        \Illuminate\Support\Facades\Storage::disk('audio')->put($f, 'x');
    }
    $this->getJson('/api/v1/audio-manifest')->assertOk()->assertJsonPath('qaida', ['2', '2-a', '28-u']);
});

it('sends the category recording and dua start times for read-along', function () {
    (new QurbaSeeder())->run();
    $cat = AdhkarCategory::where('slug', 'sleep-wake')->first();
    $cat->update(['audio_url' => 'adhkar-categories/waking.mp3', 'audio_credit' => 'Test reciter']);
    Adhkar::$approving = true;
    Adhkar::create(['adhkar_category_id' => $cat->id, 'text_arabic' => 'نص', 'reference' => 'Ref', 'status' => 'approved', 'audio_start' => 12.5]);
    Adhkar::$approving = false;

    $this->get('/zikr/adhkar/sleep-wake')->assertOk()->assertInertia(fn (Assert $p) => $p
        ->where('category.audio', asset('audio/adhkar-categories/waking.mp3'))
        ->where('category.credit', 'Test reciter')
        ->where('items.0.start', 12.5));
});
