<?php

namespace Database\Factories;

use App\Models\PayPeriod;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayPeriod>
 */
class PayPeriodFactory extends Factory
{
    public function definition(): array
    {
        $month = fake()->numberBetween(1, 12);
        $year = fake()->numberBetween(2024, 2026);

        return [
            'month' => $month,
            'year' => $year,
            'status' => 'open',
            'opening_date' => Carbon::create($year, $month, 1),
            'closing_date' => null,
            'closure_type' => null,
            'closed_by' => null,
        ];
    }
}
