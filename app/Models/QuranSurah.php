<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuranSurah extends Model
{
    public $incrementing = false;
    protected $guarded = [];

    public function ayahs(): HasMany
    {
        return $this->hasMany(QuranAyah::class, 'surah_id')->orderBy('ayah_number');
    }
}
