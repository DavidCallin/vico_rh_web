<?php

namespace App\Http\Resources;

use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $employee = $this->employee();

        return [
            'id' => $employee->id,
            'matricule' => $employee->matricule,
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'full_name' => $employee->full_name,
            'email' => $employee->email,
            'phone' => $employee->phone,
            'birth_date' => $employee->birth_date?->format('Y-m-d'),
            'gender' => $employee->gender,
            'address' => $employee->address,
            'hire_date' => $employee->hire_date->format('Y-m-d'),
            'seniority' => $employee->seniority,
            'site' => $employee->site,
            'status' => $employee->status,
            'position' => new PositionResource($this->whenLoaded('position')),
            'department' => $this->when(
                $this->loadedDepartment($employee) !== null,
                fn () => new DepartmentResource($this->loadedDepartment($employee)),
            ),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'created_at' => $employee->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $employee->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    private function employee(): Employee
    {
        $employee = $this->resource;

        if (! $employee instanceof Employee) {
            abort(500, 'Ressource salarié invalide.');
        }

        return $employee;
    }

    private function loadedDepartment(Employee $employee): ?Department
    {
        if ($employee->relationLoaded('department')) {
            return $employee->department;
        }

        if ($employee->relationLoaded('position') && $employee->position?->relationLoaded('department')) {
            return $employee->position->department;
        }

        return null;
    }
}
