<?php
// ===== QURBA admin: how complete the content is =====
namespace App\Filament\Widgets;

use App\Models\Adhkar;
use App\Models\ContentSource;
use App\Models\QuranAyah;
use App\Support\Quran\QuranStructure;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Storage;

class ContentHealth extends Widget
{
    protected static string $view = 'filament.widgets.content-health';
    protected static ?int $sort = 2;

    protected function getViewData(): array
    {
        $approved = fn (string $type) => ContentSource::where('type', $type)->where('status', 'approved')->count();
        $all = fn (string $type) => ContentSource::where('type', $type)->count();
        $audio = Storage::disk('audio');
        $names = collect($audio->files('names'))->map(fn ($f) => (int) basename($f, '.mp3'))->filter(fn ($n) => $n >= 1 && $n <= 99)->unique()->count();
        $adhkarTotal = Adhkar::count();

        return ['bars' => [
            ['Quran text', QuranAyah::count(), QuranStructure::AYAH_COUNT, 'ayahs'],
            ['Adhkar approved', Adhkar::where('status', 'approved')->count(), max(1, $adhkarTotal), 'items'],
            ['99 Names audio', $names, 99, 'recordings'],
        ], 'facts' => [
            ['Translations', $approved('translation') . ' approved / ' . $all('translation')],
            ['Tafsir editions', $approved('tafsir') . ' approved / ' . $all('tafsir')],
            ['Reciters (audio)', $approved('audio') . ' approved / ' . $all('audio')],
            ['Adhan recording', $audio->exists('adhan.mp3') ? 'Added' : 'Missing'],
            ['99 Names — kids voice', count(\App\Support\NameVoices::recorded('kids')) . ' / 99' . ($audio->exists('names-kids-full.mp3') ? ' + complete' : '')],
            ['Complete 99 Names recitation', $audio->exists('names-full.mp3') ? 'Added' : 'Missing'],
            ['Background sound', $audio->exists('ambient.mp3') ? 'Added' : 'Missing'],
        ]];
    }
}
