<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Cadre', 'ETAM', 'Ouvrier', 'Technicien']),
            'code' => fake()->unique()->regexify('[A-Z]{3}[0-9]{2}'),
        ];
    }
}