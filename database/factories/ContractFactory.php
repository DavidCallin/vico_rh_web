<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContractFactory extends Factory
{
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-3 years', 'now');
        $endDate = fake()->boolean(70) ? fake()->dateTimeBetween($startDate, '+5 years') : null;
        
        return [
            'contract_type' => fake()->randomElement(['cdi', 'cdd', 'internship', 'interim', 'apprenticeship']),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'base_salary' => fake()->randomFloat(2, 5000, 50000),
            'pdf_path' => null,
            'employee_id' => Employee::factory(),
        ];
    }
}