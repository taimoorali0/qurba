<?php
// ===== QURBA: AI voice for adhkar — generated once, hidden until a reviewer approves it =====
use App\Filament\Resources\AdhkarResource\Pages\ManageAdhkar;
use App\Models\Adhkar;
use App\Models\AdhkarCategory;
use App\Models\User;
use Database\Seeders\QurbaSeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;

function voiceAdmin(): User
{
    $u = User::factory()->create();
    $u->forceFill(['role' => 'super_admin', 'two_factor_confirmed_at' => now()])->save();
    return $u;
}

beforeEach(function () {
    (new QurbaSeeder())->run();
    Storage::fake('audio');
    config(['services.arabic_voice.provider' => 'azure', 'services.azure_tts' => ['key' => 'test-key', 'region' => 'uaenorth', 'voice' => 'ar-SA-HamedNeural']]);
});

it('generates an AI voice that plays only after a reviewer approves it', function () {
    Http::fake(['uaenorth.tts.speech.microsoft.com/*' => Http::response('MP3DATA', 200)]);
    $cat = AdhkarCategory::where('slug', 'kalimas')->first();
    $item = Adhkar::where('adhkar_category_id', $cat->id)->orderBy('sort')->first();
    Adhkar::$approving = true; $item->update(['status' => 'approved']); Adhkar::$approving = false;

    $this->actingAs(voiceAdmin())->withSession(['admin_2fa_passed' => true]);
    Livewire::test(ManageAdhkar::class)->callTableAction('aiVoice', $item)->assertHasNoTableActionErrors();

    $item->refresh();
    expect($item->audio_ai)->toBeTrue()->and($item->audio_approved)->toBeFalse();
    Storage::disk('audio')->assertExists($item->audio_url);
    Http::assertSent(fn ($r) => str_contains($r->body(), 'ar-SA-HamedNeural') && str_contains($r->body(), 'لَا إِلٰهَ') && $r->hasHeader('Ocp-Apim-Subscription-Key', 'test-key'));

    app()->detectEnvironment(fn () => 'production');
    $this->get('/zikr/adhkar/kalimas')->assertInertia(fn (Assert $p) => $p->where('items.0.audio', null)->where('items.0.ai', true));

    Livewire::test(ManageAdhkar::class)->callTableAction('approveVoice', $item);
    expect($item->fresh()->audio_approved)->toBeTrue();
    $this->get('/zikr/adhkar/kalimas')->assertInertia(fn (Assert $p) => $p->where('items.0.audio', asset('audio/'.$item->audio_url)));
});

it('replaces the AI flags when a real recording is uploaded', function () {
    $item = Adhkar::first();
    Adhkar::$aiVoice = true; $item->update(['audio_url' => 'duas/ai.mp3', 'audio_ai' => true, 'audio_approved' => false]); Adhkar::$aiVoice = false;
    $item->update(['audio_url' => 'duas/qari.mp3']);
    expect($item->fresh()->audio_ai)->toBeFalse()->and($item->fresh()->audio_approved)->toBeTrue();
});

it('reports a failed text-to-speech call without changing the dua', function () {
    Http::fake(['*' => Http::response('', 401)]);
    $item = Adhkar::first();
    $this->actingAs(voiceAdmin())->withSession(['admin_2fa_passed' => true]);
    Livewire::test(ManageAdhkar::class)->callTableAction('aiVoice', $item)->assertNotified('Could not generate the voice');
    expect($item->fresh()->audio_url)->toBeNull();
});

it('generates the voice with Google Gemini and saves playable audio', function () {
    config(['services.arabic_voice.provider' => 'gemini', 'services.gemini' => ['key' => 'gem-key', 'voice' => 'Charon', 'tts_model' => 'gemini-2.5-flash-preview-tts']]);
    $pcm = str_repeat(pack('v', 1000), 2400); // 0.1 s of 24 kHz 16-bit audio
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['candidates' => [['content' => ['parts' => [
        ['inlineData' => ['mimeType' => 'audio/L16;codec=pcm;rate=24000', 'data' => base64_encode($pcm)]],
    ]]]]])]);
    $item = Adhkar::first();
    $this->actingAs(voiceAdmin())->withSession(['admin_2fa_passed' => true]);
    Livewire::test(ManageAdhkar::class)->callTableAction('aiVoice', $item)->assertHasNoTableActionErrors();

    $item->refresh();
    expect($item->audio_ai)->toBeTrue()->and($item->audio_approved)->toBeFalse()
        ->and($item->audio_url)->toMatch('/^duas\/ai-\d+-\d+\.(mp3|wav)$/');
    $bytes = Storage::disk('audio')->get($item->audio_url);
    expect(str_starts_with($bytes, 'RIFF') || str_starts_with($bytes, 'ID3') || ord($bytes[0]) === 0xFF)->toBeTrue();
    Http::assertSent(fn ($r) => $r->hasHeader('x-goog-api-key', 'gem-key') && str_contains($r->url(), 'gemini-2.5-flash-preview-tts')
        && $r['generationConfig']['speechConfig']['voiceConfig']['prebuiltVoiceConfig']['voiceName'] === 'Charon');
});
