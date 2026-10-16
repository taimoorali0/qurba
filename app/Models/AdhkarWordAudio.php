<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** AI voice clip for one Arabic word, shared across adhkar. */
class AdhkarWordAudio extends Model
{
    protected $table = 'adhkar_word_audio';
    protected $guarded = ['id'];
    protected $casts = ['approved' => 'boolean'];

    /** Words of a dua as written (harakat kept), in reading order. */
    public static function words(string $arabic): array
    {
        return array_values(array_filter(preg_split('/\s+/u', trim($arabic)), fn ($w) => preg_match('/\p{L}/u', $w)));
    }

    public static function hashOf(string $word): string
    {
        return hash('sha256', $word);
    }

    /** word hash => clip, for the words of the given duas */
    public static function forTexts(iterable $texts, bool $approvedOnly)
    {
        $hashes = collect($texts)->flatMap(fn ($t) => static::words($t))->unique()->map(fn ($w) => static::hashOf($w));
        return static::whereIn('word_hash', $hashes)->when($approvedOnly, fn ($q) => $q->where('approved', true))->get()->keyBy('word_hash');
    }
}
