<?php

namespace App\Payroll;

use App\Models\Contract;
use App\Models\Employee;
use App\Models\PayPeriod;
use App\Models\PayrollItem;
use App\Models\Payslip;
use App\Models\PayslipLine;
use App\Support\DateSpan;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Bulletin mensuel : salaire du contrat au prorata des jours couverts,
 * retenue des congés sans solde et absences non justifiées approuvés,
 * puis ITS verrouillé. Le prorata est une règle de cette application
 * (jours calendaires du mois), pas un taux légal.
 */
final class PayslipBuilder
{
    public function __construct(private BeninItsCalculator $its) {}

    public function build(PayPeriod $period, Employee $employee): ?Payslip
    {
        $existing = Payslip::query()
            ->where('employee_id', $employee->id)
            ->where('pay_period_id', $period->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $periodStart = CarbonImmutable::create($period->year, $period->month, 1)->startOfDay();
        $periodEnd = $periodStart->endOfMonth()->startOfDay();
        $daysInMonth = $periodStart->daysInMonth;

        $contract = $this->contractFor($employee, $periodStart, $periodEnd);

        if (! $contract) {
            return null;
        }

        $contractEnd = $contract->end_date
            ? CarbonImmutable::parse($contract->end_date)
            : $periodEnd;
        $coveredDays = DateSpan::overlapDays(
            CarbonImmutable::parse($contract->start_date),
            $contractEnd,
            $periodStart,
            $periodEnd,
        );
        $monthlySalary = (int) round((float) $contract->base_salary);
        $base = (int) round($monthlySalary * $coveredDays / $daysInMonth);
        $absenceDays = $this->unpaidDays($employee, $periodStart, $periodEnd);
        $absenceAmount = (int) round($monthlySalary * $absenceDays / $daysInMonth);
        $absenceAmount = min($absenceAmount, $base);
        $taxable = $base - $absenceAmount;
        $tax = $this->its->calculate($taxable, (int) $period->month);
        $itsAmount = $tax['its'];
        $net = $taxable - $itsAmount;

        return DB::transaction(function () use (
            $employee,
            $period,
            $base,
            $absenceDays,
            $absenceAmount,
            $taxable,
            $itsAmount,
            $net,
        ): Payslip {
            $payslip = Payslip::query()->create([
                'generation_date' => now()->toDateString(),
                'gross_salary' => $this->money($taxable),
                'total_earnings' => $this->money($base),
                'total_deductions' => $this->money($absenceAmount + $itsAmount),
                'net_salary' => $this->money($net),
                'status' => 'validated',
                'employee_id' => $employee->id,
                'pay_period_id' => $period->id,
            ]);

            $this->line($payslip, PayrollCodes::BASE, 'Salaire de base', 'gain', $base, 1, false);

            if ($absenceAmount > 0) {
                $this->line(
                    $payslip,
                    PayrollCodes::ABSENCE,
                    'Retenue congé sans solde / absence non justifiée',
                    'deduction',
                    $absenceAmount,
                    $absenceDays,
                    false,
                );
            }

            $this->line(
                $payslip,
                PayrollCodes::ITS,
                'ITS (impôt sur les traitements et salaires)',
                'deduction',
                $itsAmount,
                1,
                true,
            );

            return $payslip->load('lines.payrollItem');
        });
    }

    private function contractFor(Employee $employee, CarbonImmutable $periodStart, CarbonImmutable $periodEnd): ?Contract
    {
        return $employee->contracts()
            ->whereDate('start_date', '<=', $periodEnd->toDateString())
            ->where(function ($query) use ($periodStart): void {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $periodStart->toDateString());
            })
            ->orderByDesc('start_date')
            ->first();
    }

    private function unpaidDays(Employee $employee, CarbonImmutable $periodStart, CarbonImmutable $periodEnd): int
    {
        $days = 0;

        $leaves = $employee->leaves()
            ->where('status', 'approved')
            ->where('leave_type', 'unpaid')
            ->whereDate('start_date', '<=', $periodEnd->toDateString())
            ->whereDate('end_date', '>=', $periodStart->toDateString())
            ->get();

        foreach ($leaves as $leave) {
            $days += DateSpan::overlapDays($leave->start_date, $leave->end_date, $periodStart, $periodEnd);
        }

        $absences = $employee->absences()
            ->where('status', 'approved')
            ->where('type', 'unjustified')
            ->whereDate('start_date', '<=', $periodEnd->toDateString())
            ->whereDate('end_date', '>=', $periodStart->toDateString())
            ->get();

        foreach ($absences as $absence) {
            $days += DateSpan::overlapDays($absence->start_date, $absence->end_date, $periodStart, $periodEnd);
        }

        return $days;
    }

    private function line(
        Payslip $payslip,
        string $code,
        string $name,
        string $type,
        int $amount,
        int|float $quantity,
        bool $locked,
    ): PayslipLine {
        $item = PayrollItem::query()->firstOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'type' => $type,
                'calculation' => $code === PayrollCodes::ITS ? 'formula' : 'fixed',
                'is_active' => true,
            ],
        );

        return $payslip->lines()->create([
            'payroll_item_id' => $item->id,
            'amount' => $this->money($amount),
            'quantity' => $quantity,
            'is_locked' => $locked,
        ]);
    }

    private function money(int $amount): string
    {
        return number_format($amount, 2, '.', '');
    }
}
