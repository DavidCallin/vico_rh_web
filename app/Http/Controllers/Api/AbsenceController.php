<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveAbsenceRequest;
use App\Http\Requests\StoreAbsenceRequest;
use App\Http\Resources\AbsenceResource;
use App\Models\Absence;
use App\Models\PayPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AbsenceController extends Controller
{
    /**
     * Liste des absences (filtrée selon le rôle)
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Absence::with(['employee.position.department', 'validator']);

        // Si c'est un employé simple, il ne voit que SES absences
        if ($user->hasRole('employee')) {
            $query->where('employee_id', $user->employee_id);
        }
        // Si c'est un manager, il voit les absences de SON département
        elseif ($user->hasRole('manager') && $user->employee) {
            $deptId = $user->employee->position->department_id ?? null;
            if ($deptId) {
                $query->whereHas('employee.position', function ($q) use ($deptId) {
                    $q->where('department_id', $deptId);
                });
            }
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $absences = $query->orderBy('created_at', 'desc')->paginate(15);

        return AbsenceResource::collection($absences);
    }

    /**
     * Déclarer une absence
     */
    public function store(StoreAbsenceRequest $request)
    {
        $user = Auth::user();
        
        // Upload du justificatif si fourni
        $proofPath = null;
        if ($request->hasFile('proof')) {
            $proofPath = $request->file('proof')->store('absences/proofs', 'public');
        }

        // Trouver la période de paie correspondante
        $startDate = \Carbon\Carbon::parse($request->start_date);
        $payPeriod = PayPeriod::where('year', $startDate->year)
            ->where('month', $startDate->month)
            ->first();

        $absence = Absence::create([
            'type' => $request->type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'reason' => $request->reason,
            'proof_path' => $proofPath,
            'status' => 'pending',
            'employee_id' => $user->employee_id,
            'pay_period_id' => $payPeriod?->id,
        ]);

            app(\App\Services\NotificationService::class)->notifyNewAbsenceRequest($absence);


        return new AbsenceResource($absence->load(['employee', 'payPeriod']));
    }

    /**
     * Afficher une absence spécifique
     */
    public function show(Absence $absence)
    {
        $absence->load(['employee.position.department', 'validator']);
        return new AbsenceResource($absence);
    }

    /**
     * Approuver ou Rejeter une absence
     */
    public function updateStatus(ApproveAbsenceRequest $request, Absence $absence)
    {
        if ($absence->status !== 'pending') {
            return response()->json(['message' => 'Cette absence a déjà été traitée.'], 400);
        }

        $user = Auth::user();

        $absence->update([
            'status' => $request->status,
            'validation_date' => now(),
            'validator_id' => $user->employee_id,
        ]);

        return new AbsenceResource($absence->load(['validator']));
    }

    /**
     * Supprimer une absence (seulement si en attente)
     */
    public function destroy(Absence $absence)
    {
        $user = Auth::user();

        if ($absence->status !== 'pending') {
            return response()->json(['message' => 'Impossible de supprimer une absence déjà traitée.'], 400);
        }

        if ($user->hasRole('employee') && $absence->employee_id !== $user->employee_id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        // Supprimer le justificatif s'il existe
        if ($absence->proof_path) {
            Storage::disk('public')->delete($absence->proof_path);
        }

        $absence->delete();

        return response()->noContent();
    }
}