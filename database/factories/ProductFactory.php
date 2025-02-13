<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name,
            'code' => fake()->postcode,
            'category' => fake()->colorName,
            'face_value' => fake()->randomDigitNotZero(),
            'lifting_price' => fake()->randomNumber(),
        ];
    }
}
