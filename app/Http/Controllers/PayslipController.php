<?php

namespace App\Http\Controllers;

use App\Models\Payslip;
use App\Models\User;
use App\Payroll\PayrollCodes;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PayslipController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $isHr = $user->hasRole('rh');

        $payslips = Payslip::query()
            ->with(['employee', 'payPeriod'])
            ->when(! $isHr, fn ($query) => $query->where('employee_id', $user->employee_id ?? 0))
            ->latest('id')
            ->limit(100)
            ->get()
            ->map(fn (Payslip $payslip): array => [
                'id' => $payslip->id,
                'employee_name' => $payslip->employee->full_name,
                'period' => sprintf('%02d/%d', $payslip->payPeriod->month, $payslip->payPeriod->year),
                'gross_salary' => $payslip->gross_salary,
                'net_salary' => $payslip->net_salary,
                'status' => $payslip->status,
            ]);

        return Inertia::render('payslips/index', [
            'payslips' => $payslips,
            'isHr' => $isHr,
        ]);
    }

    public function show(Request $request, Payslip $payslip): Response
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->hasRole('rh') || $user->employee_id === $payslip->employee_id, 403);

        $payslip->load(['employee.position', 'payPeriod', 'lines.payrollItem']);

        return Inertia::render('payslips/show', [
            'payslip' => [
                'id' => $payslip->id,
                'employee_name' => $payslip->employee->full_name,
                'matricule' => $payslip->employee->matricule,
                'position' => $payslip->employee->position?->title,
                'period' => sprintf('%02d/%d', $payslip->payPeriod->month, $payslip->payPeriod->year),
                'generation_date' => $payslip->generation_date->toDateString(),
                'gross_salary' => $payslip->gross_salary,
                'total_earnings' => $payslip->total_earnings,
                'total_deductions' => $payslip->total_deductions,
                'net_salary' => $payslip->net_salary,
                'lines' => $payslip->lines->map(fn ($line): array => [
                    'id' => $line->id,
                    'code' => $line->payrollItem->code,
                    'name' => $line->payrollItem->name,
                    'type' => $line->payrollItem->type,
                    'amount' => $line->amount,
                    'quantity' => $line->quantity,
                    'is_locked' => $line->is_locked || $line->payrollItem->code === PayrollCodes::ITS,
                ]),
            ],
            'isHr' => $user->hasRole('rh'),
        ]);
    }
}
