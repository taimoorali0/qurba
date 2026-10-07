<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentEntry extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['title' => 'array', 'body' => 'array', 'summary' => 'array'];

    public function source(): BelongsTo
    {
        return $this->belongsTo(ContentSource::class, 'content_source_id');
    }

    public function recordings(): HasMany
    {
        return $this->hasMany(ContentRecording::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->whereHas('source', fn ($q) => $q
            ->where('status', 'approved')->where('redistribution_allowed', true));
    }
}
