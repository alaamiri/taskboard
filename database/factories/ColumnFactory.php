<?php

namespace Database\Factories;

use App\Models\Board;
use Illuminate\Database\Eloquent\Factories\Factory;

class ColumnFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['To Do', 'In Progress', 'Done', 'Review']),
            'position' => fake()->numberBetween(0, 10),
            'board_id' => Board::factory()
        ];
    }
}
