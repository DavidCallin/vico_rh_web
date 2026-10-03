<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PayPeriod;
use App\Models\PayrollItem;
use App\Models\Payslip;
use App\Models\PayslipLine;
use App\Models\PayslipTemplate;
use Illuminate\Support\Facades\DB;

class PayslipGeneratorService
{
    /**
     * Barème progressif ITS mensuel (Bénin).
     * Référence métier utilisée dans l'application:
     * 0 - 60 000 => 0%
     * 60 001 - 150 000 => 10%
     * 150 001 - 250 000 => 15%
     * 250 001 - 500 000 => 19%
     * > 500 000 => 30%
     */
    private const ITS_BENIN_MONTHLY_BRACKETS = [
        ['min' => 0, 'max' => 60000, 'rate' => 0.00],
        ['min' => 60000, 'max' => 150000, 'rate' => 0.10],
        ['min' => 150000, 'max' => 250000, 'rate' => 0.15],
        ['min' => 250000, 'max' => 500000, 'rate' => 0.19],
        ['min' => 500000, 'max' => PHP_INT_MAX, 'rate' => 0.30],
    ];
    public function generate(Employee $employee, PayPeriod $period): Payslip
    {
        $existing = Payslip::where('employee_id', $employee->id)
            ->where('pay_period_id', $period->id)
            ->first();

        if ($existing) {
            throw new \Exception('Un bulletin existe déjà pour cet employé sur cette période.');
        }

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
        $taxableGross = 0;
        $lines = [];

        $baseSalaryItem = $this->getOrCreateBaseSalaryItem();
        $lines[] = [
            'payroll_item_id' => $baseSalaryItem->id,
            'amount' => $baseSalary,
            'quantity' => 1,
        ];
        $totalEarnings += $baseSalary;
        $taxableGross += $baseSalary;

        $template = PayslipTemplate::where('is_default', true)->first()
                    ?? PayslipTemplate::first();

        if ($template && $template->payrollItems()->exists()) {
            $payrollItems = $template->payrollItems()
                ->wherePivot('isActive', true)
                ->orderBy('payslip_template_items.displayOrder', 'asc')
                ->get();
        } else {
            $payrollItems = PayrollItem::where('isActive', true)
                ->orderBy('displayOrder', 'asc')
                ->get();
        }

        foreach ($payrollItems as $item) {
            $amount = $this->calculateAmount($item, $baseSalary);
            \Log::info('PAYSLIP_DEBUG', [
                'item' => $item->name,
                'type' => $item->type,
                'calculation' => $item->calculation,
                'amount_or_rate' => $item->amount_or_rate,
                'defaultValue' => $item->defaultValue,
                'computed_amount' => $amount,
            ]);
            if ($amount == 0) continue;
            if ($item->code === 'BASE_SALARY') continue;

            $lines[] = [
                'payroll_item_id' => $item->id,
                'amount' => $amount,
                'quantity' => 1,
            ];

            // ✅ 'gain', pas 'earning' — confirmé par la base de données
            if ($item->type === 'gain') {
                $totalEarnings += $amount;

                if ($item->taxStatus === 'taxable') {
                    $taxableGross += $amount;
                }
            } else {
                $totalDeductions += $amount;
            }
        }

        // 🧾 ITS en dur (Bénin) — calculé sur le brut imposable
        $itsAmount = $this->calculateITS($taxableGross);
        if ($itsAmount > 0) {
            $itsItem = $this->getOrCreateItsItem();

            $lines[] = [
                'payroll_item_id' => $itsItem->id,
                'amount' => $itsAmount,
                'quantity' => 1,
            ];

            $totalDeductions += $itsAmount;
        }

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

            if (\Illuminate\Support\Facades\Schema::hasColumn('payslips', 'taxable_gross')) {
                $payslipData['taxable_gross'] = $taxableGross;
            }

            $payslip = Payslip::create($payslipData);

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
     * 🔎 Récupère la rubrique système du salaire de base ou la crée.
     */
    private function getOrCreateBaseSalaryItem(): PayrollItem
    {
        return PayrollItem::updateOrCreate(
            ['code' => 'BASE_SALARY'],
            [
                'name' => 'Salaire de base',
                'type' => 'gain',
                'taxStatus' => 'taxable',
                'amount_or_rate' => 0,
                'defaultValue' => 0,
                'calculation' => 'fixed',
                'displayOrder' => 0,
                'isActive' => false,
                'description' => 'Rubrique système : salaire de base issu du contrat actif.',
            ]
        );
    }

    /**
     * 🔎 Récupère la rubrique "ITS" ou la crée si elle n'existe pas encore.
     * Elle est marquée isActive = false pour ne JAMAIS être reprise par la
     * boucle générique ci-dessus (elle est ajoutée uniquement en dur ici).
     */
    private function getOrCreateItsItem(): PayrollItem
    {
        return PayrollItem::updateOrCreate(
            ['code' => 'ITS'],
            [
                'name' => 'Impôt sur Traitements et Salaires (ITS)',
                'type' => 'deduction',
                'taxStatus' => 'non_taxable',
                'amount_or_rate' => 0,
                'defaultValue' => 0,
                'calculation' => 'fixed',
                'displayOrder' => 999,
                'isActive' => false, // exclue de la boucle générique
                'description' => 'Rubrique système : ITS calculé automatiquement selon le barème progressif béninois.',
            ]
        );
    }

    private function calculateAmount(PayrollItem $item, float $baseSalary): float
    {
        $value = (float) ($item->amount_or_rate ?? $item->defaultValue ?? 0);

        return match ($item->calculation) {
            'fixed' => $value,
            'percentage' => $baseSalary * ($value / 100),
            'formula' => $this->evaluateFormula((string) $item->amount_or_rate, $baseSalary, $item->defaultValue ? (float) $item->defaultValue : null),
            default => 0,
        };
    }

    private function evaluateFormula(string $formula, float $baseSalary, ?float $defaultValue = null): float
    {
        $formula = str_replace('base', (string) $baseSalary, $formula);

        if ($defaultValue !== null) {
            $formula = str_replace('default', (string) $defaultValue, $formula);
        }

        if (!preg_match('/^[\d\s\+\-\*\/\.\(\)]+$/', $formula)) {
            return 0;
        }

        try {
            return (float) eval("return {$formula};");
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * 🧮 ITS (Impôt sur Traitements et Salaires) — Bénin, barème mensuel en dur.
     * ⚠️ À vérifier/actualiser selon le barème officiel en vigueur (DGI Bénin).
     */
    private function calculateITS(float $taxableGross): float
    {
        if ($taxableGross <= 0) {
            return 0;
        }

        $its = 0;
        foreach (self::ITS_BENIN_MONTHLY_BRACKETS as $bracket) {
            if ($taxableGross > $bracket['min']) {
                $taxableInBracket = min($taxableGross, $bracket['max']) - $bracket['min'];
                $its += $taxableInBracket * $bracket['rate'];
            }
        }

        return round($its, 2);
    }
}
