<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property mixed $house
 * @property mixed $items
 */
class DailyExpense extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $with = ['house'];

    protected $casts = [
        'items' => 'array',
    ];

    public function house(): BelongsTo
    {
        return $this->belongsTo(House::class);
    }
}
