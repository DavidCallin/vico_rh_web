<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PayslipResource;
use App\Models\Employee;
use App\Models\PayPeriod;
use App\Models\Payslip;
use App\Services\PayslipGeneratorService;
use Illuminate\Http\Request;
use App\Mail\PayslipAvailableMail;
use App\Models\Setting;
use Illuminate\Support\Facades\Mail;

class PayslipController extends Controller
{
    protected PayslipGeneratorService $generator;

    public function __construct(PayslipGeneratorService $generator)
    {
        $this->generator = $generator;
    }

    /**
     * Liste des bulletins
     */
    public function index(Request $request)
    {
        $query = Payslip::with(['employee.position.department', 'payPeriod', 'lines.payrollItem']);

        if ($request->has('pay_period_id')) {
            $query->where('pay_period_id', $request->pay_period_id);
        }

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $payslips = $query->orderBy('generation_date', 'desc')->paginate(15);

        return PayslipResource::collection($payslips);
    }

    /**
     * Afficher un bulletin
     */
    public function show(Payslip $payslip)
    {
        $payslip->load(['employee.position.department', 'payPeriod', 'lines.payrollItem']);
        return new PayslipResource($payslip);
    }

    /**
     * Générer un bulletin pour un employé sur une période
     */
    public function generate(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'pay_period_id' => 'required|exists:pay_periods,id',
        ]);

        $employee = Employee::findOrFail($request->employee_id);
        $period = PayPeriod::findOrFail($request->pay_period_id);

        if ($period->status === 'closed' || $period->status === 'archived') {
            return response()->json([
                'message' => 'Impossible de générer un bulletin sur une période clôturée.',
            ], 400);
        }

        try {
            $payslip = $this->generator->generate($employee, $period);
            $payslip->load(['employee', 'payPeriod', 'lines.payrollItem']);
            return new PayslipResource($payslip);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Valider un bulletin (passe de draft à validated)
     */
    public function validate(Payslip $payslip)
    {
        if ($payslip->status !== 'draft') {
            return response()->json([
                'message' => 'Seuls les bulletins en brouillon peuvent être validés.',
            ], 400);
        }

        $payslip->update(['status' => 'validated']);

         // ENVOI D'EMAIL : Si l'option est activée dans les settings
        if (Setting::get('payslip_auto_email', false)) {
            $employee = $payslip->employee;
            if ($employee && $employee->user) {
                Mail::to($employee->user->email)->send(new PayslipAvailableMail($payslip));
            }
        }

        return new PayslipResource($payslip->load(['employee', 'payPeriod', 'lines.payrollItem']));
    }

    /**
     * Marquer un bulletin comme payé
     */
    public function markAsPaid(Payslip $payslip)
    {
        if ($payslip->status !== 'validated') {
            return response()->json([
                'message' => 'Seuls les bulletins validés peuvent être marqués comme payés.',
            ], 400);
        }

        $payslip->update([
            'status' => 'paid',
            'payment_date' => now(),
        ]);

        return new PayslipResource($payslip);
    }
}