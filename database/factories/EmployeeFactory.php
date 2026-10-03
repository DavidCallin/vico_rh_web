<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'matricule' => fake()->unique()->regexify('EMP[0-9]{5}'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'birth_date' => fake()->dateTimeBetween('-50 years', '-20 years'),
            'gender' => fake()->randomElement(['M', 'F']),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->address(),
            'hire_date' => fake()->dateTimeBetween('-5 years', 'now'),
            'site' => fake()->city(),
            'status' => 'active',
            'position_id' => Position::factory(),
            'category_id' => Category::factory(),
        ];
    }
}
