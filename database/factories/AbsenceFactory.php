<?php

namespace Database\Factories;

use App\Models\Absence;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Absence>
 */
class AbsenceFactory extends Factory
{
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-1 month', '+1 month');
        $endDate = (clone $startDate)->modify('+'.fake()->numberBetween(0, 3).' days');

        return [
            'type' => fake()->randomElement(['unjustified', 'medical', 'family_emergency', 'other']),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => fake()->sentence(),
            'proof_path' => null,
            'status' => 'pending',
            'validation_date' => null,
            'employee_id' => Employee::factory(),
            'validator_id' => null,
            'pay_period_id' => null,
        ];
    }
}
