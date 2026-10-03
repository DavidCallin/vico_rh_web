<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\PayPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // 1. STATISTIQUES DES EMPLOYÉS (Optimisé avec des requêtes COUNT)
        $totalEmployees = Employee::count();
        $activeEmployees = Employee::where('status', 'active')->count();
        $trialEmployees = Employee::where('status', 'trial')->count();
        // Le statut `on_leave` n'existe pas dans l'enum actuel de la table employees.
        // On mappe cet indicateur aux employés non actifs (démissionnés/terminés).
        $onLeaveEmployees = Employee::whereIn('status', ['resigned', 'terminated'])->count();

        // 2. ALERTES ET TÂCHES EN ATTENTE
        $pendingLeavesCount = Leave::where('status', 'pending')->count();

        // Contrats expirant dans les 30 prochains jours
        $expiringContractsCount = Contract::whereBetween('end_date', [
            now()->toDateString(),
            now()->addDays(30)->toDateString()
        ])->count();

        // 3. RÉPARTITION PAR DÉPARTEMENT (Pour les graphiques camembert)
        // On utilise un JOIN pour compter les employés actifs par département
        $departmentStats = Department::select('departments.name', DB::raw('count(employees.id) as total'))
            ->leftJoin('positions', 'departments.id', '=', 'positions.department_id')
            ->leftJoin('employees', function ($join) {
                $join->on('positions.id', '=', 'employees.position_id')
                     ->where('employees.status', 'active');
            })
            ->groupBy('departments.id', 'departments.name')
            ->get();

        // 4. PÉRIODE DE PAIE EN COURS
        // Priorité à la période réellement ouverte, sinon fallback sur la période du mois courant.
        $currentPayPeriod = PayPeriod::where('status', 'open')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->first()
            ?? PayPeriod::where('year', now()->year)
                ->where('month', now()->month)
                ->first();

        $currentPayPeriodLabel = $currentPayPeriod
            ? \Carbon\Carbon::create($currentPayPeriod->year, $currentPayPeriod->month, 1)->translatedFormat('F Y')
            : 'Aucune période ouverte';

        // 5. RETOUR FORMATÉ POUR LE FRONT-END
        return response()->json([
            'kpi' => [
                'total_employees' => $totalEmployees,
                'active_employees' => $activeEmployees,
                'trial_employees' => $trialEmployees,
                'on_leave_employees' => $onLeaveEmployees,
                'employees_inactive_or_other' => max(0, $totalEmployees - ($activeEmployees + $trialEmployees + $onLeaveEmployees)),
                'pending_leaves' => $pendingLeavesCount,
                'expiring_contracts_30d' => $expiringContractsCount,
            ],
            'charts' => [
                'employees_by_department' => $departmentStats,
            ],
            'current_period' => [
                'label' => $currentPayPeriodLabel,
                'status' => $currentPayPeriod?->status ?? 'none',
            ],
            'recent_activities' => [
                // Optionnel : tu pourras ajouter ici les 5 derniers congés approuvés ou employés créés
                'message' => 'Fonctionnalité à venir dans la prochaine itération'
            ]
        ]);
    }
}
