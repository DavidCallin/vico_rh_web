<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class DepartmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Ressources Humaines', 'Finance', 'Informatique', 'Commercial', 'Production', 'Marketing']),
            'description' => fake()->sentence(),
            'manager_id' => null, // On l'assignera après
        ];
    }
}