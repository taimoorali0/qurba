<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentSource extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'redistribution_allowed' => 'boolean',
        'offline_allowed' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
