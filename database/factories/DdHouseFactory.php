<?php

namespace Database\Factories;

use App\Models\DdHouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DdHouse>
 */
class DdHouseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->randomElement(['MYMVAI01','MYMVAI02','MYMVAI03']),
            'name' => fake()->unique()->randomElement(['Patwary','Modina','Sumaya']),
        ];
    }
}
