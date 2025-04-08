<?php

namespace App\Models;

use App\Models\House;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @method static where(string $string, mixed $houseId)
 * @method static whereDate(string $string, string $today)
 * @method static create(array $array)
 * @method static first()
 * @method static latest()
 */
class Stock extends Model
{
    protected $guarded = [];
    protected $with = ['house'];

    protected $casts = [
        'products' => 'array',
    ];

    public function house(): BelongsTo
    {
        return $this->belongsTo(House::class);
    }

    // public function products()
    // {
    //     // Assuming the JSON data in the `products` column has a `product_id` field
    //     return $this->hasMany(Product::class, 'id', 'product_id');
    // }
}
