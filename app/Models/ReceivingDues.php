<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property mixed $house
 * @property mixed $reports
 * @property mixed $items
 * @property mixed $commissions
 * @property mixed $house_id
 * @method static where(string $string, $selectedHouse)
 */
class ReceivingDues extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $with = ['house'];

    protected $casts = [
        'commissions' => 'array',
        'items' => 'array',
    ];

    public function house(): BelongsTo
    {
        return $this->belongsTo(House::class);
    }
}
