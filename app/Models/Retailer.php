<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @method static search($search)
 * @method static insert()
 * @method static whereNotNull( string $string )
 * @method static create( array $attributes )
 * @method static firstWhere()
 * @method static where()
 * @method static pluck(string $string)
 * @method static count()
 * @method static whereIn(string $string, array $selectedRecords)
 * @method static findOrFail($id)
 * @method static truncate()
 * @method static select(string $string, string $string1)
 * @method static when($allRetailersSelected, \Closure $param, \Closure $param1)
 * @method static upsert(mixed[] $data, string[] $array, string[] $array1)
 * @property mixed $zm
 * @property mixed $manager
 * @property mixed $supervisor
 * @property mixed $created_at
 * @property mixed $updated_at
 * @property mixed $disabled_at
 * @property mixed $id
 * @property mixed $document
 * @property mixed $user
 */
class Retailer extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $with = ['rso','house','user'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'others_operator' => 'array',
        ];
    }

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
    public function rso(): BelongsTo
    {
        return $this->belongsTo(Rso::class);
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


    protected function itopNumber(): Attribute
    {
        return Attribute::make(
            set: fn(string $value) => '0'.$value,
        );
    }
}
