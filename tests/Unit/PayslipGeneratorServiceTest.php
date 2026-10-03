<?php

namespace Tests\Unit;

use App\Models\Contract;
use App\Models\Employee;
use App\Models\PayPeriod;
use App\Services\PayslipGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayslipGeneratorServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_adds_its_line_using_benin_progressive_brackets(): void
    {
        $employee = Employee::factory()->create();
        $period = PayPeriod::factory()->create([
            'opening_date' => now()->startOfMonth(),
            'closing_date' => now()->endOfMonth(),
        ]);

        Contract::factory()->create([
            'employee_id' => $employee->id,
            'start_date' => now()->subMonths(3)->toDateString(),
            'end_date' => null,
            'base_salary' => 700000,
        ]);

        $service = app(PayslipGeneratorService::class);
        $payslip = $service->generate($employee, $period)->load('lines.payrollItem');

        $itsLine = $payslip->lines->first(fn ($line) => $line->payrollItem?->code === 'ITS');

        // 0-60k:0% + 60k-150k:10% + 150k-250k:15% + 250k-500k:19% + 500k-700k:30%
        // => 0 + 9 000 + 15 000 + 47 500 + 60 000 = 131 500
        $this->assertNotNull($itsLine);
        $this->assertSame(131500.0, (float) $itsLine->amount);
        $this->assertSame(131500.0, (float) $payslip->total_deductions);
        $this->assertSame(568500.0, (float) $payslip->net_salary);
    }

    public function test_it_adds_base_salary_as_gain_line_from_employee_contract(): void
    {
        $employee = Employee::factory()->create();
        $period = PayPeriod::factory()->create([
            'opening_date' => now()->startOfMonth(),
            'closing_date' => now()->endOfMonth(),
        ]);

        Contract::factory()->create([
            'employee_id' => $employee->id,
            'start_date' => now()->subMonths(3)->toDateString(),
            'end_date' => null,
            'base_salary' => 50000,
        ]);

        $service = app(PayslipGeneratorService::class);
        $payslip = $service->generate($employee, $period)->load('lines.payrollItem');

        $baseSalaryLine = $payslip->lines->first(fn ($line) => $line->payrollItem?->code === 'BASE_SALARY');

        $this->assertNotNull($baseSalaryLine);
        $this->assertSame(50000.0, (float) $baseSalaryLine->amount);
        $this->assertSame(50000.0, (float) $payslip->total_earnings);
        $this->assertSame(50000.0, (float) $payslip->gross_salary);
    }
}
