<?php

namespace App\Payroll;

use App\Models\Employee;
use App\Models\PayPeriod;
use App\Models\User;
use App\Services\HrNotifier;
use Illuminate\Support\Facades\DB;

final class PayPeriodRunner
{
    public function __construct(
        private PayslipBuilder $builder,
        private HrNotifier $notifier,
    ) {}

    public function run(PayPeriod $period, User $actor): int
    {
        $employees = Employee::query()
            ->whereIn('status', ['active', 'trial'])
            ->with(['user', 'contracts'])
            ->orderBy('last_name')
            ->get();

        $payslipIds = [];

        DB::transaction(function () use ($period, $actor, $employees, &$payslipIds): void {
            foreach ($employees as $employee) {
                $payslip = $this->builder->build($period, $employee);

                if ($payslip) {
                    $payslipIds[] = $payslip->id;
                }
            }

            if ($payslipIds === []) {
                return;
            }

            $period->update([
                'status' => 'closed',
                'closing_date' => now()->toDateString(),
                'closure_type' => 'normal',
                'closed_by' => $actor->id,
            ]);
        });

        if ($payslipIds === []) {
            return 0;
        }

        $pending = $period->payslips()
            ->whereIn('id', $payslipIds)
            ->whereNull('delivered_at')
            ->with(['employee.user', 'payPeriod'])
            ->get();

        foreach ($pending as $payslip) {
            $this->notifier->payslipIssued($payslip);
            $payslip->update(['delivered_at' => now()]);
        }

        return count($payslipIds);
    }
}
