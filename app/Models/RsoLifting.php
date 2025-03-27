<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RsoLifting extends Model
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
