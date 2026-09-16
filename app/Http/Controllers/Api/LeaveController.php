<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveLeaveRequest;
use App\Http\Requests\StoreLeaveRequest;
use App\Http\Resources\LeaveResource;
use App\Models\Leave;
use App\Models\PayPeriod;
use App\Models\LeaveBalance; // ✅ Ajouté
use App\Models\LeaveBalanceHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Mail\LeaveRequestedMail;
use App\Mail\LeaveStatusChangedMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class LeaveController extends Controller
{
    /**
     * Liste des congés (filtrée selon le rôle)
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Leave::with(['employee.position.department', 'validator']);

        if ($user->hasRole('employee')) {
            $query->where('employee_id', $user->employee_id);
        } elseif ($user->hasRole('manager') && $user->employee) {
            $departmentId = $user->employee->position->department_id ?? null;
            if ($departmentId) {
                $query->whereHas('employee.position', function ($q) use ($departmentId) {
                    $q->where('department_id', $departmentId);
                });
            }
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $leaves = $query->orderBy('created_at', 'desc')->paginate(15);
        return LeaveResource::collection($leaves);
    }

    /**
     * Créer une demande de congé
     */
    public function store(StoreLeaveRequest $request)
    {
        $user = Auth::user();

        $startDate = \Carbon\Carbon::parse($request->start_date);
        $endDate = \Carbon\Carbon::parse($request->end_date);
        $daysCount = $startDate->diffInDays($endDate) + 1;

        $payPeriod = PayPeriod::where('year', $startDate->year)
            ->where('month', $startDate->month)
            ->first();

        $leave = Leave::create([
            'leave_type' => $request->leave_type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'days_count' => $daysCount,
            'status' => 'pending',
            'reason' => $request->reason,
            'employee_id' => $user->employee_id,
            'pay_period_id' => $payPeriod?->id,
        ]);

        // 🎯 MISE À JOUR DU SOLDE : Ajouter aux jours en attente
        $this->updateLeaveBalance($leave->employee_id, $startDate->year, $daysCount, 'pending_add');

        return new LeaveResource($leave->load(['employee', 'payPeriod']));
    }

    /**
     * Afficher un congé spécifique
     */
    public function show(Leave $leave)
    {
        $leave->load(['employee.position.department', 'validator', 'balanceHistories']);
        return new LeaveResource($leave);
    }

    /**
     * Approuver ou Rejeter un congé
     */
    public function updateStatus(ApproveLeaveRequest $request, Leave $leave)
    {
        if ($leave->status !== 'pending') {
            return response()->json(['message' => 'Ce congé a déjà été traité.'], 400);
        }

        $user = Auth::user();
        $newStatus = $request->status;
        $year = \Carbon\Carbon::parse($leave->start_date)->year;

        $leave->update([
            'status' => $newStatus,
            'validation_date' => now(),
            'validator_id' => $user->employee_id,
            'reason' => $newStatus === 'rejected' 
                ? ($request->rejection_reason ?: $leave->reason) 
                : $leave->reason,
        ]);

        // 🎯 MISE À JOUR DU SOLDE SELON LA DÉCISION
        if ($newStatus === 'approved') {
            $this->updateLeaveBalance($leave->employee_id, $year, $leave->days_count, 'approve');
        } elseif ($newStatus === 'rejected' || $newStatus === 'cancelled') {
            $this->updateLeaveBalance($leave->employee_id, $year, $leave->days_count, 'reject');
        }

        // 🎯 HISTORISATION PRÉCISE DU SOLDE
        $currentBalance = LeaveBalance::where('employee_id', $leave->employee_id)
            ->where('year', $year)
            ->first();

        LeaveBalanceHistory::create([
            'employee_id' => $leave->employee_id,
            'leave_type' => $leave->leave_type,
            'change' => $newStatus === 'approved' ? -$leave->days_count : 0,
            'balance_after' => $currentBalance ? $currentBalance->remaining_days : 0, // ✅ Vrai solde calculé
            'reason' => $newStatus === 'approved' 
                ? "Congé approuvé (Demande #{$leave->id})" 
                : "Congé rejeté/annulé (Demande #{$leave->id})",
            'reference_id' => $leave->id,
            'reference_type' => 'leave',
            'created_by' => $user->employee_id,
            'date' => now(),
            'days_count' => $leave->days_count,
        ]);

        // ENVOI D'EMAIL
        $employee = $leave->employee;
        if ($employee && $employee->user) {
            Mail::to($employee->user->email)->send(new LeaveStatusChangedMail($leave, $newStatus));
        }

        return new LeaveResource($leave->load(['validator']));
    }

    /**
     * Supprimer un congé (seulement s'il est en attente)
     */
    public function destroy(Leave $leave)
    {
        $user = Auth::user();

        if ($leave->status !== 'pending') {
            return response()->json(['message' => 'Impossible de supprimer un congé déjà traité.'], 400);
        }

        if ($user->hasRole('employee') && $leave->employee_id !== $user->employee_id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        // 🎯 MISE À JOUR DU SOLDE : Retirer des jours en attente avant suppression
        $year = \Carbon\Carbon::parse($leave->start_date)->year;
        $this->updateLeaveBalance($leave->employee_id, $year, $leave->days_count, 'pending_remove');

        $leave->delete();

        return response()->noContent();
    }

    /**
     * 🛠️ MÉTHODE PRIVÉE : Gestion centralisée des calculs de solde
     */
    private function updateLeaveBalance(int $employeeId, int $year, float $days, string $action): void
    {
        $balance = LeaveBalance::firstOrCreate(
            ['employee_id' => $employeeId, 'year' => $year],
            ['total_days' => 25, 'taken_days' => 0, 'remaining_days' => 25, 'pending_days' => 0]
        );

        switch ($action) {
            case 'pending_add':
                $balance->pending_days += $days;
                break;
            case 'approve':
                $balance->taken_days += $days;
                $balance->pending_days = max(0, $balance->pending_days - $days);
                break;
            case 'reject':
            case 'pending_remove':
                $balance->pending_days = max(0, $balance->pending_days - $days);
                break;
        }

        // Recalcule automatiquement : remaining = total - taken - pending
        $balance->recalculateRemaining();
    }
}