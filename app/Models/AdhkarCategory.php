<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdhkarCategory extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['name' => 'array'];

    public function adhkar(): HasMany
    {
        return $this->hasMany(Adhkar::class)->orderBy('sort');
    }

    public function label(string $locale = 'en'): string
    {
        return $this->name[$locale] ?? $this->name['en'] ?? $this->slug;
    }
}
