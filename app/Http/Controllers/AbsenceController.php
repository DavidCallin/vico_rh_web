<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAbsenceRequest;
use App\Models\Absence;
use App\Models\Employee;
use App\Models\User;
use App\Services\HrNotifier;
use App\Support\HrLabels;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AbsenceController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $isHr = $user->hasRole('rh');

        $absences = Absence::query()
            ->with('employee')
            ->when(! $isHr, fn ($query) => $query->where('employee_id', $user->employee_id ?? 0))
            ->latest('id')
            ->limit(100)
            ->get()
            ->map(fn (Absence $absence): array => [
                'id' => $absence->id,
                'employee_name' => $this->owner($absence)->full_name,
                'type' => $absence->type,
                'type_label' => HrLabels::absenceType($absence->type),
                'start_date' => $absence->start_date->toDateString(),
                'end_date' => $absence->end_date->toDateString(),
                'status' => $absence->status,
                'status_label' => HrLabels::requestStatus($absence->status),
                'reason' => $absence->reason,
                'has_proof' => $absence->proof_path !== null,
            ]);

        return Inertia::render('absences/index', [
            'absences' => $absences,
            'absenceTypes' => HrLabels::absenceTypes(),
            'employees' => $isHr
                ? Employee::query()->orderBy('last_name')->get()->map(fn (Employee $employee): array => [
                    'id' => $employee->id,
                    'name' => $employee->full_name,
                ])->all()
                : [],
            'isHr' => $isHr,
            'canCreate' => $isHr || $user->employee_id !== null,
        ]);
    }

    public function store(StoreAbsenceRequest $request, HrNotifier $notifier): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $employeeId = $user->hasRole('rh')
            ? $request->integer('employee_id')
            : (int) $user->employee_id;
        $start = CarbonImmutable::parse($request->string('start_date')->toString());
        $end = CarbonImmutable::parse($request->string('end_date')->toString());
        $proof = $request->file('proof')?->store('absences', 'local');

        $absence = Absence::query()->create([
            'type' => $request->string('type')->toString(),
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'reason' => $request->input('reason'),
            'proof_path' => $proof,
            'status' => 'pending',
            'employee_id' => $employeeId,
        ]);

        $absence->load('employee');
        $notifier->absenceRequested(
            $this->owner($absence),
            HrLabels::absenceType($absence->type).' du '.$start->format('d/m/Y').' au '.$end->format('d/m/Y'),
            $user,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Demande d\'absence envoyée.']);

        return to_route('absences.index');
    }

    public function approve(Request $request, Absence $absence, HrNotifier $notifier): RedirectResponse
    {
        return $this->decide($request, $absence, 'approved', $notifier);
    }

    public function reject(Request $request, Absence $absence, HrNotifier $notifier): RedirectResponse
    {
        return $this->decide($request, $absence, 'rejected', $notifier);
    }

    public function proof(Request $request, Absence $absence): StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless(
            $user->hasRole('rh') || $user->employee_id === $absence->employee_id,
            403,
        );
        abort_unless($absence->proof_path && Storage::disk('local')->exists($absence->proof_path), 404);

        return Storage::disk('local')->download($absence->proof_path);
    }

    private function decide(Request $request, Absence $absence, string $status, HrNotifier $notifier): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->hasRole('rh'), 403);

        if ($absence->status !== 'pending') {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Cette demande a déjà été traitée.']);

            return back();
        }

        $absence->update([
            'status' => $status,
            'validation_date' => now()->toDateString(),
            'validator_id' => $user->employee_id,
        ]);

        $absence->load('employee');
        $notifier->requestDecided(
            $this->owner($absence),
            'absence',
            $status,
            HrLabels::absenceType($absence->type).' du '.$absence->start_date->format('d/m/Y').' au '.$absence->end_date->format('d/m/Y'),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $status === 'approved' ? 'Absence approuvée.' : 'Absence rejetée.',
        ]);

        return back();
    }

    private function owner(Absence $absence): Employee
    {
        $employee = $absence->employee;

        if (! $employee instanceof Employee) {
            abort(404);
        }

        return $employee;
    }
}
