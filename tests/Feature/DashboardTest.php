<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\PayPeriod;
use App\Models\Position;
use App\Models\User;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_api_dashboard_returns_consistent_statistics_and_open_period_priority(): void
    {
        $this->withoutMiddleware(Authenticate::class);
        $this->withoutMiddleware(Authorize::class);

        $department = Department::factory()->create(['name' => 'Informatique']);
        $position = Position::factory()->create(['department_id' => $department->id]);

        Employee::factory()->count(2)->create([
            'status' => 'active',
            'position_id' => $position->id,
        ]);
        Employee::factory()->create(['status' => 'trial']);
        Employee::factory()->create(['status' => 'resigned']);

        PayPeriod::factory()->create([
            'month' => 1,
            'year' => now()->year,
            'status' => 'closed',
        ]);

        $openPeriod = PayPeriod::factory()->create([
            'month' => 2,
            'year' => now()->year,
            'status' => 'open',
        ]);

        $response = $this->getJson('/api/dashboard');

        $response
            ->assertOk()
            ->assertJsonPath('kpi.total_employees', 4)
            ->assertJsonPath('kpi.active_employees', 2)
            ->assertJsonPath('kpi.trial_employees', 1)
            ->assertJsonPath('kpi.on_leave_employees', 1)
            ->assertJsonPath('kpi.employees_inactive_or_other', 0)
            ->assertJsonPath('current_period.status', 'open');

        $this->assertSame(
            \Carbon\Carbon::create($openPeriod->year, $openPeriod->month, 1)->translatedFormat('F Y'),
            $response->json('current_period.label')
        );
    }
}
