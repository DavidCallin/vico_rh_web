<?php

namespace Database\Seeders;

use App\Models\Absence;
use App\Models\Employee;
use App\Models\PayPeriod;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AbsenceSeeder extends Seeder
{
    public function run(): void
    {
        $employees = Employee::where('status', 'active')->limit(10)->get();
        $types = ['medical', 'family_emergency', 'unjustified', 'other'];
        $statuses = ['pending', 'approved', 'rejected'];

        foreach ($employees as $employee) {
            // Créer 1 à 2 absences par employé
            $count = rand(1, 2);
            
            for ($i = 0; $i < $count; $i++) {
                $startDate = Carbon::now()->subDays(rand(5, 60));
                $endDate = $startDate->copy()->addDays(rand(1, 5));
                
                // Trouver la période de paie correspondante
                $payPeriod = PayPeriod::where('year', $startDate->year)
                    ->where('month', $startDate->month)
                    ->first();

                Absence::create([
                    'employee_id' => $employee->id,
                    'type' => $types[array_rand($types)],
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'reason' => 'Motif généré automatiquement par le seeder pour tester l\'affichage.',
                    'proof_path' => rand(0, 1) ? 'absences/proofs/fake_certificate.pdf' : null, // 50% de chance d'avoir un justificatif
                    'status' => $statuses[array_rand($statuses)],
                    'pay_period_id' => $payPeriod?->id,
                    'validation_date' => rand(0, 1) ? $startDate->copy()->addDays(2)->format('Y-m-d H:i:s') : null,
                    'validator_id' => rand(0, 1) ? 1 : null, // ID 1 = Admin/RH de test
                ]);
            }
        }

        $this->command->info('✅ Absences de test créées avec succès !');
    }
}