<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmployeeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_register_then_login_and_records_last_login(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Awa Bio',
            'email' => 'awa@paie.test',
            'login' => 'awa',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated();

        $this->postJson('/api/login', [
            'email' => 'awa@paie.test',
            'password' => 'password123',
        ])->assertOk();

        $this->assertNotNull(User::query()->where('email', 'awa@paie.test')->first()->last_login_at);
    }

    public function test_employee_crud_returns_the_department_and_rejects_unknown_status(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $position = Position::factory()->create();
        $category = Category::factory()->create();

        $this->postJson('/api/employees', [
            'matricule' => 'EMP-API-1',
            'first_name' => 'Sena',
            'last_name' => 'Kiki',
            'email' => 'sena@paie.test',
            'gender' => 'F',
            'hire_date' => '2024-02-01',
            'status' => 'on_leave',
            'position_id' => $position->id,
            'category_id' => $category->id,
        ])->assertUnprocessable();

        $created = $this->postJson('/api/employees', [
            'matricule' => 'EMP-API-1',
            'first_name' => 'Sena',
            'last_name' => 'Kiki',
            'email' => 'sena@paie.test',
            'gender' => 'F',
            'hire_date' => '2024-02-01',
            'status' => 'active',
            'position_id' => $position->id,
            'category_id' => $category->id,
        ])->assertCreated();

        $created->assertJsonPath('data.department.name', $position->department->name);

        $resigned = Employee::factory()->create(['status' => 'resigned']);
        $this->assertTrue(Employee::active()->where('matricule', 'EMP-API-1')->exists());
        $this->assertFalse(Employee::active()->whereKey($resigned->id)->exists());
    }
}
