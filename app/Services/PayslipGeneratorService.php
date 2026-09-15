<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PayPeriod;
use App\Models\PayrollItem;
use App\Models\Payslip;
use App\Models\PayslipLine;
use Illuminate\Support\Facades\DB;

class PayslipGeneratorService
{
    /**
     * Génère le bulletin de paie d'un employé pour une période donnée
     */
    public function generate(Employee $employee, PayPeriod $period): Payslip
    {
        // Vérifier qu'un bulletin n'existe pas déjà
        $existing = Payslip::where('employee_id', $employee->id)
            ->where('pay_period_id', $period->id)
            ->first();

        if ($existing) {
            throw new \Exception('Un bulletin existe déjà pour cet employé sur cette période.');
        }

        // Récupérer le contrat actif de l'employé
        $contract = $employee->contracts()
            ->where('start_date', '<=', $period->closing_date ?? now())
            ->where(function ($q) use ($period) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $period->opening_date);
            })
            ->orderBy('start_date', 'desc')
            ->first();

        if (!$contract) {
            throw new \Exception('Aucun contrat actif trouvé pour cet employé sur cette période.');
        }

        $baseSalary = $contract->base_salary;
        $totalEarnings = 0;
        $totalDeductions = 0;
        $lines = [];

        // Récupérer toutes les rubriques actives
        $payrollItems = PayrollItem::where('is_active', true)->get();

        foreach ($payrollItems as $item) {
            $amount = $this->calculateAmount($item, $baseSalary);
            
            if ($amount == 0) continue;

            $lines[] = [
                'payroll_item_id' => $item->id,
                'amount' => $amount,
                'quantity' => 1,
            ];

            if ($item->type === 'gain') {
                $totalEarnings += $amount;
            } else {
                $totalDeductions += $amount;
            }
        }

        // Créer le bulletin dans une transaction (tout ou rien)
        return DB::transaction(function () use ($employee, $period, $baseSalary, $totalEarnings, $totalDeductions, $lines) {
            $payslip = Payslip::create([
                'employee_id' => $employee->id,
                'pay_period_id' => $period->id,
                'generation_date' => now(),
                'gross_salary' => $baseSalary,
                'total_earnings' => $totalEarnings,
                'total_deductions' => $totalDeductions,
                'net_salary' => $totalEarnings - $totalDeductions,
                'status' => 'draft',
            ]);

            // Créer les lignes du bulletin
            foreach ($lines as $line) {
                PayslipLine::create([
                    'payslip_id' => $payslip->id,
                    'payroll_item_id' => $line['payroll_item_id'],
                    'amount' => $line['amount'],
                    'quantity' => $line['quantity'],
                ]);
            }

            return $payslip;
        });
    }

    /**
     * Calcule le montant d'une rubrique selon sa méthode de calcul
     */
    private function calculateAmount(PayrollItem $item, float $baseSalary): float
    {
        return match ($item->calculation) {
            'fixed' => (float) $item->amount_or_rate,
            'percentage' => $baseSalary * ((float) $item->amount_or_rate / 100),
            'formula' => $this->evaluateFormula($item->amount_or_rate, $baseSalary),
            default => 0,
        };
    }

    /**
     * Évalue une formule simple (ex: "base * 0.1 + 100")
     */
    private function evaluateFormula(string $formula, float $baseSalary): float
    {
        // Remplace les variables par leurs valeurs
        $formula = str_replace('base', (string) $baseSalary, $formula);
        
        // Sécurité : n'évaluer que les expressions mathématiques simples
        if (!preg_match('/^[\d\s\+\-\*\/\.\(\)]+$/', $formula)) {
            return 0;
        }

        try {
            return (float) eval("return {$formula};");
        } catch (\Throwable $e) {
            return 0;
        }
    }
}