<?php

namespace Database\Factories;

use App\Models\Rso;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rso>
 */
class RsoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dd_house_id' => fake()->numberBetween(1, 3),
            'user_id' => fake()->numberBetween(1, 300),
            'zm_number' => fake()->randomElement(),
            'manager_number' => fake()->e164PhoneNumber(),
            'supervisor_number' => fake()->e164PhoneNumber(),
            'number' => fake()->unique()->e164PhoneNumber,
            'code' => fake()->unique()->e164PhoneNumber,
        ];
    }
}
