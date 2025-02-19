<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @method static when(mixed $search, \Closure $param)
 * @method static search(mixed $search)
 * @method static whereNotNull(string $string)
 * @method static create( array $attr )
 * @method static insert( array[] $array )
 * @method static where()
 * @method static firstWhere()
 * @method static pluck(string $string)
 * @method static count()
 * @method static whereIn(string $string, $selectedUsers)
 * @method static findOrFail($id)
 * @method static select(string $string, string $string1, string $string2)
 * @method static upsert(array $users, string[] $array, string[] $array1)
 * @property mixed $documents
 * @property mixed $user
 * @property mixed $user_id
 */
class Rso extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $with = ['user','house'];

    /**
     * Relationship with User model
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship with User model
     *
     * @return BelongsTo
     */
    public function house(): BelongsTo
    {
        return $this->belongsTo(House::class);
    }

    /**
     * Relationship with User model
     *
     * @return HasMany
     */
    public function retailer(): HasMany
    {
        return $this->hasMany(Retailer::class);
    }
}
