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
        $taxableGross = 0; // ✅ NOUVEAU : Brut imposable
        $lines = [];

        // Récupérer toutes les rubriques actives
        $payrollItems = PayrollItem::where('isActive', true)
            ->orderBy('displayOrder', 'asc')
            ->get();

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
                
                // ✅ NOUVEAU : Calculer le brut imposable
                if ($item->taxStatus === 'taxable') {
                    $taxableGross += $amount;
                }
            } else {
                $totalDeductions += $amount;
            }
        }

        // Créer le bulletin dans une transaction (tout ou rien)
        return DB::transaction(function () use ($employee, $period, $baseSalary, $totalEarnings, $totalDeductions, $taxableGross, $lines) {
            $payslipData = [
                'employee_id' => $employee->id,
                'pay_period_id' => $period->id,
                'generation_date' => now(),
                'gross_salary' => $baseSalary,
                'total_earnings' => $totalEarnings,
                'total_deductions' => $totalDeductions,
                'net_salary' => $totalEarnings - $totalDeductions,
                'status' => 'draft',
            ];

            // ✅ NOUVEAU : Ajouter le brut imposable si la colonne existe
            if (\Illuminate\Support\Facades\Schema::hasColumn('payslips', 'taxable_gross')) {
                $payslipData['taxable_gross'] = $taxableGross;
            }

            $payslip = Payslip::create($payslipData);

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
     * Utilise defaultValue comme valeur de secours si amount_or_rate est null ou 0
     */
    private function calculateAmount(PayrollItem $item, float $baseSalary): float
    {
        // ✅ NOUVEAU : Utiliser defaultValue comme fallback
        $value = $item->amount_or_rate ?? $item->defaultValue ?? 0;

        return match ($item->calculation) {
            'fixed' => (float) $value,
            'percentage' => $baseSalary * ((float) $value / 100),
            'formula' => $this->evaluateFormula($item->amount_or_rate, $baseSalary, $item->defaultValue),
            default => 0,
        };
    }

    /**
     * Évalue une formule simple (ex: "base * 0.1 + default")
     * Supporte maintenant la variable 'default' pour utiliser defaultValue
     */
    private function evaluateFormula(string $formula, float $baseSalary, ?float $defaultValue = null): float
    {
        // Remplace les variables par leurs valeurs
        $formula = str_replace('base', (string) $baseSalary, $formula);
        
        // ✅ NOUVEAU : Remplacer 'default' par la valeur par défaut si elle existe
        if ($defaultValue !== null) {
            $formula = str_replace('default', (string) $defaultValue, $formula);
        }

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