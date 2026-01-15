<?php

namespace Database\Factories;

use App\Models\Column;
use Illuminate\Database\Eloquent\Factories\Factory;

class CardFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'position' => fake()->numberBetween(0, 10),
            'column_id' => Column::factory(),
            'user_id' => null
        ];
    }
}
