<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\LeaveBalance;
use Illuminate\Database\Seeder;

class LeaveBalanceSeeder extends Seeder
{
    public function run(): void
    {
        $currentYear = date('Y');
        $defaultDays = 25; // 25 jours de congés payés par an (standard français)

        $employees = Employee::where('status', 'active')->get();

        foreach ($employees as $employee) {
            LeaveBalance::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'year' => $currentYear,
                ],
                [
                    'total_days' => $defaultDays,
                    'taken_days' => 0,
                    'remaining_days' => $defaultDays,
                    'pending_days' => 0,
                ]
            );
        }

        $this->command->info("✅ Soldes de congé créés pour {$employees->count()} employé(s) pour l'année {$currentYear}");
    }
}