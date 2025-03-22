<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static search( $search )
 * @method static create( array $attributes )
 * @method static insert(array[] $array)
 * @method static findOrFail(mixed $param)
 * @method static where(string $string, string $string1)
 * @method static find(mixed $param)
 */
class Product extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected array $searchable = [
        'name',
        'code',
        'lifting_price',
        'face_value',
    ];
}
