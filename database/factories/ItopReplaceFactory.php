<?php

namespace Database\Factories;

use App\Models\ItopReplace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItopReplace>
 */
class ItopReplaceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => fake()->numberBetween(1, 10),
            'retailer_id' => fake()->numberBetween(1, 20),
            'sim_serial' => fake()->unique()->creditCardNumber(),
            'balance' => fake()->numberBetween(500, 5000),
            'reason' => fake()->text(10),
            'remarks' => fake()->text(10),
            'description' => fake()->realText(20),
            'requested_at' => now(),
        ];
    }
}
