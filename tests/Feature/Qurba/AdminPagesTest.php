<?php
// ===== QURBA: every admin list page renders with real rows (catches broken column closures) =====
use App\Models\ContentAuditLog;
use App\Models\ContentSource;
use App\Models\Course;
use App\Models\LearnInterest;
use App\Models\User;
use function Tests\Feature\Qurba\seedQuranSample;

require_once __DIR__ . '/Helpers.php';

it('renders every admin list page with data', function () {
    $src = seedQuranSample();
    ContentSource::create(['type' => 'tafsir', 'name' => 'Test tafsir', 'edition' => 'test-tafsir', 'status' => 'pending_review']);
    ContentAuditLog::record($src, 'imported', null, ['ayahs' => 2]);
    $course = Course::create(['slug' => 'test', 'title' => ['en' => 'Test course'], 'active' => true]);
    LearnInterest::create(['course_id' => $course->id, 'name' => 'Parent', 'email' => 'p@example.com', 'contact_consent' => true]);

    $admin = User::factory()->create();
    $admin->forceFill(['role' => 'super_admin', 'two_factor_confirmed_at' => now()])->save();
    $this->actingAs($admin)->withSession(['admin_2fa_passed' => true]);

    foreach (['adhkars', 'audio-files', 'content-audit-logs', 'content-sources', 'courses', 'learn-interests',
        'quran-ayahs', 'quran-reciters', 'quran-surahs', 'users'] as $page) {
        $this->get("/admin/{$page}")->assertOk();
    }
});

it('opens the edit forms and sorts the ayah table without errors', function () {
    seedQuranSample();
    $admin = User::factory()->create();
    $admin->forceFill(['role' => 'super_admin', 'two_factor_confirmed_at' => now()])->save();
    $this->actingAs($admin);

    $cat = \App\Models\AdhkarCategory::first();
    $dhikr = \App\Models\Adhkar::create(['adhkar_category_id' => $cat->id, 'text_arabic' => 'سُبْحَانَ اللَّهِ', 'reference' => 'Muslim', 'repeat_count' => 33]);
    \Livewire\Livewire::test(\App\Filament\Resources\AdhkarResource\Pages\ManageAdhkar::class)
        ->mountTableAction('edit', $dhikr)->assertHasNoErrors()->assertSee('Muslim');

    $reciter = \App\Models\QuranReciter::first();
    \Livewire\Livewire::test(\App\Filament\Resources\QuranReciterResource\Pages\ManageQuranReciters::class)
        ->mountTableAction('edit', $reciter)->assertHasNoErrors();

    \Livewire\Livewire::test(\App\Filament\Resources\QuranAyahResource\Pages\ManageQuranAyahs::class)
        ->sortTable('ayah_key', 'desc')->assertHasNoErrors()
        ->searchTable('الحمد')->assertHasNoErrors();
});
