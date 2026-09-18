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

        // ✅ NOUVEAU : Récupérer le template par défaut
        $template = PayslipTemplate::where('is_default', true)->first() 
                    ?? PayslipTemplate::first();

        // ✅ NOUVEAU : Récupérer les rubriques selon le template
        if ($template && $template->payrollItems()->exists()) {
            // Utiliser les rubriques spécifiques au template (avec pivot isActive)
            $payrollItems = $template->payrollItems()
                ->wherePivot('isActive', true)
                ->orderBy('payslip_template_items.displayOrder', 'asc')
                ->get();
        } else {
            // Fallback : utiliser toutes les rubriques actives
            $payrollItems = PayrollItem::where('isActive', true)
                ->orderBy('displayOrder', 'asc')
                ->get();
        }

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
                // ✅ NOUVEAU : Calcul du brut imposable
                if ($item->taxStatus === 'taxable') {
                    $taxableGross += $amount;
                }
            } else {
                $totalDeductions += $amount;
            }
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

    private function calculateAmount(PayrollItem $item, float $baseSalary): float
    {
        $value = $item->amount_or_rate ?? $item->defaultValue ?? 0;

        return match ($item->calculation) {
            'fixed' => (float) $value,
            'percentage' => $baseSalary * ((float) $value / 100),
            'formula' => $this->evaluateFormula($item->amount_or_rate, $baseSalary, $item->defaultValue),
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
}