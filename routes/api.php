<?php

use App\Http\Controllers\Api\AbsenceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\LeaveController;
use App\Http\Controllers\Api\PayPeriodController;
use App\Http\Controllers\Api\PayslipController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Routes publiques pour l'API

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Routes protégées 
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // ===== ROUTES EMPLOYÉS =====
    Route::middleware('can:employees.view')->group(function () {
        Route::get('/employees', [EmployeeController::class, 'index']);
        Route::get('/employees/{employee}', [EmployeeController::class, 'show']);
    });
    
    Route::middleware('can:employees.create')->group(function () {
        Route::post('/employees', [EmployeeController::class, 'store']);
    });
    
    Route::middleware('can:employees.update')->group(function () {
        Route::put('/employees/{employee}', [EmployeeController::class, 'update']);
        Route::patch('/employees/{employee}', [EmployeeController::class, 'update']);
    });
    
    Route::middleware('can:employees.delete')->group(function () {
        Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy']);
    });

        // ===== ROUTES CONGÉS =====
    Route::middleware('can:leaves.view')->group(function () {
        Route::get('/leaves', [LeaveController::class, 'index']);
        Route::get('/leaves/{leave}', [LeaveController::class, 'show']);
    });
    
    Route::middleware('can:leaves.create')->group(function () {
        Route::post('/leaves', [LeaveController::class, 'store']);
        // Un employé peut annuler sa propre demande en attente
        Route::delete('/leaves/{leave}', [LeaveController::class, 'destroy']); 
    });
    
    Route::middleware('can:leaves.approve')->group(function () {
        // On utilise POST ou PATCH pour le changement de statut
        Route::patch('/leaves/{leave}/status', [LeaveController::class, 'updateStatus']);
    });

    // ===== ROUTES ABSENCES =====
    Route::middleware('can:absences.view')->group(function () {
        Route::get('/absences', [AbsenceController::class, 'index']);
        Route::get('/absences/{absence}', [AbsenceController::class, 'show']);
    });
    
    Route::middleware('can:absences.create')->group(function () {
        Route::post('/absences', [AbsenceController::class, 'store']);
        Route::delete('/absences/{absence}', [AbsenceController::class, 'destroy']);
    });
    
    Route::middleware('can:absences.approve')->group(function () {
        Route::patch('/absences/{absence}/status', [AbsenceController::class, 'updateStatus']);
    });

    // ===== ROUTES CONTRATS =====
    Route::middleware('can:contracts.view')->group(function () {
        Route::get('/contracts', [ContractController::class, 'index']);
        Route::get('/contracts/{contract}', [ContractController::class, 'show']);
    });
    
    Route::middleware('can:contracts.create')->group(function () {
        Route::post('/contracts', [ContractController::class, 'store']);
    });
    
    Route::middleware('can:contracts.update')->group(function () {
        Route::put('/contracts/{contract}', [ContractController::class, 'update']);
        Route::patch('/contracts/{contract}', [ContractController::class, 'update']);
        Route::delete('/contracts/{contract}', [ContractController::class, 'destroy']);
    });

    // ===== ROUTES PÉRIODES DE PAIE =====
    Route::middleware('can:payroll.view')->group(function () {
        Route::get('/pay-periods', [PayPeriodController::class, 'index']);
        Route::get('/pay-periods/{payPeriod}', [PayPeriodController::class, 'show']);
    });
    
    Route::middleware('can:payroll.manage')->group(function () {
        Route::post('/pay-periods/open', [PayPeriodController::class, 'open']);
        Route::post('/pay-periods/{payPeriod}/close', [PayPeriodController::class, 'close']);
    });

        // ===== ROUTES BULLETINS DE PAIE =====
    Route::middleware('can:payroll.view')->group(function () {
        Route::get('/payslips', [PayslipController::class, 'index']);
        Route::get('/payslips/{payslip}', [PayslipController::class, 'show']);
        Route::get('/payslips/{payslip}/download', [PayslipController::class, 'download']);
    });
    
    Route::middleware('can:payroll.manage')->group(function () {
        Route::post('/payslips/generate', [PayslipController::class, 'generate']);
        Route::post('/payslips/{payslip}/validate', [PayslipController::class, 'validate']);
        Route::post('/payslips/{payslip}/pay', [PayslipController::class, 'markAsPaid']);
    });

    // ===== ROUTES DASHBOARD =====
    // On utilise la permission 'employees.view' comme garde-fou pour l'accès au dashboard
    Route::middleware('can:employees.view')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index']);
    });

        // ===== ROUTES POSTES =====
    Route::get('/positions', function () {
        $positions = \App\Models\Position::with('department')->get();
        return response()->json([
            'data' => $positions->map(fn($p) => [
                'id' => $p->id,
                'title' => $p->title,
                'department_id' => $p->department_id,
                'department' => $p->department ? [
                    'id' => $p->department->id,
                    'name' => $p->department->name,
                ] : null,
            ]),
        ]);
    });

    // ===== ROUTES CATEGORIES =====
    Route::get('/categories', function () {
        $categories = \App\Models\Category::all();
        return response()->json([
            'data' => $categories->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'code' => $c->code,
            ]),
        ]);
    });

        // ===== ROUTES TEMPLATES DE CONTRATS =====
    Route::get('/contract-templates', function () {
        $templates = \App\Models\ContractTemplate::all();
        return response()->json(['data' => $templates]);
    });

    Route::get('/contract-templates/{type}', function ($type) {
        $template = \App\Models\ContractTemplate::where('type', $type)->firstOrFail();
        return response()->json(['data' => $template]);
    });

    Route::put('/contract-templates/{type}', function (\Illuminate\Http\Request $request, $type) {
        $template = \App\Models\ContractTemplate::where('type', $type)->firstOrFail();
        $template->update(['content' => $request->input('content')]);
        return response()->json(['data' => $template]);
    });

    // ===== ROUTES TEMPLATES DE BULLETINS =====
    Route::get('/payslip-templates', function () {
        $templates = \App\Models\PayslipTemplate::all();
        return response()->json(['data' => $templates]);
    });

    Route::get('/payslip-templates/{id}', function ($id) {
        $template = \App\Models\PayslipTemplate::findOrFail($id);
        return response()->json(['data' => $template]);
    });

    Route::post('/payslip-templates', function (\Illuminate\Http\Request $request) {
        $template = \App\Models\PayslipTemplate::create($request->all());
        return response()->json(['data' => $template], 201);
    });

    Route::put('/payslip-templates/{id}', function (\Illuminate\Http\Request $request, $id) {
        $template = \App\Models\PayslipTemplate::findOrFail($id);
        $template->update($request->all());
        return response()->json(['data' => $template]);
    });

    Route::delete('/payslip-templates/{id}', function ($id) {
        \App\Models\PayslipTemplate::destroy($id);
        return response()->json(['message' => 'Template supprimé']);
    });
});
