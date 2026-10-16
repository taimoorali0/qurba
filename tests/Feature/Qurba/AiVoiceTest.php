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
    config(['services.azure_tts' => ['key' => 'test-key', 'region' => 'uaenorth', 'voice' => 'ar-SA-HamedNeural']]);
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
