<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Word-by-word or tajweed data for one ayah, from one content source. */
class QuranAyahData extends Model
{
    protected $table = 'quran_ayah_data';
    protected $fillable = ['content_source_id', 'surah_id', 'ayah_number', 'data'];
    protected $casts = ['data' => 'array'];

    public function source(): BelongsTo
    {
        return $this->belongsTo(ContentSource::class, 'content_source_id');
    }
}
