<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuranTranslation extends Model
{
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::updating(function (QuranTranslation $tr) {
            if ($tr->isDirty('text')) {
                $tr->content_hash = hash('sha256', $tr->text);
                ContentAuditLog::record($tr, 'updated', ['text' => $tr->getOriginal('text')], ['text' => $tr->text]);
            }
        });
    }

    public function ayah(): BelongsTo
    {
        return $this->belongsTo(QuranAyah::class, 'quran_ayah_id');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(ContentSource::class, 'content_source_id');
    }
}
