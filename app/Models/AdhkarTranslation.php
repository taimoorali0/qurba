<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdhkarTranslation extends Model
{
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        // A changed translation also needs review again
        $back = function (AdhkarTranslation $t) {
            $parent = $t->adhkar;
            if ($parent && $parent->status === 'approved' && ! Adhkar::$approving) {
                $parent->status = 'in_review';
                $parent->save();
            }
        };
        static::saved($back);
        static::deleted($back);
    }

    public function adhkar(): BelongsTo { return $this->belongsTo(Adhkar::class); }
}
