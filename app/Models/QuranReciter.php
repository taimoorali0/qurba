<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuranReciter extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['active' => 'boolean'];

    public function audio(): HasMany
    {
        return $this->hasMany(QuranAudio::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(ContentSource::class, 'content_source_id');
    }
}
