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
        ->get('/admin/audio-files')->assertOk()->assertSee('Audio files');
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
