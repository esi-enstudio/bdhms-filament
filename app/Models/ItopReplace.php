<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @method static search(mixed $search)
 * @method static create(array $attributes)
 * @method static firstWhere( string $string, mixed $dd_house_id )
 * @method static where(string $string, mixed $retailer_id)
 * @method static select( string $string )
 * @method static truncate()
 * @method static count()
 * @property mixed $dd_house_id
 * @property mixed $manager
 * @property mixed $rso_id
 * @property mixed $user_id
 * @property mixed $supervisor
 * @property mixed $zm
 * @property mixed $user
 * @property mixed $status
 * @property mixed $number
 * @property mixed $remarks
 * @property mixed|string $requested
 * @property mixed $requested_at
 * @property mixed $created_at
 * @property mixed|string $created
 * @property mixed $completed_at
 * @property mixed|string $completed
 */
class ItopReplace extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $with = ['user','retailer'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function house(): BelongsTo
    {
        return $this->belongsTo(House::class);
    }

    public function retailer(): BelongsTo
    {
        return $this->belongsTo(Retailer::class);
    }
}
