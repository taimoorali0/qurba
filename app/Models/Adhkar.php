<?php
// ===== QURBA: one dhikr / dua. Any change to an approved item sends it back to review. =====
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Adhkar extends Model
{
    protected $table = 'adhkar';
    protected $casts = ['audio_ai' => 'boolean', 'audio_approved' => 'boolean'];
    protected $guarded = ['id'];

    /** Only the reviewer approval action switches this on. */
    public static bool $approving = false;
    /** True while the AI voice generator saves, so its audio stays unapproved */
    public static bool $aiVoice = false;

    protected static function booted(): void
    {
        static::saving(function (Adhkar $a) {
            // A recording uploaded by hand replaces any AI voice and needs no voice review
            if ($a->isDirty('audio_url') && ! static::$aiVoice) { $a->audio_ai = false; $a->audio_approved = true; }
            $a->content_hash = hash('sha256', $a->text_arabic . '|' . $a->reference . '|' . $a->repeat_count);
            if (static::$approving) return;
            if ($a->status === 'approved' && $a->isDirty('status')) $a->status = 'in_review'; // cannot self-approve via form
            if ($a->exists && $a->getOriginal('status') === 'approved' && $a->isDirty(['text_arabic', 'transliteration', 'reference', 'repeat_count'])) {
                $a->status = 'in_review';
            }
        });
        static::saved(function (Adhkar $a) {
            if ($a->wasChanged(['text_arabic', 'transliteration', 'reference', 'repeat_count', 'status'])) {
                ContentAuditLog::record($a, $a->wasRecentlyCreated ? 'created' : 'updated', null,
                    array_intersect_key($a->getChanges(), array_flip(['text_arabic', 'transliteration', 'reference', 'repeat_count', 'status'])));
            }
        });
    }

    public function category(): BelongsTo { return $this->belongsTo(AdhkarCategory::class, 'adhkar_category_id'); }
    public function translations(): HasMany { return $this->hasMany(AdhkarTranslation::class); }
    public function source(): BelongsTo { return $this->belongsTo(ContentSource::class, 'content_source_id'); }
}
