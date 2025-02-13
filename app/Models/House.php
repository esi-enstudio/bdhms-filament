<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @method static insert(array[] $array)
 * @method static findOrFail( $userId )
 * @method static count()
 * @method static pluck( string $string )
 * @method static whereIn( string $string, $selectedUsers )
 * @method static find($userId)
 * @method static where( string $string, string $string1 )
 * @method static create(array $attr)
 * @method static firstWhere()
 * @method static select(string $string, string $string1, string $string2)
 * @property mixed $id
 */
class House extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected array $searchable = [
        'name',
        'code',
    ];

    /**
     * Relationship with User model
     *
     */
    public function rso(): HasMany
    {
        return $this->hasMany(Rso::class);
    }

    public function liftings(): HasMany
    {
        return $this->hasMany(Lifting::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

}
