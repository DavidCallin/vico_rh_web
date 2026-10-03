<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PayslipResource;
use App\Models\Employee;
use App\Models\PayPeriod;
use App\Models\Payslip;
use App\Models\PayslipTemplate;
use App\Models\LeaveBalance; // ✅ Ajouté pour les congés
use App\Services\PayslipGeneratorService;
use Illuminate\Http\Request;
use App\Mail\PayslipAvailableMail;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema; // ✅ CORRECTION : Import de Schema
use Barryvdh\DomPDF\Facade\Pdf;

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
        $user = Auth::user();
        $query = Payslip::with(['employee', 'payPeriod', 'lines.payrollItem']);

        if ($user && $user->hasRole('employee')) {
            $query->where('employee_id', $user->employee_id);
        }

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
        $user = Auth::user();

        if ($user && $user->hasRole('employee') && $payslip->employee_id !== $user->employee_id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $payslip->load(['employee', 'payPeriod', 'lines.payrollItem']);
        return new PayslipResource($payslip);
    }

    /**
     * 🎯 TÉLÉCHARGER / GÉNÉRER LE PDF DU BULLETIN (Version avancée synchronisée)
     */
    public function download(Payslip $payslip)
    {
        $user = Auth::user();

        if ($user && $user->hasRole('employee') && $payslip->employee_id !== $user->employee_id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $payslip->load(['employee', 'payPeriod', 'lines.payrollItem']);

        if (in_array($payslip->payPeriod->status, ['break', 'archived'])) {
            return response()->json([
                'message' => 'Impossible de télécharger ce bulletin : la période est verrouillée.'
            ], 403);
        }

        $template = PayslipTemplate::where('is_default', true)->first()
                    ?? PayslipTemplate::first();

        if (!$template) {
            return response()->json(['message' => 'Aucun template de bulletin configuré.'], 404);
        }

        $employee = $payslip->employee;
        $period = $payslip->payPeriod;

        // 1. Récupérer le solde de congé
        $currentYear = now()->year;
        $leaveBalance = \App\Models\LeaveBalance::where('employee_id', $employee->id)
            ->where('year', $currentYear)
            ->first();

        $leavesTaken = $leaveBalance ? $leaveBalance->taken_days : 0;
        $leavesRemaining = $leaveBalance ? $leaveBalance->remaining_days : 0;

        // 2. Regrouper les lignes par type
        $earningLines = $payslip->lines->filter(fn($l) => $l->payrollItem->type === 'gain');
        $deductionLines = $payslip->lines->filter(fn($l) => $l->payrollItem->type === 'deduction');
        $totalEarnings = $earningLines->sum('amount');
        $totalDeductions = $deductionLines->sum('amount');

        // 3. Construire le HTML des lignes de gains
        $earningsHtml = '';
        foreach ($earningLines as $line) {
            $amount = number_format($line->amount, 2, ',', ' ') . ' €';
            $desc = $line->description ? " <small style='color:#666'>({$line->description})</small>" : '';
            $earningsHtml .= "<tr><td>{$line->payrollItem->name}{$desc}</td><td style='text-align:right;'>{$amount}</td></tr>";
        }
        $earningsHtml .= "<tr style='font-weight:bold; background-color:#f0f9ff;'><td>TOTAL GAINS</td><td style='text-align:right;'>" . number_format($totalEarnings, 2, ',', ' ') . " €</td></tr>";

        // 4. Construire le HTML des lignes de déductions
        $deductionsHtml = '';
        foreach ($deductionLines as $line) {
            $amount = number_format($line->amount, 2, ',', ' ') . ' €';
            $desc = $line->description ? " <small style='color:#666'>({$line->description})</small>" : '';
            $deductionsHtml .= "<tr><td>{$line->payrollItem->name}{$desc}</td><td style='text-align:right;'>-{$amount}</td></tr>";
        }
        $deductionsHtml .= "<tr style='font-weight:bold; background-color:#fef2f2;'><td>TOTAL DÉDUCTIONS</td><td style='text-align:right;'>-" . number_format($totalDeductions, 2, ',', ' ') . " €</td></tr>";

        // 5. Remplacer les variables dans le template
        $htmlContent = $template->content;
        $htmlContent = str_replace('{{employee_name}}', $employee->full_name, $htmlContent);
        $htmlContent = str_replace('{{matricule}}', $employee->matricule, $htmlContent);
        $htmlContent = str_replace('{{period}}', $period->label ?? $period->month . '/' . $period->year, $htmlContent);

        // Variables de congé
        $htmlContent = str_replace('{{leaves_taken}}', number_format($leavesTaken, 2, ',', ' '), $htmlContent);
        $htmlContent = str_replace('{{leaves_remaining}}', number_format($leavesRemaining, 2, ',', ' '), $htmlContent);

        // Variables financières globales
        $htmlContent = str_replace('{{gross_salary}}', number_format($payslip->gross_salary, 2, ',', ' ') . ' €', $htmlContent);
        $htmlContent = str_replace('{{deductions}}', number_format($payslip->total_deductions, 2, ',', ' ') . ' €', $htmlContent);
        $htmlContent = str_replace('{{net_salary}}', number_format($payslip->net_salary, 2, ',', ' ') . ' €', $htmlContent);

        // Injection des tableaux de lignes
        $htmlContent = str_replace('{{earnings_lines}}', $earningsHtml, $htmlContent);
        $htmlContent = str_replace('{{deductions_lines}}', $deductionsHtml, $htmlContent);

        // 6. Générer le PDF

        $pdf = Pdf::loadHTML($htmlContent)
                  ->setPaper('a4', 'portrait')
                  ->setOption('margin-top', 10)
                  ->setOption('margin-bottom', 10);

        // 7. Sauvegarder le fichier (avec padding du mois pour avoir "09" au lieu de "9")
        $monthPadded = str_pad($period->month, 2, '0', STR_PAD_LEFT);
        $fileName = 'bulletins/bulletin_' . $employee->matricule . '_' . $period->year . '_' . $monthPadded . '.pdf';

        if (Schema::hasColumn('payslips', 'file_path')) {
            Storage::disk('public')->put($fileName, $pdf->output());
            $payslip->update(['file_path' => $fileName]);
        }

        // 8. Retourner le PDF en téléchargement
        return $pdf->download('Bulletin_' . $employee->matricule . '_' . $monthPadded . '_' . $period->year . '.pdf');
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

        if ($period->status === 'closed' || $period->status === 'archived' || $period->status === 'break') {
            return response()->json([
                'message' => 'Impossible de générer un bulletin sur une période verrouillée.',
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

        /**
     * Téléchargement du bulletin via le lien signé de l'email
     */
    public function downloadSigned(\App\Models\Payslip $payslip)
    {
        $payslip->load(['employee', 'payPeriod', 'lines.payrollItem']);

        $monthPadded = str_pad((string) $payslip->payPeriod->month, 2, '0', STR_PAD_LEFT);
        $downloadFileName = 'Bulletin_' . $payslip->employee->matricule . '_' . $monthPadded . '_' . $payslip->payPeriod->year . '.pdf';

        if (!empty($payslip->file_path) && Storage::disk('public')->exists($payslip->file_path)) {
            return Storage::disk('public')->download($payslip->file_path, $downloadFileName);
        }

        return $this->download($payslip);
    }
}
