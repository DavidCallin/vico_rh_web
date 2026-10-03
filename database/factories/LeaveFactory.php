<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Leave;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Leave>
 */
class LeaveFactory extends Factory
{
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-1 year', '+1 year');
        $endDate = (clone $startDate)->modify('+'.fake()->numberBetween(1, 15).' days');

        return [
            'leave_type' => fake()->randomElement(['annual', 'sick', 'unpaid', 'maternity', 'paternity', 'family', 'other']),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days_count' => fake()->randomFloat(1, 1, 15),
            'status' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'validation_date' => fake()->boolean(50) ? now() : null,
            'reason' => fake()->sentence(),
            'employee_id' => Employee::factory(),
            'validator_id' => null,
            'pay_period_id' => null,
        ];
    }
}
