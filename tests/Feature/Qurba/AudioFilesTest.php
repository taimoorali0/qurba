<?php
// ===== QURBA: admin audio uploads =====
use App\Filament\Pages\AudioFiles;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function audioAdmin(string $role): User
{
    $u = User::factory()->create();
    $u->forceFill(['role' => $role, 'two_factor_confirmed_at' => now()])->save();
    return $u;
}

it('lets content admins open the audio page and blocks support staff', function () {
    $this->actingAs(audioAdmin('content_admin'))->withSession(['admin_2fa_passed' => true])
        ->get('/admin/audio-files')->assertOk()->assertSee('Audio library')->assertSee('99 Names of Allah');
    $this->actingAs(audioAdmin('support'))->withSession(['admin_2fa_passed' => true])
        ->get('/admin/audio-files')->assertForbidden();
});

it('stores uploads under fixed names and drops misnamed name files', function () {
    Storage::fake('audio');
    $this->actingAs(audioAdmin('super_admin'));

    Livewire::test(AudioFiles::class)
        ->set('data.adhan', [UploadedFile::fake()->create('my adhan.mp3', 100, 'audio/mpeg')])
        ->set('data.names', [
            UploadedFile::fake()->create('7.mp3', 50, 'audio/mpeg'),
            UploadedFile::fake()->create('ar-rahman.mp3', 50, 'audio/mpeg'),
        ])
        ->call('save')
        ->assertHasNoErrors();

    Storage::disk('audio')->assertExists('adhan.mp3');
    Storage::disk('audio')->assertExists('names/7.mp3');
    expect(collect(Storage::disk('audio')->files('names'))->filter(fn ($f) => str_contains($f, 'invalid'))->count())->toBe(0);
});

it('replaces and removes a single name recording from the grid', function () {
    Storage::fake('audio');
    $this->actingAs(audioAdmin('content_admin'));

    Livewire::test(AudioFiles::class)
        ->set('nameUploads.12', UploadedFile::fake()->create('anything.mp3', 40, 'audio/mpeg'))
        ->assertHasNoErrors();
    Storage::disk('audio')->assertExists('names/12.mp3');

    Livewire::test(AudioFiles::class)->call('deleteName', 12);
    Storage::disk('audio')->assertMissing('names/12.mp3');
});

it('rejects a non-audio file in the grid', function () {
    Storage::fake('audio');
    $this->actingAs(audioAdmin('content_admin'));
    Livewire::test(AudioFiles::class)
        ->set('nameUploads.5', UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'))
        ->assertHasErrors('nameUploads.5');
    Storage::disk('audio')->assertMissing('names/5.mp3');
});

it('lists installed sounds in the audio manifest', function () {
    Storage::fake('audio');
    Storage::disk('audio')->put('adhan.mp3', 'x');
    Storage::disk('audio')->put('names/3.mp3', 'x');
    Storage::disk('audio')->put('names/notes.txt', 'x');
    $this->getJson('/api/v1/audio-manifest')->assertOk()
        ->assertJson(['ambient' => false, 'adhan' => true, 'adhanFajr' => false, 'names' => [3]]);
});

it('moves a full recitation from a name slot to the complete-recitation slot', function () {
    Storage::fake('audio');
    Storage::disk('audio')->put('names/99.mp3', 'full');
    $this->actingAs(audioAdmin('content_admin'));
    Livewire::test(AudioFiles::class)->call('useAsFull', 99);
    Storage::disk('audio')->assertMissing('names/99.mp3');
    expect(Storage::disk('audio')->get('names-full.mp3'))->toBe('full');
    $this->getJson('/api/v1/audio-manifest')->assertJson(['namesFull' => true, 'names' => []]);
});

it('accepts a large background sound up to 20 MB', function () {
    Storage::fake('audio');
    $this->actingAs(audioAdmin('content_admin'));
    Livewire::test(AudioFiles::class)
        ->set('data.ambient', [UploadedFile::fake()->create('nasheed.mp3', 18 * 1024, 'audio/mpeg')])
        ->call('save')->assertHasNoErrors();
    Storage::disk('audio')->assertExists('ambient.mp3');
});

it('keeps separate recordings per voice and lists them in the manifest', function () {
    Storage::fake('audio');
    $this->actingAs(audioAdmin('content_admin'));

    Livewire::test(AudioFiles::class)
        ->call('setVoice', 'kids')
        ->set('nameUploads.4', UploadedFile::fake()->create('a.mp3', 40, 'audio/mpeg'))
        ->set('fullUpload', UploadedFile::fake()->create('song.mp3', 2500, 'audio/mpeg'))
        ->assertHasNoErrors();

    Storage::disk('audio')->assertExists('names-kids/4.mp3');
    Storage::disk('audio')->assertExists('names-kids-full.mp3');
    Storage::disk('audio')->assertMissing('names/4.mp3');

    $this->getJson('/api/v1/audio-manifest')->assertOk()
        ->assertJsonPath('voices.kids.names', [4])
        ->assertJsonPath('voices.kids.full', 'names-kids-full.mp3')
        ->assertJsonPath('voices.standard.names', []);
});
