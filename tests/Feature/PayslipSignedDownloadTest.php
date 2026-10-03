<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\PayPeriod;
use App\Models\Payslip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PayslipSignedDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_payslip_download_returns_stored_file(): void
    {
        Storage::fake('public');

        $employee = Employee::factory()->create();
        $payPeriod = PayPeriod::factory()->create([
            'status' => 'closed',
            'month' => 9,
            'year' => 2026,
        ]);

        $path = 'bulletins/bulletin_test.pdf';
        Storage::disk('public')->put($path, 'fake-pdf-content');

        $payslip = Payslip::create([
            'generation_date' => now()->toDateString(),
            'gross_salary' => 1000,
            'total_earnings' => 1100,
            'total_deductions' => 100,
            'net_salary' => 1000,
            'status' => 'validated',
            'employee_id' => $employee->id,
            'pay_period_id' => $payPeriod->id,
            'file_path' => $path,
        ]);

        $url = URL::temporarySignedRoute('payslip.download', now()->addMinutes(30), [
            'payslip' => $payslip->id,
        ]);

        $response = $this->get($url);

        $response->assertOk();
        $response->assertHeader('content-disposition');
    }
}
