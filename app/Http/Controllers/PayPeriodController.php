<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePayPeriodRequest;
use App\Models\PayPeriod;
use App\Models\User;
use App\Payroll\PayPeriodRunner;
use App\Support\HrLabels;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PayPeriodController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasRole('rh'), 403);

        $periods = PayPeriod::query()
            ->with('payslips:id,pay_period_id,delivered_at')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get()
            ->map(function (PayPeriod $period): array {
                $pending = $period->payslips->whereNull('delivered_at')->count();

                return [
                    'id' => $period->id,
                    'month' => $period->month,
                    'year' => $period->year,
                    'label' => sprintf('%02d/%d', $period->month, $period->year),
                    'status' => $period->status,
                    'status_label' => HrLabels::payStatus($period->status),
                    'payslips_count' => $period->payslips->count(),
                    'can_run' => $period->payslips->isEmpty() || $pending > 0,
                ];
            });

        return Inertia::render('pay-periods/index', [
            'periods' => $periods,
        ]);
    }

    public function store(StorePayPeriodRequest $request): RedirectResponse
    {
        $month = $request->integer('month');
        $year = $request->integer('year');

        PayPeriod::query()->create([
            'month' => $month,
            'year' => $year,
            'status' => 'open',
            'opening_date' => CarbonImmutable::create($year, $month, 1)->toDateString(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Période de paie ouverte.']);

        return to_route('pay-periods.index');
    }

    public function run(Request $request, PayPeriod $payPeriod, PayPeriodRunner $runner): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->hasRole('rh'), 403);

        $count = $runner->run($payPeriod, $user);

        if ($count === 0) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Aucun bulletin : aucun salarié actif n\'a de contrat sur cette période.',
            ]);

            return back();
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $count.' bulletin(s) calculé(s) et transmis.',
        ]);

        return to_route('pay-periods.index');
    }
}
