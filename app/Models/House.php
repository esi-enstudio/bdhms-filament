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
 * @method static create(array|mixed[] $data)
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


    /** @return HasMany<Commission, self> */
    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }


    /** @return HasMany<DailyExpense, self> */
    public function dailyExpenses(): HasMany
    {
        return $this->hasMany(DailyExpense::class);
    }


    /** @return HasMany<ReceivingDues, self> */
    public function receivingDues(): HasMany
    {
        return $this->hasMany(ReceivingDues::class);
    }


    /** @return HasMany<Retailer, self> */
    public function retailers(): HasMany
    {
        return $this->hasMany(Retailer::class);
    }


    /** @return HasMany<RsoLifting, self> */
    public function rsoLiftings(): HasMany
    {
        return $this->hasMany(RsoLifting::class);
    }


    /** @return HasMany<\App\Models\Rso, self> */
    public function rsos(): HasMany
    {
        return $this->hasMany(\App\Models\Rso::class);
    }


    /** @return HasMany<RsoSales, self> */
    public function rsoSales(): HasMany
    {
        return $this->hasMany(RsoSales::class);
    }


    /** @return HasMany<RsoStock, self> */
    public function rsoStocks(): HasMany
    {
        return $this->hasMany(RsoStock::class);
    }


    /** @return HasMany<Sales, self> */
    public function sales(): HasMany
    {
        return $this->hasMany(Sales::class);
    }


    /** @return HasMany<Stock, self> */
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

}
