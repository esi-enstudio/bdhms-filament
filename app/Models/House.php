<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @method static firstWhere(string $string, string $ddCode)
 * @method static whereIn(string $string, \Illuminate\Support\Collection $houseCodes)
 * @method static where(string $string, string $string1)
 * @method static find($state)
 * @method static pluck(string $string, string $string1)
 */
class House extends Model
{
    use HasFactory;

    protected $guarded = [];

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
        return $this->belongsToMany(User::class)->withTimestamps();
    }

}
