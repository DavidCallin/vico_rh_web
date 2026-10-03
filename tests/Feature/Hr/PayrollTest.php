<?php

namespace Tests\Feature\Hr;

use App\Models\Absence;
use App\Models\Contract;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\PayPeriod;
use App\Payroll\PayrollCodes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\Mime\Email;
use Tests\Concerns\CreatesEmployees;
use Tests\TestCase;

class PayrollTest extends TestCase
{
    use CreatesEmployees;
    use RefreshDatabase;

    public function test_running_a_period_creates_a_locked_its_line_and_delivers_it(): void
    {
        $hr = $this->hrUser();
        $employee = $this->employee([
            'email' => 'paie@paie.test',
            'status' => 'active',
        ]);
        $user = $this->userFor($employee);
        Contract::factory()->create([
            'employee_id' => $employee->id,
            'contract_type' => 'cdi',
            'start_date' => '2026-01-01',
            'end_date' => null,
            'base_salary' => 350000,
        ]);
        $period = PayPeriod::factory()->create([
            'month' => 4,
            'year' => 2026,
            'opening_date' => '2026-04-01',
        ]);

        $this->actingAs($user)->get(route('pay-periods.index'))->assertForbidden();

        $this->actingAs($hr)
            ->post(route('pay-periods.run', $period))
            ->assertRedirect(route('pay-periods.index'));

        $payslip = $employee->payslips()->with('lines.payrollItem')->first();
        $this->assertNotNull($payslip);
        $this->assertSame('350000.00', $payslip->gross_salary);
        $this->assertSame('307000.00', $payslip->net_salary);
        $this->assertNotNull($payslip->delivered_at);

        $its = $payslip->lines->first(fn ($line) => $line->payrollItem->code === PayrollCodes::ITS);
        $this->assertNotNull($its);
        $this->assertTrue($its->is_locked);
        $this->assertSame('43000.00', $its->amount);

        $this->assertSame('Bulletin de paie 04/2026', $user->notifications()->first()->data['title']);
        $transport = Mail::getSymfonyTransport();
        $this->assertInstanceOf(ArrayTransport::class, $transport);
        $this->assertTrue($transport->messages()->contains(function ($sent): bool {
            $message = $sent->getOriginalMessage();

            return $message instanceof Email
                && $message->getSubject() === 'Bulletin de paie 04/2026';
        }));

        $this->actingAs($user)
            ->get(route('payslips.show', $payslip))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('payslips/show')
                ->where('payslip.lines', fn ($lines) => collect($lines)->contains(
                    fn ($line) => $line['code'] === PayrollCodes::ITS && $line['is_locked'] === true,
                )));

        $this->actingAs($hr)
            ->from(route('payslips.show', $payslip))
            ->patch(route('payslip-lines.update', [$payslip, $its]), ['amount' => 1])
            ->assertInvalid(['amount']);

        $this->assertSame('43000.00', $its->refresh()->amount);

        $other = $this->userFor($this->employee(['email' => 'autre@paie.test']));
        $this->actingAs($other)->get(route('payslips.show', $payslip))->assertForbidden();
    }

    public function test_unpaid_leave_reduces_the_taxable_salary_before_its(): void
    {
        $hr = $this->hrUser();
        $employee = Employee::factory()->create(['status' => 'active']);
        Contract::factory()->create([
            'employee_id' => $employee->id,
            'start_date' => '2026-01-01',
            'end_date' => null,
            'base_salary' => 300000,
        ]);
        Leave::factory()->create([
            'employee_id' => $employee->id,
            'leave_type' => 'unpaid',
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-03',
            'days_count' => 3,
            'status' => 'approved',
        ]);
        Absence::factory()->create([
            'employee_id' => $employee->id,
            'type' => 'medical',
            'start_date' => '2026-04-08',
            'end_date' => '2026-04-08',
            'status' => 'approved',
        ]);
        $period = PayPeriod::factory()->create([
            'month' => 4,
            'year' => 2026,
            'opening_date' => '2026-04-01',
        ]);

        $this->actingAs($hr)->post(route('pay-periods.run', $period));

        $payslip = $employee->payslips()->first();
        $this->assertSame('270000.00', $payslip->gross_salary);
        $this->assertSame('300000.00', $payslip->total_earnings);
        $this->assertSame('242200.00', $payslip->net_salary);
    }
}
