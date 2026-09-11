<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PayrollItemFactory extends Factory
{
    public function definition(): array
    {
        $type = fake()->randomElement(['gain', 'deduction', 'contribution']);
        
        return [
            'name' => fake()->randomElement([
                'Salaire de base', 'Prime de transport', 'CNSS', 'IR', 
                'Mutuelle', 'Prime de rendement', 'Heures supplémentaires'
            ]),
            'code' => fake()->unique()->regexify('[A-Z]{4}[0-9]{3}'),
            'type' => $type,
            'amount_or_rate' => fake()->randomFloat(2, 0, 100),
            'calculation' => fake()->randomElement(['fixed', 'percentage', 'formula']),
            'isActive' => true,
        ];
    }
}