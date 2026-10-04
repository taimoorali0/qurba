<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearnInterest extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['for_child' => 'boolean', 'contact_consent' => 'boolean'];

    public function course(): BelongsTo { return $this->belongsTo(Course::class); }
}
