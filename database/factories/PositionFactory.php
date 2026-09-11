<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

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