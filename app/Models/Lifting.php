<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static create( array $validated )
 * @method static latest()
 * @method static sum(string $string)
 * @method static whereBetween(string $string, array $array)
 */
class Lifting extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $with = ['user','ddHouse'];

    protected $casts = [
        'products' => 'array',
    ];

    public function ddHouse(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(House::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
