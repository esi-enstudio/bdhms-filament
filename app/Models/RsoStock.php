<?php

namespace App\Models;

use App\Models\House;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property mixed $house_id
 */
class RsoStock extends Model
{
    protected $guarded = [];
    protected $with = ['house','rso'];

    protected $casts = [
        'products' => 'array',
    ];

    public function house(): BelongsTo
    {
        return $this->belongsTo(House::class);
    }

    public function rso(): BelongsTo
    {
        return $this->belongsTo(Rso::class);
    }
}
