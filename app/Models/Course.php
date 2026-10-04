<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['title' => 'array', 'summary' => 'array', 'active' => 'boolean'];

    public function interests(): HasMany { return $this->hasMany(LearnInterest::class); }
}
