<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveLeaveRequest;
use App\Http\Requests\StoreLeaveRequest;
use App\Http\Resources\LeaveResource;
use App\Models\Leave;
use App\Models\PayPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Mail\LeaveRequestedMail;
use App\Mail\LeaveStatusChangedMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Models\LeaveBalanceHistory;

class LeaveController extends Controller
{
    /**
     * Liste des congés (filtrée selon le rôle)
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Leave::with(['employee.position.department', 'validator']);

        // Si c'est un employé simple, il ne voit que SES congés
        if($user->hasRole('employee')){
            $query->where('employee_id', $user->employee_id);
        }

        // Si c'est un manager, il voit les congés de SON département
        elseif($user->hasRole('manager') && $user->employee){
            $departmentId = $user->employee->position->department_id ?? null;
            if($departmentId){
                $query->whereHas('employee.position', function($q) use ($departmentId){
                    $q->where('department_id', $departmentId);
                });
            }
        }

        // Admin et RH voient tout (éventuellement filtrable par département/date)
        if($request->has('status')){
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

        // Calcul du nombre de jours de congé
        $startDate = \Carbon\Carbon::parse($request->start_date);
        $endDate = \Carbon\Carbon::parse($request->end_date);
        $daysCount = $startDate->diffInDays($endDate) + 1; // +1 pour inclure le jour de début

        //Trouver la période de paie correspondante
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
            'employee_id' => $user->employee_id, // L'employé connecté
            'pay_period_id' => $payPeriod?->id,
        ]);

        // ENVOI D'EMAIL : Notifier les RH et Managers
        // $approvers = User::role(['rh', 'manager'])->get();
        // foreach ($approvers as $approver) {
        //     Mail::to($approver->email)->send(new LeaveRequestedMail($leave));
        // }

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
        // Empêcher de modifier un congé déjà traité
        if ($leave->status !== 'pending') {
            return response()->json(['message' => 'Ce congé a déjà été traité.'], 400);
        }

        $user = Auth::user();
        $newStatus = $request->status;

        $leave->update([
            'status' => $request->status,
            'validation_date' => now(),
            'validator_id' => $user->employee_id,
            'reason' => $request->status === 'rejected' 
                ? ($request->rejection_reason ?: $leave->reason) 
                : $leave->reason,
        ]);

        //  Si le congé est approuvé, on déduit du solde et on historise
        if ($newStatus === 'approved') {
            LeaveBalanceHistory::create([
                'employee_id' => $leave->employee_id,
                'leave_type' => $leave->leave_type,
                'change' => -$leave->days_count, // Négatif car c'est une consommation
                'balance_after' => 0, // (Optionnel : tu pourras calculer le vrai solde ici si tu as une méthode getBalance())
                'reason' => "Congé approuvé (Demande #{$leave->id})",
                'reference_id' => $leave->id,
                'reference_type' => 'leave',
                'created_by' => $user->employee_id,
                'date' => now(),
                'days_count' => -$leave->days_count,
            ]);
        }

        //  ENVOI D'EMAIL : Notifier l'employé du résultat
        $employee = $leave->employee;
        if ($employee && $employee->user) {
            Mail::to($employee->user->email)->send(new LeaveStatusChangedMail($leave, $request->status));
        }

        // TODO (Étape future) : Ici, on déclenchera la mise à jour du LeaveBalanceHistory

        return new LeaveResource($leave->load(['validator']));
    }

        /**
     * Supprimer un congé (seulement s'il est en attente et par son créateur ou un admin)
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

        $leave->delete();

        return response()->noContent();
    }


}
