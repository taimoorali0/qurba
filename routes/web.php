<?php
// ===== QURBA ROUTES — START =====
use App\Http\Controllers\QuranController;
use App\Http\Controllers\SyncController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Home'))->name('home');

// Post-login landing: send account users to the Qurba profile, not the starter dashboard
Route::get('dashboard', fn () => redirect()->route('profile'))
    ->middleware(['auth', 'verified'])->name('dashboard');

// Quran (guest access)
Route::get('/quran', [QuranController::class, 'index'])->name('quran.index');
Route::get('/quran/{surah}', [QuranController::class, 'show'])->whereNumber('surah')->name('quran.show');
Route::get('/quran/{any}', fn () => Inertia::render('Section', ['section' => 'quran']))->where('any', '.*');

// Which app sounds are installed (so the app never probes for missing files)
Route::get('api/v1/audio-manifest', function () {
    $d = \Illuminate\Support\Facades\Storage::disk('audio');
    $names = collect($d->files('names'))->map(fn ($f) => (int) basename($f, '.mp3'))->filter(fn ($n) => $n >= 1 && $n <= 99)->unique()->sort()->values();
    return response()->json(['ambient' => $d->exists('ambient.mp3'), 'adhan' => $d->exists('adhan.mp3'),
        'adhanFajr' => $d->exists('adhan-fajr.mp3'), 'namesFull' => $d->exists('names-full.mp3'), 'names' => $names,
        'voices' => \App\Support\NameVoices::manifest()])->header('Cache-Control', 'public, max-age=300');
})->middleware('throttle:60,1');

// Content version check (used by 7-day sync)
Route::get('api/v1/content-version', fn () => response()->json(['data' => \Illuminate\Support\Facades\DB::table('content_versions')
    ->select('content_type', 'version', 'dataset_hash', 'updated_at')->orderByDesc('id')->limit(50)->get()]))->middleware('throttle:30,1');

// Public JSON API v1 (read-only content)
Route::prefix('api/v1/quran')->group(function () {
    Route::get('surahs', [QuranController::class, 'apiSurahs']);
    Route::get('surahs/{surah}', [QuranController::class, 'apiSurah'])->whereNumber('surah');
    Route::get('search', [QuranController::class, 'apiSearch'])->middleware('throttle:60,1');
    Route::get('tafsir/{source}/{surah}', [QuranController::class, 'apiTafsir'])->whereNumber(['source', 'surah'])->middleware('throttle:60,1');
});

// Zikr (guest access)
Route::get('/zikr', fn () => Inertia::render('ZikrHub'))->name('zikr');
Route::get('/zikr/adhkar', [\App\Http\Controllers\AdhkarController::class, 'index'])->name('adhkar');
Route::get('/zikr/adhkar/{slug}', [\App\Http\Controllers\AdhkarController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('adhkar.show');
Route::get('/zikr/adhkar-favorites', [\App\Http\Controllers\AdhkarController::class, 'favorites'])->name('adhkar.favorites');
Route::get('/zikr/tasbeeh', fn () => Inertia::render('Tasbeeh'))->name('zikr.tasbeeh');

// 99 Names of Allah (static, on device)
Route::get('/names', fn () => Inertia::render('Names'))->name('names');

// Prayer + Qibla (guest access; calculated on the device)
Route::get('/prayer', fn () => Inertia::render('Prayer'))->name('prayer');
Route::get('/qibla', fn () => Inertia::render('Qibla'))->name('qibla');

// Profile (guest + account)
Route::get('/profile', fn () => Inertia::render('Profile'))->name('profile');

// Account sync API (session auth + CSRF)
Route::middleware('auth')->prefix('api/v1')->group(function () {
    Route::post('sync', [SyncController::class, 'sync'])->middleware('throttle:20,1');
    Route::get('me/export', [SyncController::class, 'export'])->middleware('throttle:5,1');
    Route::delete('devices/{id}', [SyncController::class, 'removeDevice'])->whereNumber('id');
});

// Admin two-factor (outside the panel so it can run before 2FA is passed)
Route::middleware('auth')->prefix('admin-security')->group(function () {
    Route::get('setup', [\App\Http\Controllers\AdminTwoFactorController::class, 'setup'])->name('admin.2fa.setup');
    Route::post('setup', [\App\Http\Controllers\AdminTwoFactorController::class, 'confirm'])->name('admin.2fa.confirm')->middleware('throttle:6,1');
    Route::get('check', [\App\Http\Controllers\AdminTwoFactorController::class, 'challenge'])->name('admin.2fa.challenge');
    Route::post('check', [\App\Http\Controllers\AdminTwoFactorController::class, 'verify'])->name('admin.2fa.verify')->middleware('throttle:6,1');
});

// Push reminders
Route::prefix('api/v1/push')->middleware('throttle:30,1')->group(function () {
    Route::get('key', [\App\Http\Controllers\PushController::class, 'key']);
    Route::post('subscribe', [\App\Http\Controllers\PushController::class, 'subscribe']);
    Route::post('schedule', [\App\Http\Controllers\PushController::class, 'schedule']);
    Route::post('unsubscribe', [\App\Http\Controllers\PushController::class, 'unsubscribe']);
});

// Qurba Learning
Route::get('/learn', [\App\Http\Controllers\LearnController::class, 'index'])->name('learn');
Route::post('/learn/interest', [\App\Http\Controllers\LearnController::class, 'interest'])->middleware('throttle:5,1')->name('learn.interest');

// Module placeholders (guest access)
foreach ([] as $section) {
    Route::get("/{$section}/{any?}", fn () => Inertia::render('Section', ['section' => $section]))
        ->where('any', '.*')->name($section);
}

// Starter kit route files (loaded only if present)
foreach (['settings.php', 'auth.php'] as $file) {
    if (file_exists(__DIR__.'/'.$file)) {
        require __DIR__.'/'.$file;
    }
}
// ===== QURBA ROUTES — END =====

// Reviewed library content for Hadith, Seerah, Kids, Naats and Ruqyah.
Route::prefix('api/v1/library')->middleware('throttle:60,1')->group(function () {
    Route::get('modules', [\App\Http\Controllers\ContentLibraryController::class, 'modules']);
    Route::get('{module}', [\App\Http\Controllers\ContentLibraryController::class, 'index']);
    Route::get('{module}/{slug}', [\App\Http\Controllers\ContentLibraryController::class, 'show']);
});

Route::get('/library/{module}', [\App\Http\Controllers\ContentLibraryController::class, 'page'])
    ->where('module', 'hadith|seerah|kids|naats|ruqyah')->name('library.module');

Route::get('/explore', fn () => Inertia::render('Explore'))->name('explore');
