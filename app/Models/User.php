<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @method static whereHas(string $string, \Closure $param)
 * @method static where(string $string, string $string1)
 * @method static firstOrNew(array $array)
 * @method static firstWhere(string $string, string $userPhoneNumber)
 * @method static insert(array[] $array)
 * @method static firstOrCreate(string[] $array, array $array1)
 * @method static whereIn(string $string, $usersNumber)
 * @property mixed|string $phone
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasRoles;


    /**
     * The attributes that are mass assignabl
     * e.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'avatar',
        'phone',
        'name',
        'email',
        'password',
        'status',
        'remarks',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'disabled_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Relationship with Rso model
     *
     * @return HasOne
     */
    public function rso(): HasOne
    {
        return $this->hasOne(Rso::class);
    }

    /**
     * Relationship with Retailer model
     *
     * @return HasOne
     */
    public function retailer(): HasOne
    {
        return $this->hasOne(Retailer::class);
    }

    /**
     * Relationship with Retailer model
     *
     * @return HasMany
     */
    public function lifting(): HasMany
    {
        return $this->hasMany(Lifting::class);
    }

    public function houses(): BelongsToMany
    {
        return $this->belongsToMany(House::class, 'house_user')->withTimestamps();
    }
}
