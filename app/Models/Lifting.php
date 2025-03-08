<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @method static create( array $validated )
 * @method static latest()
 * @method static sum(string $string)
 * @method static whereBetween(string $string, array $array)
 * @property mixed $house_id
 * @property mixed $products
 * @property mixed $itopup
 */
class Lifting extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $with = ['user','house'];

    protected $casts = [
        'products' => 'array',
    ];

    public function house(): BelongsTo
    {
        return $this->belongsTo(House::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
