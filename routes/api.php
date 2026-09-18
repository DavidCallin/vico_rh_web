<?php

use App\Http\Controllers\Api\AbsenceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\LeaveController;
use App\Http\Controllers\Api\PayPeriodController;
use App\Http\Controllers\Api\PayrollItemController;
use App\Http\Controllers\Api\PayslipController;
use App\Http\Controllers\Api\PayslipTemplateController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ==========================================
// 1. ROUTES PUBLIQUES
// ==========================================
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// ==========================================
// 2. ROUTES PROTÉGÉES (Authentification requise)
// ==========================================
Route::middleware('auth:sanctum')->group(function () {
    
    // --- Auth ---
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // ==========================================
    // 3. ROUTES MÉTIER
    // ==========================================

    // ===== A. EMPLOYÉS =====
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

    // ===== B. CONGÉS =====
    Route::middleware('can:leaves.view')->group(function () {
        Route::get('/leaves', [LeaveController::class, 'index']);
        Route::get('/leaves/{leave}', [LeaveController::class, 'show']);
    });
    Route::middleware('can:leaves.create')->group(function () {
        Route::post('/leaves', [LeaveController::class, 'store']);
        Route::delete('/leaves/{leave}', [LeaveController::class, 'destroy']); 
    });
    Route::middleware('can:leaves.approve')->group(function () {
        Route::patch('/leaves/{leave}/status', [LeaveController::class, 'updateStatus']);
    });

    // ===== C. ABSENCES =====
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

    // ===== D. CONTRATS =====
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
    });
    Route::middleware('can:contracts.delete')->group(function () {
        Route::delete('/contracts/{contract}', [ContractController::class, 'destroy']);
    });

    // ===== E. PÉRIODES DE PAIE =====
    // ⚠️ IMPORTANT : La route /status DOIT être avant /{payPeriod}
    Route::middleware('can:payroll.view')->group(function () {
        Route::get('/pay-periods/status', function () {
        // ✅ Cherche explicitement la période ouverte (il ne devrait y en avoir qu'une seule)
        $openPeriod = \App\Models\PayPeriod::where('status', 'open')->first();
        
        // ✅ Sécurité : s'il y en a plusieurs, on ne garde que la plus récente
        if (!$openPeriod) {
            $openPeriod = \App\Models\PayPeriod::where('status', 'open')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->first();
        }
        
        return response()->json([
            'has_open_period' => $openPeriod !== null,
            'open_period' => $openPeriod ? new \App\Http\Resources\PayPeriodResource($openPeriod) : null,
        ]);
    });
        
        Route::get('/pay-periods', [PayPeriodController::class, 'index']);
        Route::get('/pay-periods/{payPeriod}', [PayPeriodController::class, 'show']);
    });

    Route::middleware('can:payroll.manage')->group(function () {
        Route::post('/pay-periods', [PayPeriodController::class, 'store']);
        Route::patch('/pay-periods/{payPeriod}/open', [PayPeriodController::class, 'open']); // PATCH au lieu de POST
        Route::post('/pay-periods/{payPeriod}/generate-payslips', [PayPeriodController::class, 'generatePayslips']);
        Route::patch('/pay-periods/{payPeriod}/close', [PayPeriodController::class, 'close']); // PATCH au lieu de POST
        Route::patch('/pay-periods/{payPeriod}/break', [PayPeriodController::class, 'break']);
        Route::patch('/pay-periods/{payPeriod}/reopen', [PayPeriodController::class, 'reopen']);
        Route::post('/pay-periods/{payPeriod}/send-payslips-email', [PayPeriodController::class, 'sendPayslipsByEmail']);


    });

        // ===== K. RUBRIQUES DE PAIE =====
    Route::middleware('can:payroll.manage')->group(function () {
        Route::get('/payroll-items', [PayrollItemController::class, 'index']);
        Route::post('/payroll-items', [PayrollItemController::class, 'store']);
        Route::put('/payroll-items/{payrollItem}', [PayrollItemController::class, 'update']);
        Route::delete('/payroll-items/{payrollItem}', [PayrollItemController::class, 'destroy']);
        Route::patch('/payroll-items/{payrollItem}/toggle-active', [PayrollItemController::class, 'toggleActive']);
    });

    // ===== F. BULLETINS DE PAIE =====
    Route::middleware('can:payroll.view')->group(function () {
        Route::get('/payslips', [PayslipController::class, 'index']);
        Route::get('/payslips/{payslip}', [PayslipController::class, 'show']);
        Route::get('/payslips/{payslip}/download', [PayslipController::class, 'download']);
    });
    
    Route::middleware('can:payroll.manage')->group(function () {
        Route::post('/payslips/generate', [PayslipController::class, 'generate']);
        Route::patch('/payslips/{payslip}/validate', [PayslipController::class, 'validate']); // PATCH au lieu de POST
        Route::patch('/payslips/{payslip}/pay', [PayslipController::class, 'markAsPaid']); // PATCH au lieu de POST
    });

    // ===== G. DASHBOARD =====
    Route::middleware('can:employees.view')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index']);
    });

        // ===== H. DONNÉES DE RÉFÉRENCE (Catégories, Départements, Postes) =====

    // --- 1. CATÉGORIES ---
    Route::get('/categories', function () {
        $categories = \App\Models\Category::all();
        return response()->json([
            'data' => $categories->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'code' => $c->code,
                'created_at' => $c->created_at,
            ]),
        ]);
    });

    Route::post('/categories', function (\Illuminate\Http\Request $request) {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'code' => 'required|string|max:50|unique:categories,code',
        ]);
        $category = \App\Models\Category::create($validated);
        return response()->json(['message' => 'Catégorie créée.', 'data' => $category], 201);
    });

    Route::put('/categories/{category}', function (\Illuminate\Http\Request $request, \App\Models\Category $category) {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'code' => 'required|string|max:50|unique:categories,code,' . $category->id,
        ]);
        $category->update($validated);
        return response()->json(['message' => 'Catégorie mise à jour.', 'data' => $category]);
    });

    Route::delete('/categories/{category}', function (\App\Models\Category $category) {
        if ($category->employees()->exists() || $category->payslipTemplates()->exists()) {
            return response()->json(['message' => 'Impossible : cette catégorie est utilisée par des employés ou des templates.'], 400);
        }
        $category->delete();
        return response()->json(['message' => 'Catégorie supprimée.']);
    });

    // --- 2. DÉPARTEMENTS ---
    Route::get('/departments', function () {
        $departments = \App\Models\Department::with('manager')->get();
        return response()->json([
            'data' => $departments->map(fn($d) => [
                'id' => $d->id,
                'name' => $d->name,
                'code' => $d->code,
                'manager_id' => $d->manager_id,
                'manager' => $d->manager ? [
                    'id' => $d->manager->id,
                    'full_name' => $d->manager->full_name,
                ] : null,
                'created_at' => $d->created_at,
            ]),
        ]);
    });

    Route::post('/departments', function (\Illuminate\Http\Request $request) {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:departments,name',
            'code' => 'required|string|max:50|unique:departments,code',
            'manager_id' => 'nullable|exists:employees,id',
        ]);
        $department = \App\Models\Department::create($validated);
        return response()->json(['message' => 'Département créé.', 'data' => $department], 201);
    });

    Route::put('/departments/{department}', function (\Illuminate\Http\Request $request, \App\Models\Department $department) {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:departments,name,' . $department->id,
            'code' => 'required|string|max:50|unique:departments,code,' . $department->id,
            'manager_id' => 'nullable|exists:employees,id',
        ]);
        $department->update($validated);
        return response()->json(['message' => 'Département mis à jour.', 'data' => $department]);
    });

    Route::delete('/departments/{department}', function (\App\Models\Department $department) {
        if ($department->positions()->exists() || $department->employees()->exists()) {
            return response()->json(['message' => 'Impossible : ce département est utilisé par des postes ou des employés.'], 400);
        }
        $department->delete();
        return response()->json(['message' => 'Département supprimé.']);
    });

    // --- 3. POSTES ---
    Route::get('/positions', function () {
        $positions = \App\Models\Position::with(['department', 'category'])->get();
        return response()->json([
            'data' => $positions->map(fn($p) => [
                'id' => $p->id,
                'title' => $p->title,
                'department_id' => $p->department_id,
                'category_id' => $p->category_id,
                'department' => $p->department ? [
                    'id' => $p->department->id,
                    'name' => $p->department->name,
                ] : null,
                'category' => $p->category ? [
                    'id' => $p->category->id,
                    'name' => $p->category->name,
                ] : null,
                'created_at' => $p->created_at,
            ]),
        ]);
    });

        Route::post('/positions', function (\Illuminate\Http\Request $request) {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'category_id' => 'nullable|exists:categories,id', 
        ]);
        $position = \App\Models\Position::create($validated);
        return response()->json(['message' => 'Poste créé.', 'data' => $position->load(['department', 'category'])], 201);
    });

    Route::put('/positions/{position}', function (\Illuminate\Http\Request $request, \App\Models\Position $position) {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'category_id' => 'nullable|exists:categories,id', 
        ]);
        $position->update($validated);
        return response()->json(['message' => 'Poste mis à jour.', 'data' => $position->load(['department', 'category'])]);
    });

    Route::delete('/positions/{position}', function (\App\Models\Position $position) {
        if ($position->employees()->exists()) {
            return response()->json(['message' => 'Impossible : ce poste est utilisé par des employés.'], 400);
        }
        $position->delete();
        return response()->json(['message' => 'Poste supprimé.']);
    });

    // ===== I. TEMPLATES DE CONTRATS =====
    Route::get('/contract-templates', function () {
        $templates = \App\Models\ContractTemplate::all();
        return response()->json(['data' => $templates]);
    });

    Route::get('/contract-templates/{contractTemplate}', function (\App\Models\ContractTemplate $contractTemplate) {
        return response()->json(['data' => $contractTemplate]);
    });

    Route::post('/contract-templates', function (\Illuminate\Http\Request $request) {
        $validated = $request->validate([
            'type' => 'required|string|max:255|unique:contract_templates,type',
            'content' => 'required|string',
        ]);
        $template = \App\Models\ContractTemplate::create($validated);
        return response()->json(['message' => 'Modèle créé.', 'data' => $template], 201);
    });

    // ✅ MODIFICATION : Utilise l'ID (route model binding)
    Route::put('/contract-templates/{contractTemplate}', function (\Illuminate\Http\Request $request, \App\Models\ContractTemplate $contractTemplate) {
        $validated = $request->validate([
            // ✅ Permet de changer le type, en excluant le template actuel de l'unicité
            'type' => 'sometimes|required|string|max:255|unique:contract_templates,type,' . $contractTemplate->id,
            'content' => 'required|string',
        ]);
        $contractTemplate->update($validated);
        return response()->json(['message' => 'Modèle mis à jour.', 'data' => $contractTemplate]);
    });

    Route::delete('/contract-templates/{contractTemplate}', function (\App\Models\ContractTemplate $contractTemplate) {
        $contractTemplate->delete();
        return response()->json(['message' => 'Modèle supprimé.']);
    });

        // ===== TEMPLATES DE BULLETINS (version avancée) =====
    Route::middleware('can:payroll.manage')->group(function () {
        Route::get('/payslip-templates', [PayslipTemplateController::class, 'index']);
        Route::post('/payslip-templates', [PayslipTemplateController::class, 'store']);
        Route::put('/payslip-templates/{payslipTemplate}', [PayslipTemplateController::class, 'update']);
        Route::delete('/payslip-templates/{payslipTemplate}', [PayslipTemplateController::class, 'destroy']);
        Route::post('/payslip-templates/{payslipTemplate}/sync-items', [PayslipTemplateController::class, 'syncPayrollItems']);
    });

    // ===== NOTIFICATIONS =====
    Route::get('/notifications', function (\Illuminate\Http\Request $request) {
        $notifications = \App\Models\Notification::forUser($request->user()->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
        return response()->json(['data' => $notifications]);
    });

    Route::get('/notifications/unread-count', function (\Illuminate\Http\Request $request) {
        $count = \App\Models\Notification::forUser($request->user()->id)
            ->unread()
            ->count();
        return response()->json(['count' => $count]);
    });

    Route::patch('/notifications/{notification}/read', function (\Illuminate\Http\Request $request, \App\Models\Notification $notification) {
        if ($notification->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }
        $notification->markAsRead();
        return response()->json(['message' => 'Marquée comme lue.']);
    });

    Route::patch('/notifications/mark-all-read', function (\Illuminate\Http\Request $request) {
        \App\Models\Notification::forUser($request->user()->id)
            ->unread()
            ->update(['is_read' => true, 'read_at' => now()]);
        return response()->json(['message' => 'Toutes marquées comme lues.']);
    });

    // ===== DASHBOARD WIDGETS =====
    Route::middleware('can:employees.view')->group(function () {
        Route::get('/dashboard/pending-requests', function () {
            $pendingLeaves = \App\Models\Leave::where('status', 'pending')->count();
            $pendingAbsences = \App\Models\Absence::where('status', 'pending')->count();
            
            return response()->json([
                'pending_leaves' => $pendingLeaves,
                'pending_absences' => $pendingAbsences,
            ]);
        });

        Route::get('/dashboard/expiring-contracts', function () {
            $warningDays = \App\Models\Setting::get('contract_expiry_warning_days', 30);
            $today = \Carbon\Carbon::today();
            
            $contracts = \App\Models\Contract::whereNotNull('end_date')
                ->where('end_date', '>=', $today)
                ->where('end_date', '<=', $today->copy()->addDays($warningDays))
                ->with('employee')
                ->orderBy('end_date', 'asc')
                ->limit(10)
                ->get()
                ->map(fn($c) => [
                    'id' => $c->id,
                    'employee_name' => $c->employee->full_name,
                    'end_date' => $c->end_date,
                    'days_remaining' => $today->diffInDays(\Carbon\Carbon::parse($c->end_date)),
                ]);
            
            return response()->json(['data' => $contracts]);
        });
    });

    // ===== L. GESTION DES UTILISATEURS ET RÔLES =====
    Route::middleware('can:manage.users')->prefix('admin')->group(function () {
        Route::get('/users', [\App\Http\Controllers\Api\UserController::class, 'index']);
        Route::post('/users', [\App\Http\Controllers\Api\UserController::class, 'store']);
        Route::put('/users/{user}', [\App\Http\Controllers\Api\UserController::class, 'update']);
        Route::delete('/users/{user}', [\App\Http\Controllers\Api\UserController::class, 'destroy']);
        Route::post('/users/{user}/reset-password', [\App\Http\Controllers\Api\UserController::class, 'resetPassword']);
        Route::get('/roles-permissions', [\App\Http\Controllers\Api\UserController::class, 'getRolesAndPermissions']);
    });

        // ===== M. SETTINGS (Lecture + Modification uniquement) =====
    Route::middleware('can:manage.users')->group(function () {
        // Lister tous les settings (groupés)
        Route::get('/settings', function () {
            $settings = \App\Models\Setting::orderBy('group')->orderBy('key')->get();
            
            // Grouper par catégorie
            $grouped = $settings->groupBy('group')->map(function ($items, $group) {
                return [
                    'group' => $group,
                    'label' => match($group) {
                        'contracts' => 'Contrats',
                        'leaves' => 'Congés',
                        'payroll' => 'Paie',
                        'general' => 'Général',
                        default => ucfirst($group),
                    },
                    'settings' => $items,
                ];
            })->values();
            
            return response()->json(['data' => $grouped]);
        });

        // Modifier un setting existant (PUT uniquement, pas de création)
        Route::put('/settings/{setting}', function (\Illuminate\Http\Request $request, \App\Models\Setting $setting) {
            $validated = $request->validate([
                'value' => 'required|string',
            ]);

            // Vérifier que le type est cohérent
            if ($setting->type === 'boolean' && !in_array($validated['value'], ['0', '1', 'true', 'false'])) {
                return response()->json(['message' => 'Valeur booléenne invalide.'], 422);
            }
            if ($setting->type === 'integer' && !is_numeric($validated['value'])) {
                return response()->json(['message' => 'Valeur numérique requise.'], 422);
            }

            $setting->update(['value' => $validated['value']]);

            return response()->json([
                'message' => 'Paramètre mis à jour.',
                'data' => $setting
            ]);
        });
    });

        // ===== RÉINITIALISATION MOT DE PASSE (Admin uniquement) =====
    Route::middleware('can:manage.users')->group(function () {
        Route::post('/employees/{employee}/reset-password', function (\Illuminate\Http\Request $request, \App\Models\Employee $employee) {
            $user = $employee->user;
            if (!$user) {
                return response()->json(['message' => 'Cet employé n\'a pas de compte utilisateur.'], 404);
            }

            $newPassword = \Illuminate\Support\Str::random(8);
            $user->update(['password' => \Illuminate\Support\Facades\Hash::make($newPassword)]);

            // Envoyer le nouveau mot de passe par email
            try {
                \Illuminate\Support\Facades\Mail::send('emails.welcome', [
                    'user' => $user,
                    'temporaryPassword' => $newPassword,
                ], function ($mail) use ($user) {
                    $mail->to($user->email)
                         ->subject('[VICO RH] Réinitialisation de votre mot de passe');
                });
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("Erreur envoi email réinitialisation : " . $e->getMessage());
            }

            return response()->json([
                'message' => 'Mot de passe réinitialisé et envoyé par email.',
                'new_password' => config('app.debug') ? $newPassword : null // Visible seulement en debug
            ]);
        });
    });

    // ===== TÉLÉCHARGEMENT BULLETIN VIA LIEN EMAIL (URL Signée) =====
    Route::get('/payslips/{payslip}/download', [\App\Http\Controllers\Api\PayslipController::class, 'downloadSigned'])
    ->name('payslip.download')
    ->middleware('signed'); // ✅ Sécurise le lien contre la falsification


});