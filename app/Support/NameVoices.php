<?php
// ===== QURBA: recorded voices for the 99 Names (add a voice here to get a new tab in the admin) =====
namespace App\Support;

use Illuminate\Support\Facades\Storage;

class NameVoices
{
    /** key => [label, folder for 1.mp3…99.mp3, complete-recitation file] */
    public const ALL = [
        'standard' => ['label' => 'Standard', 'dir' => 'names', 'full' => 'names-full.mp3'],
        'kids' => ['label' => 'For kids', 'dir' => 'names-kids', 'full' => 'names-kids-full.mp3'],
    ];

    public static function get(string $key): array
    {
        return self::ALL[$key] ?? self::ALL['standard'];
    }

    /** Recorded name numbers for a voice */
    public static function recorded(string $key): array
    {
        $dir = self::get($key)['dir'];
        return collect(Storage::disk('audio')->files($dir))->map(fn ($f) => (int) basename($f, '.mp3'))
            ->filter(fn ($n) => $n >= 1 && $n <= 99)->unique()->sort()->values()->all();
    }

    /** For the app: every voice with what it has */
    public static function manifest(): array
    {
        $d = Storage::disk('audio');
        return collect(self::ALL)->map(fn ($v, $key) => [
            'label' => $v['label'], 'dir' => $v['dir'], 'names' => self::recorded($key),
            'full' => $d->exists($v['full']) ? $v['full'] : null,
        ])->all();
    }
}
