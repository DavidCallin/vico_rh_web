<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class EmployeeController extends Controller
{
    /**
     * Liste tous les employés (avec pagination)
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Employee::with(['position.department', 'category']);

        // Filtre par statut si fourni
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filtre par département si fourni
        if ($request->has('department_id')) {
            $query->whereHas('position', function ($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        // Recherche par nom ou matricule
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('matricule', 'like', "%{$search}%");
            });
        }

        $employees = $query->orderBy('last_name')->paginate(15);

        return EmployeeResource::collection($employees);
    }

    /**
     * Affiche un employé spécifique
     */
    public function show(Employee $employee): EmployeeResource
    {
        $employee->load(['position.department', 'category', 'contracts', 'leaves']);

        return new EmployeeResource($employee);
    }

    /**
     * Crée un nouvel employé
     */
    public function store(StoreEmployeeRequest $request): EmployeeResource
    {
        $employee = Employee::create($request->validated());

        return new EmployeeResource($employee->load(['position.department', 'category']));
    }

    /**
     * Met à jour un employé existant
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee): EmployeeResource
    {
        $employee->update($request->validated());

        return new EmployeeResource($employee->load(['position.department', 'category']));
    }

    /**
     * Supprime un employé
     */
    public function destroy(Employee $employee): Response
    {
        $employee->delete();

        return response()->noContent();
    }
}
