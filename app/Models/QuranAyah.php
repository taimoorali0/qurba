<?php
namespace App\Models;

use App\Support\Quran\ArabicNormalizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

class QuranAyah extends Model
{
    protected $guarded = ['id'];

    /** Only the verified importer may switch this on. */
    public static bool $importMode = false;

    protected static function booted(): void
    {
        static::updating(function (QuranAyah $ayah) {
            if ($ayah->isDirty('text_uthmani') && ! static::$importMode) {
                throw new RuntimeException("Quran text for {$ayah->ayah_key} is protected. Use the verified importer.");
            }
            if ($ayah->isDirty('text_uthmani')) {
                $ayah->text_search = ArabicNormalizer::forSearch($ayah->text_uthmani);
                $ayah->content_hash = ArabicNormalizer::hash($ayah->text_uthmani);
                ContentAuditLog::record($ayah, 'updated',
                    ['text_uthmani' => $ayah->getOriginal('text_uthmani')],
                    ['text_uthmani' => $ayah->text_uthmani]);
            }
        });

        static::deleting(function (QuranAyah $ayah) {
            if (! static::$importMode) {
                throw new RuntimeException('Quran ayahs cannot be deleted.');
            }
        });
    }

    public function surah(): BelongsTo
    {
        return $this->belongsTo(QuranSurah::class, 'surah_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(QuranTranslation::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(ContentSource::class, 'content_source_id');
    }

    public function isIntact(): bool
    {
        return hash_equals($this->content_hash, ArabicNormalizer::hash($this->text_uthmani));
    }
}
