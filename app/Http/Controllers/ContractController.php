<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContractRequest;
use App\Models\Contract;
use App\Models\Employee;
use App\Models\User;
use App\Services\ContractGenerator;
use App\Services\ContractReminderService;
use App\Support\HrLabels;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContractController extends Controller
{
    public function index(Request $request, ContractReminderService $reminders): Response
    {
        $reminders->dispatchDue();

        /** @var User $user */
        $user = $request->user();
        $isHr = $user->hasRole('rh');
        $horizon = now()->addDays((int) config('payroll.contract_reminder_days'))->startOfDay();

        $contracts = Contract::query()
            ->with('employee')
            ->when(! $isHr, fn ($query) => $query->where('employee_id', $user->employee_id ?? 0))
            ->latest('id')
            ->limit(100)
            ->get()
            ->map(function (Contract $contract) use ($horizon): array {
                $end = $contract->end_date?->copy()->startOfDay();
                $expired = $end?->lt(now()->startOfDay()) ?? false;
                $soon = $end !== null && ! $expired && $end->lte($horizon);

                return [
                    'id' => $contract->id,
                    'employee_name' => $contract->employee instanceof Employee
                        ? $contract->employee->full_name
                        : '',
                    'contract_type' => $contract->contract_type,
                    'contract_type_label' => HrLabels::contractType($contract->contract_type),
                    'start_date' => $contract->start_date->toDateString(),
                    'end_date' => $contract->end_date?->toDateString(),
                    'base_salary' => $contract->base_salary,
                    'has_document' => $contract->pdf_path !== null,
                    'expired' => $expired,
                    'expiring_soon' => $soon,
                ];
            });

        return Inertia::render('contracts/index', [
            'contracts' => $contracts,
            'contractTypes' => HrLabels::contractTypes(),
            'employees' => $isHr
                ? Employee::query()->orderBy('last_name')->get()->map(fn (Employee $employee): array => [
                    'id' => $employee->id,
                    'name' => $employee->full_name,
                ])->all()
                : [],
            'isHr' => $isHr,
            'reminderDays' => (int) config('payroll.contract_reminder_days'),
        ]);
    }

    public function store(StoreContractRequest $request, ContractGenerator $generator): RedirectResponse
    {
        $contract = Contract::query()->create([
            'employee_id' => $request->integer('employee_id'),
            'contract_type' => $request->string('contract_type')->toString(),
            'start_date' => $request->date('start_date')?->toDateString(),
            'end_date' => $request->input('end_date') ?: null,
            'base_salary' => $request->input('base_salary'),
        ]);

        $generator->generate($contract);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Contrat enregistré et document généré.']);

        return to_route('contracts.index');
    }

    public function generate(Request $request, Contract $contract, ContractGenerator $generator): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('rh'), 403);
        $generator->generate($contract);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Document de contrat généré.']);

        return back();
    }

    public function download(Request $request, Contract $contract): StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->hasRole('rh') || $user->employee_id === $contract->employee_id, 403);
        abort_unless($contract->pdf_path && Storage::disk('local')->exists($contract->pdf_path), 404);

        return Storage::disk('local')->download($contract->pdf_path, 'contrat-'.$contract->id.'.pdf');
    }
}
