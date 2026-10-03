<?php

namespace App\Http\Controllers;

use App\Models\Absence;
use App\Models\Contract;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Payslip;
use App\Models\User;
use App\Services\ContractReminderService;
use App\Support\HrLabels;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ContractReminderService $reminders): Response
    {
        $reminders->dispatchDue();

        /** @var User $user */
        $user = $request->user();
        $isHr = $user->hasRole('rh');
        $employeeId = $user->employee_id;

        $leaveQuery = Leave::query();
        $absenceQuery = Absence::query();
        $payslipQuery = Payslip::query()->with(['payPeriod', 'employee'])->latest('id');
        $contractQuery = Contract::query()->with('employee')->whereNotNull('end_date');

        if (! $isHr) {
            $leaveQuery->where('employee_id', $employeeId ?? 0);
            $absenceQuery->where('employee_id', $employeeId ?? 0);
            $payslipQuery->where('employee_id', $employeeId ?? 0);
            $contractQuery->where('employee_id', $employeeId ?? 0);
        }

        $horizon = now()->addDays((int) config('payroll.contract_reminder_days'))->toDateString();
        $today = now()->startOfDay();

        return Inertia::render('dashboard', [
            'isHr' => $isHr,
            'pendingLeaves' => (clone $leaveQuery)->where('status', 'pending')->count(),
            'pendingAbsences' => (clone $absenceQuery)->where('status', 'pending')->count(),
            'payslipCount' => (clone $payslipQuery)->count(),
            'expiringContracts' => $contractQuery
                ->whereDate('end_date', '<=', $horizon)
                ->get()
                ->sortBy(function (Contract $contract) use ($today): int {
                    $end = $contract->end_date?->copy()->startOfDay() ?? $today;
                    if ($end->greaterThanOrEqualTo($today)) {
                        return $end->getTimestamp();
                    }

                    return 4_000_000_000 - $end->getTimestamp();
                })
                ->take(8)
                ->values()
                ->map(fn (Contract $contract): array => [
                    'id' => $contract->id,
                    'employee_name' => $contract->employee instanceof Employee
                        ? $contract->employee->full_name
                        : '',
                    'contract_type_label' => HrLabels::contractType($contract->contract_type),
                    'end_date' => $contract->end_date?->toDateString(),
                    'expired' => $contract->end_date?->copy()->startOfDay()->lt(now()->startOfDay()) ?? false,
                ]),
            'recentPayslips' => $payslipQuery->limit(5)->get()->map(fn (Payslip $payslip): array => [
                'id' => $payslip->id,
                'period' => sprintf('%02d/%d', $payslip->payPeriod->month, $payslip->payPeriod->year),
                'net_salary' => $payslip->net_salary,
                'employee_name' => $isHr && $payslip->employee instanceof Employee
                    ? $payslip->employee->full_name
                    : null,
            ]),
        ]);
    }
}
