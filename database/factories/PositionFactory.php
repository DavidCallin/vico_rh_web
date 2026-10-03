<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Position>
 */
class PositionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->jobTitle(),
            'department_id' => Department::factory(),
            'category_id' => Category::factory(),
        ];
    }
}
