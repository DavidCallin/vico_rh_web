<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PayPeriodResource;
use App\Models\PayPeriod;
use App\Models\Payslip;
use App\Models\PayslipTemplate;
use App\Models\Employee;
use App\Models\Setting;
use App\Services\PayslipGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Mail\PayslipAvailableMail;
use Barryvdh\DomPDF\Facade\Pdf;

class PayPeriodController extends Controller
{
    protected PayslipGeneratorService $generator;

    public function __construct(PayslipGeneratorService $generator)
    {
        $this->generator = $generator;
    }

    /**
     * Liste des périodes de paie
     */
    public function index(Request $request)
    {
        $query = PayPeriod::with('closedByUser');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('year')) {
            $query->where('year', $request->year);
        }

        $periods = $query->orderBy('year', 'desc')
                        ->orderBy('month', 'desc')
                        ->paginate(12);

        return PayPeriodResource::collection($periods);
    }

    /**
     * Afficher une période
     */
    public function show(PayPeriod $payPeriod)
    {
        $payPeriod->load(['closedByUser', 'payslips.employee']);
        return new PayPeriodResource($payPeriod);
    }

        /**
     * 🎯 CRÉER une nouvelle période de paie
     * Vérifie qu'aucune période n'est déjà ouverte
     */
    public function store(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2100',
        ]);

        // ✅ VÉRIFIER QU'AUCUNE PÉRIODE N'EST DÉJÀ OUVERTE
        $openPeriod = PayPeriod::where('status', 'open')->first();
        if ($openPeriod) {
            return response()->json([
                'message' => "Impossible de créer une nouvelle période. La période '{$openPeriod->label}' est déjà ouverte. Vous devez d'abord la clôturer.",
            ], 409);
        }

        // Vérifier qu'elle n'existe pas déjà
        $exists = PayPeriod::where('month', $request->month)
            ->where('year', $request->year)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Cette période de paie existe déjà.',
            ], 409);
        }

        // Calculer les dates de début et fin
        $startDate = \Carbon\Carbon::createFromDate($request->year, $request->month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Générer le label (ex: "Janvier 2026")
        $label = $startDate->locale('fr')->isoFormat('MMMM YYYY');

        $period = PayPeriod::create([
            'month' => $request->month,
            'year' => $request->year,
            'label' => ucfirst($label),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'open',
            'opening_date' => now(),
        ]);

        return response()->json([
            'message' => 'Période de paie créée avec succès.',
            'data' => new PayPeriodResource($period),
        ], 201);
    }

    /**
     * 🎯 GÉNÉRER LES BULLETINS (SANS clôturer la période)
     * Appelée par le bouton "Générer" du frontend
     */
    public function generatePayslips(PayPeriod $payPeriod)
    {
        if ($payPeriod->status === 'archived') {
            return response()->json([
                'message' => 'Impossible de générer sur une période archivée.',
            ], 400);
        }

        $result = $this->processPayslipGeneration($payPeriod);

        return response()->json([
            'message' => "{$result['generated_count']} bulletin(s) généré(s) avec succès.",
            'generated_count' => $result['generated_count'],
            'errors' => $result['errors'],
        ]);
    }

    /**
     * 🎯 CLÔTURER la période (peut générer les bulletins manquants si besoin)
     * Appelée par le bouton "Clôturer" du frontend
     */
    public function close(PayPeriod $payPeriod)
    {
        if ($payPeriod->status === 'closed') {
            return response()->json(['message' => 'Cette période est déjà clôturée.'], 400);
        }

        if ($payPeriod->status === 'archived') {
            return response()->json(['message' => 'Cette période est archivée.'], 400);
        }

        // Générer les bulletins manquants avant de clôturer
        $result = $this->processPayslipGeneration($payPeriod);

        // Clôturer officiellement
        $payPeriod->update([
            'status' => 'closed',
            'closing_date' => now(),
            'closure_type' => 'normal',
            'closed_by' => auth()->id(),
        ]);

        return response()->json([
            'message' => "Période clôturée avec succès. {$result['generated_count']} bulletin(s) généré(s).",
            'generated_count' => $result['generated_count'],
            'errors' => $result['errors'],
            'pay_period' => new PayPeriodResource($payPeriod->load('closedByUser')),
        ]);
    }

        /**
     * 🎯 RÉOUVRIR une période clôturée
     * Vérifie qu'aucune autre période n'est déjà ouverte
     */
    public function open(PayPeriod $payPeriod)
    {
        if ($payPeriod->status === 'open') {
            return response()->json(['message' => 'Cette période est déjà ouverte.'], 400);
        }

        if ($payPeriod->status === 'archived') {
            return response()->json(['message' => 'Impossible de réouvrir une période archivée.'], 400);
        }

        // ✅ VÉRIFIER QU'AUCUNE AUTRE PÉRIODE N'EST OUVERTE
        $openPeriod = PayPeriod::where('status', 'open')->first();
        if ($openPeriod && $openPeriod->id !== $payPeriod->id) {
            return response()->json([
                'message' => "Impossible de réouvrir cette période. La période '{$openPeriod->label}' est déjà ouverte. Vous devez d'abord la clôturer.",
            ], 409);
        }

        $payPeriod->update([
            'status' => 'open',
            'closing_date' => null,
            'closure_type' => null,
            'closed_by' => null,
        ]);

        return response()->json([
            'message' => 'Période réouverte avec succès.',
            'pay_period' => new PayPeriodResource($payPeriod->load('closedByUser')),
        ]);
    }

    /**
     * 🛠️ MÉTHODE PRIVÉE : Logique commune de génération des bulletins
     * Utilisée à la fois par generatePayslips() et close()
     */
    private function processPayslipGeneration(PayPeriod $payPeriod): array
    {
        // 1. Récupérer tous les employés actifs ayant un contrat sur cette période
        $activeEmployees = Employee::where('status', 'active')
            ->whereHas('contracts', function ($query) use ($payPeriod) {
                $closingDate = $payPeriod->closing_date ?? now();
                $openingDate = $payPeriod->opening_date ?? now()->startOfMonth();
                $query->where('start_date', '<=', $closingDate)
                      ->where(function ($q) use ($openingDate) {
                          $q->whereNull('end_date')
                            ->orWhere('end_date', '>=', $openingDate);
                      });
            })
            ->with('user')
            ->get();

        $generatedCount = 0;
        $errors = [];

        // 2. Générer les bulletins pour chaque employé
        foreach ($activeEmployees as $employee) {
            try {
                // Vérifier si le bulletin existe déjà
                $exists = Payslip::where('employee_id', $employee->id)
                    ->where('pay_period_id', $payPeriod->id)
                    ->exists();

                if ($exists) {
                    continue; // Déjà généré, on passe
                }

                $payslip = $this->generator->generate($employee, $payPeriod);
                $generatedCount++;

                // 🎯 GÉNÉRER AUTOMATIQUEMENT LE PDF AVEC LE TEMPLATE
                $this->generatePayslipPdf($payslip);

                // 3. Envoyer l'email si activé dans les settings
                if (Setting::get('payslip_auto_email', false) && $employee->user) {
                    Mail::to($employee->user->email)->send(new PayslipAvailableMail($payslip));
                }
            } catch (\Exception $e) {
                $errors[] = "Employé {$employee->full_name} : " . $e->getMessage();
            }
        }

        return [
            'generated_count' => $generatedCount,
            'errors' => $errors,
        ];
    }

        /**
     * 🎯 MÉTHODE PRIVÉE : Générer le PDF d'un bulletin avec regroupement des rubriques
     */
    private function generatePayslipPdf(Payslip $payslip): void
    {
        if (!\Schema::hasColumn('payslips', 'file_path')) {
            return;
        }

        $template = \App\Models\PayslipTemplate::where('is_default', true)->first() 
                    ?? \App\Models\PayslipTemplate::first();

        if (!$template) {
            return;
        }

        $payslip->load(['employee', 'payPeriod', 'lines.payrollItem']);
        
        $employee = $payslip->employee;
        $period = $payslip->payPeriod;
        
        // 1. Récupérer le solde de congé de l'année en cours
        $currentYear = now()->year;
        $leaveBalance = \App\Models\LeaveBalance::where('employee_id', $employee->id)
            ->where('year', $currentYear)
            ->first();
        
        $leavesTaken = $leaveBalance ? $leaveBalance->taken_days : 0;
        $leavesRemaining = $leaveBalance ? $leaveBalance->remaining_days : 0;

        // 2. Regrouper les lignes par type
        $earningLines = $payslip->lines->filter(fn($l) => $l->payrollItem->type === 'earning');
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

        // Variables financières
        $htmlContent = str_replace('{{gross_salary}}', number_format($payslip->gross_salary, 2, ',', ' ') . ' €', $htmlContent);
        $htmlContent = str_replace('{{deductions}}', number_format($payslip->total_deductions, 2, ',', ' ') . ' €', $htmlContent);
        $htmlContent = str_replace('{{net_salary}}', number_format($payslip->net_salary, 2, ',', ' ') . ' €', $htmlContent);
        
        // Injection des tableaux de lignes
        $htmlContent = str_replace('{{earnings_lines}}', $earningsHtml, $htmlContent);
        $htmlContent = str_replace('{{deductions_lines}}', $deductionsHtml, $htmlContent);

        // 6. Générer et sauvegarder le PDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($htmlContent)
                  ->setPaper('a4', 'portrait');

        $fileName = 'bulletins/bulletin_' . $employee->matricule . '_' . $period->year . '_' . str_pad($period->month, 2, '0', STR_PAD_LEFT) . '.pdf';
        \Illuminate\Support\Facades\Storage::disk('public')->put($fileName, $pdf->output());

        $payslip->update(['file_path' => $fileName]);
    }
}