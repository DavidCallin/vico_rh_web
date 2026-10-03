<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeaveRequest;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveBalanceHistory;
use App\Models\User;
use App\Services\HrNotifier;
use App\Support\DateSpan;
use App\Support\HrLabels;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $isHr = $user->hasRole('rh');

        $leaves = Leave::query()
            ->with('employee')
            ->when(! $isHr, fn ($query) => $query->where('employee_id', $user->employee_id ?? 0))
            ->latest('id')
            ->limit(100)
            ->get()
            ->map(fn (Leave $leave): array => $this->present($leave));

        return Inertia::render('leaves/index', [
            'leaves' => $leaves,
            'leaveTypes' => HrLabels::leaveTypes(),
            'employees' => $isHr ? $this->employeeOptions() : [],
            'isHr' => $isHr,
            'canCreate' => $isHr || $user->employee_id !== null,
        ]);
    }

    public function store(StoreLeaveRequest $request, HrNotifier $notifier): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $employeeId = $this->employeeId($user, $request->integer('employee_id') ?: null);
        $start = CarbonImmutable::parse($request->string('start_date')->toString());
        $end = CarbonImmutable::parse($request->string('end_date')->toString());

        $leave = Leave::query()->create([
            'leave_type' => $request->string('leave_type')->toString(),
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'days_count' => DateSpan::inclusiveDays($start, $end),
            'status' => 'pending',
            'reason' => $request->input('reason'),
            'employee_id' => $employeeId,
        ]);

        $leave->load('employee');
        $notifier->leaveRequested($this->owner($leave), $this->summary($leave), $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Demande de congé envoyée.']);

        return to_route('leaves.index');
    }

    public function approve(Request $request, Leave $leave, HrNotifier $notifier): RedirectResponse
    {
        return $this->decide($request, $leave, 'approved', $notifier);
    }

    public function reject(Request $request, Leave $leave, HrNotifier $notifier): RedirectResponse
    {
        return $this->decide($request, $leave, 'rejected', $notifier);
    }

    private function decide(Request $request, Leave $leave, string $status, HrNotifier $notifier): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->hasRole('rh'), 403);

        if ($leave->status !== 'pending') {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Cette demande a déjà été traitée.']);

            return back();
        }

        $leave->update([
            'status' => $status,
            'validation_date' => now()->toDateString(),
            'validator_id' => $user->employee_id,
        ]);

        if ($status === 'approved') {
            $this->debitAnnualLeave($leave);
        }

        $leave->load('employee');
        $notifier->requestDecided($this->owner($leave), 'congé', $status, $this->summary($leave));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $status === 'approved' ? 'Congé approuvé.' : 'Congé rejeté.',
        ]);

        return back();
    }

    private function debitAnnualLeave(Leave $leave): void
    {
        if ($leave->leave_type !== 'annual') {
            return;
        }

        $previous = (float) (LeaveBalanceHistory::query()
            ->where('employee_id', $leave->employee_id)
            ->orderByDesc('id')
            ->value('balance_after') ?? 0);
        $days = (float) $leave->days_count;

        LeaveBalanceHistory::query()->create([
            'date' => now()->toDateString(),
            'movement_type' => 'debit',
            'days_count' => $days,
            'balance_after' => $previous - $days,
            'reason' => 'Congé annuel du '.$leave->start_date->format('d/m/Y').' au '.$leave->end_date->format('d/m/Y'),
            'employee_id' => $leave->employee_id,
            'leave_id' => $leave->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Leave $leave): array
    {
        return [
            'id' => $leave->id,
            'employee_name' => $this->owner($leave)->full_name,
            'leave_type' => $leave->leave_type,
            'leave_type_label' => HrLabels::leaveType($leave->leave_type),
            'start_date' => $leave->start_date->toDateString(),
            'end_date' => $leave->end_date->toDateString(),
            'days_count' => $leave->days_count,
            'status' => $leave->status,
            'status_label' => HrLabels::requestStatus($leave->status),
            'reason' => $leave->reason,
        ];
    }

    private function owner(Leave $leave): Employee
    {
        $employee = $leave->employee;

        if (! $employee instanceof Employee) {
            abort(404);
        }

        return $employee;
    }

    private function summary(Leave $leave): string
    {
        return HrLabels::leaveType($leave->leave_type)
            .' du '.$leave->start_date->format('d/m/Y')
            .' au '.$leave->end_date->format('d/m/Y');
    }

    private function employeeId(User $user, ?int $chosen): int
    {
        if ($user->hasRole('rh')) {
            return (int) $chosen;
        }

        return (int) $user->employee_id;
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function employeeOptions(): array
    {
        return Employee::query()
            ->orderBy('last_name')
            ->get()
            ->map(fn (Employee $employee): array => [
                'id' => $employee->id,
                'name' => $employee->full_name,
            ])
            ->values()
            ->all();
    }
}
