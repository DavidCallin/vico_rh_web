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
     * Appelée par le bouton "Réouvrir" du frontend
     */
    public function open(PayPeriod $payPeriod)
    {
        if ($payPeriod->status === 'open') {
            return response()->json(['message' => 'Cette période est déjà ouverte.'], 400);
        }

        if ($payPeriod->status === 'archived') {
            return response()->json(['message' => 'Impossible de réouvrir une période archivée.'], 400);
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
     * 🎯 MÉTHODE PRIVÉE : Générer le PDF d'un bulletin avec le template
     */
    private function generatePayslipPdf(Payslip $payslip): void
    {
        // Vérifier que la colonne file_path existe dans la table payslips
        if (!\Schema::hasColumn('payslips', 'file_path')) {
            return;
        }

        // Récupérer le template par défaut
        $template = PayslipTemplate::where('is_default', true)->first() 
                    ?? PayslipTemplate::first();

        if (!$template) {
            return; // Pas de template configuré, on ignore
        }

        $payslip->load(['employee', 'payPeriod', 'lines.payrollItem']);
        
        $employee = $payslip->employee;
        $period = $payslip->payPeriod;
        
        // Calcul du salaire de base
        $baseSalary = $employee->contracts()->latest('start_date')->first()?->base_salary ?? 0;

        // Remplacer les variables dans le template HTML
        $htmlContent = $template->content;
        $htmlContent = str_replace('{{employee_name}}', $employee->full_name, $htmlContent);
        $htmlContent = str_replace('{{matricule}}', $employee->matricule, $htmlContent);
        $htmlContent = str_replace('{{period}}', $period->label ?? $period->month . '/' . $period->year, $htmlContent);
        $htmlContent = str_replace('{{gross_salary}}', number_format($payslip->gross_salary, 2, ',', ' ') . ' €', $htmlContent);
        $htmlContent = str_replace('{{deductions}}', number_format($payslip->total_deductions, 2, ',', ' ') . ' €', $htmlContent);
        $htmlContent = str_replace('{{net_salary}}', number_format($payslip->net_salary, 2, ',', ' ') . ' €', $htmlContent);
        $htmlContent = str_replace('{{base_salary}}', number_format($baseSalary, 2, ',', ' ') . ' €', $htmlContent);
        $htmlContent = str_replace('{{transport_allowance}}', '0,00 €', $htmlContent);

        // Générer le PDF
        $pdf = Pdf::loadHTML($htmlContent)
                  ->setPaper('a4', 'portrait');

        // Sauvegarder
        $fileName = 'bulletins/bulletin_' . $employee->matricule . '_' . $period->year . '_' . str_pad($period->month, 2, '0', STR_PAD_LEFT) . '.pdf';
        Storage::disk('public')->put($fileName, $pdf->output());

        // Mettre à jour le chemin dans la base
        $payslip->update(['file_path' => $fileName]);
    }
}