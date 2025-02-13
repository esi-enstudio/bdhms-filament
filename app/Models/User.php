<?php

namespace App\Models;

 use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
 use Illuminate\Database\Eloquent\Relations\BelongsToMany;
 use Illuminate\Database\Eloquent\Relations\HasMany;
 use Illuminate\Database\Eloquent\Relations\HasOne;
 use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

 /**
 * @method static create(array $user)
 * @method static latest()
 * @method static when()
  * @method static search(mixed $search)
  * @method static where()
  * @method static insert()
  * @method static firstWhere()
  * @method static whereIn(string $string, $supervisorId)
  * @method static paginate(mixed $perPage)
  * @method static findOrFail( mixed $userId )
  * @method static updateOrCreate( array $array, array $array1 )
  * @method static find(mixed $userId)
  * @method static pluck(string $string)
  * @method static count()
  * @method static whereHas(string $string, \Closure $param)
  * @method static whereNotIn(string $string, $userId)
  * @method static role(string $string)
  * @method static select()
  * @property mixed $created_at
  * @property mixed $updated_at
  * @property mixed $email_verified_at
  * @property mixed $disabled_at
  * @property mixed|string $created
  * @property mixed|string $updated
  * @property mixed|string $email_verified
  * @property mixed|string $disabled
  * @property mixed|string $remember
  * @property mixed $remember_token
  * @property mixed $avatar
  * @property mixed $id
  * @property mixed|string $update
  * @property mixed|string $password
  * @property mixed $name
  * @property mixed $houses
  */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;


    /**
     * The attributes that are mass assignable.
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
        return $this->belongsToMany(House::class, 'house_user');
    }

}
