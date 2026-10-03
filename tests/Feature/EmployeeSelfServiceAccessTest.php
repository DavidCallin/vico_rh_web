<?php

namespace Tests\Feature;

use App\Models\Absence;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\PayPeriod;
use App\Models\Payslip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmployeeSelfServiceAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_cannot_view_other_employee_leave_absence_or_payslip(): void
    {
        $role = Role::create(['name' => 'employee']);
        $permissions = ['leaves.view', 'absences.view', 'payslips.view'];

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);
            $role->givePermissionTo($permission);
        }

        $viewerEmployee = Employee::factory()->create();
        $otherEmployee = Employee::factory()->create();

        $viewer = User::factory()->create(['employee_id' => $viewerEmployee->id]);
        $viewer->assignRole($role);

        $leave = Leave::factory()->create([
            'employee_id' => $otherEmployee->id,
            'status' => 'pending',
        ]);

        $absence = Absence::create([
            'type' => 'other',
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'status' => 'pending',
            'employee_id' => $otherEmployee->id,
        ]);

        $period = PayPeriod::factory()->create();
        $payslip = Payslip::create([
            'employee_id' => $otherEmployee->id,
            'pay_period_id' => $period->id,
            'generation_date' => now()->toDateString(),
            'gross_salary' => 100000,
            'total_earnings' => 100000,
            'total_deductions' => 0,
            'net_salary' => 100000,
            'status' => 'validated',
        ]);

        $this->actingAs($viewer, 'sanctum');

        $this->getJson('/api/leaves/' . $leave->id)->assertForbidden();
        $this->getJson('/api/absences/' . $absence->id)->assertForbidden();
        $this->getJson('/api/payslips/' . $payslip->id)->assertForbidden();
    }
}
