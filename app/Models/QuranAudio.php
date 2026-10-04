<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuranAudio extends Model
{
    protected $table = 'quran_audio';
    protected $guarded = ['id'];
    protected $casts = ['stream_allowed' => 'boolean', 'offline_allowed' => 'boolean'];

    public function reciter(): BelongsTo
    {
        return $this->belongsTo(QuranReciter::class, 'quran_reciter_id');
    }

    /** Full URL using AUDIO_BASE_URL from .env (CDN / object storage / provider). */
    public function fullUrl(): string
    {
        return str_starts_with($this->url, 'http')
            ? $this->url
            : rtrim(config('qurba.audio_base_url'), '/') . '/' . ltrim($this->url, '/');
    }
}
