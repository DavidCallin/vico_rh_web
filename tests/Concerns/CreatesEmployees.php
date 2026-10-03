<?php

namespace Tests\Concerns;

use App\Models\Employee;
use App\Models\User;
use Spatie\Permission\Models\Role;

trait CreatesEmployees
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function employee(array $attributes = []): Employee
    {
        return Employee::factory()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function userFor(Employee $employee, array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'employee_id' => $employee->id,
            'name' => $employee->full_name,
            'email' => $employee->email,
        ], $attributes));
    }

    protected function hrUser(): User
    {
        $employee = $this->employee([
            'email' => 'rh-'.uniqid().'@paie.test',
        ]);
        $user = $this->userFor($employee);
        Role::findOrCreate('rh', 'web');
        $user->assignRole('rh');

        return $user;
    }
}
