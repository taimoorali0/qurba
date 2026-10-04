<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuranTafsir extends Model
{
    protected $fillable = ['content_source_id', 'surah_id', 'ayah_number', 'text'];

    public function source(): BelongsTo
    {
        return $this->belongsTo(ContentSource::class, 'content_source_id');
    }
}
