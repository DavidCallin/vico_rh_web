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
        \Illuminate\Support\Facades\Log::info("🔍 Début génération bulletins pour période {$payPeriod->id}");

        if ($payPeriod->status !== 'open') {
            return response()->json([
                'message' => 'Impossible de générer des bulletins : la période n\'est pas ouverte.'
            ], 400);
        }

        try {
            // 1. ✅ SUPPRIMER LES ANCIENS BULLETINS ET LEURS FICHIERS PDF
            $existingPayslips = \App\Models\Payslip::where('pay_period_id', $payPeriod->id)->get();
            foreach ($existingPayslips as $oldPayslip) {
                if ($oldPayslip->file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($oldPayslip->file_path)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPayslip->file_path);
                }
            }
            $deletedCount = \App\Models\Payslip::where('pay_period_id', $payPeriod->id)->delete();
            \Illuminate\Support\Facades\Log::info("🗑️ {$deletedCount} ancien(s) bulletin(s) et fichiers supprimés");

            // 2. ✅ RÉCUPÉRER LE TEMPLATE PAR DÉFAUT (comme dans ta méthode download)
            $template = \App\Models\PayslipTemplate::where('is_default', true)->first() 
                        ?? \App\Models\PayslipTemplate::first();

            if (!$template) {
                return response()->json(['message' => 'Aucun template de bulletin configuré.'], 404);
            }

            // 3. ✅ GÉNÉRER LES NOUVEAUX BULLETINS + PDF
            $employees = \App\Models\Employee::whereIn('status', ['active', 'trial'])
                ->with('position', 'category')
                ->get();
            
            \Illuminate\Support\Facades\Log::info("👥 {$employees->count()} employé(s) trouvé(s) pour la génération");
            
            $generatedCount = 0;
            $errorCount = 0;

            foreach ($employees as $employee) {
                try {
                    $baseSalary = $employee->position ? ($employee->position->base_salary ?? 0) : 0;

                    // A. Créer l'enregistrement en base
                    $payslip = \App\Models\Payslip::create([
                        'employee_id' => $employee->id,
                        'pay_period_id' => $payPeriod->id,
                        'gross_salary' => $baseSalary,
                        'net_salary' => $baseSalary,
                        'total_earnings' => $baseSalary,
                        'total_deductions' => 0,
                        'generation_date' => now(),
                    ]);

                    // B. Générer le PDF (Logique extraite de ta méthode download)
                    $payslip->load(['employee', 'payPeriod', 'lines.payrollItem']);
                    
                    $currentYear = now()->year;
                    $leaveBalance = \App\Models\LeaveBalance::where('employee_id', $employee->id)->where('year', $currentYear)->first();
                    $leavesTaken = $leaveBalance ? $leaveBalance->taken_days : 0;
                    $leavesRemaining = $leaveBalance ? $leaveBalance->remaining_days : 0;

                    $earningLines = $payslip->lines ? $payslip->lines->filter(fn($l) => $l->payrollItem && $l->payrollItem->type === 'earning') : collect();
                    $deductionLines = $payslip->lines ? $payslip->lines->filter(fn($l) => $l->payrollItem && $l->payrollItem->type === 'deduction') : collect();

                    $totalEarnings = $earningLines->sum('amount');
                    $totalDeductions = $deductionLines->sum('amount');

                    $earningsHtml = '';
                    foreach ($earningLines as $line) {
                        $amount = number_format($line->amount, 2, ',', ' ') . ' €';
                        $desc = $line->description ? " <small style='color:#666'>({$line->description})</small>" : '';
                        $earningsHtml .= "<tr><td>{$line->payrollItem->name}{$desc}</td><td style='text-align:right;'>{$amount}</td></tr>";
                    }
                    $earningsHtml .= "<tr style='font-weight:bold; background-color:#f0f9ff;'><td>TOTAL GAINS</td><td style='text-align:right;'>" . number_format($totalEarnings, 2, ',', ' ') . " €</td></tr>";

                    $deductionsHtml = '';
                    foreach ($deductionLines as $line) {
                        $amount = number_format($line->amount, 2, ',', ' ') . ' €';
                        $desc = $line->description ? " <small style='color:#666'>({$line->description})</small>" : '';
                        $deductionsHtml .= "<tr><td>{$line->payrollItem->name}{$desc}</td><td style='text-align:right;'>-{$amount}</td></tr>";
                    }
                    $deductionsHtml .= "<tr style='font-weight:bold; background-color:#fef2f2;'><td>TOTAL DÉDUCTIONS</td><td style='text-align:right;'>-" . number_format($totalDeductions, 2, ',', ' ') . " €</td></tr>";

                    $htmlContent = $template->content;
                    $htmlContent = str_replace('{{employee_name}}', $employee->full_name, $htmlContent);
                    $htmlContent = str_replace('{{matricule}}', $employee->matricule, $htmlContent);
                    $htmlContent = str_replace('{{period}}', $payPeriod->label ?? ($payPeriod->month . '/' . $payPeriod->year), $htmlContent);
                    $htmlContent = str_replace('{{leaves_taken}}', number_format($leavesTaken, 2, ',', ' '), $htmlContent);
                    $htmlContent = str_replace('{{leaves_remaining}}', number_format($leavesRemaining, 2, ',', ' '), $htmlContent);
                    $htmlContent = str_replace('{{gross_salary}}', number_format($payslip->gross_salary, 2, ',', ' ') . ' €', $htmlContent);
                    $htmlContent = str_replace('{{deductions}}', number_format($payslip->total_deductions, 2, ',', ' ') . ' €', $htmlContent);
                    $htmlContent = str_replace('{{net_salary}}', number_format($payslip->net_salary, 2, ',', ' ') . ' €', $htmlContent);
                    $htmlContent = str_replace('{{earnings_lines}}', $earningsHtml, $htmlContent);
                    $htmlContent = str_replace('{{deductions_lines}}', $deductionsHtml, $htmlContent);

                    // Génération et sauvegarde du PDF
                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($htmlContent)
                        ->setPaper('a4', 'portrait')
                        ->setOption('margin-top', 10)
                        ->setOption('margin-bottom', 10);

                    $monthPadded = str_pad($payPeriod->month, 2, '0', STR_PAD_LEFT);
                    $fileName = 'bulletins/bulletin_' . $employee->matricule . '_' . $payPeriod->year . '_' . $monthPadded . '.pdf';
                    
                    // ✅ SAUVEGARDE DANS LE STORAGE
                    \Illuminate\Support\Facades\Storage::disk('public')->put($fileName, $pdf->output());
                    $payslip->update(['file_path' => $fileName]);

                    $generatedCount++;
                } catch (\Throwable $e) {
                    $errorCount++;
                    \Illuminate\Support\Facades\Log::error("❌ Erreur création bulletin pour employé {$employee->id} : " . $e->getMessage());
                }
            }

            \Illuminate\Support\Facades\Log::info("✅ Génération terminée : {$generatedCount} créé(s), {$errorCount} erreur(s)");

            return response()->json([
                'message' => "{$generatedCount} bulletin(s) généré(s) avec PDF." . ($errorCount > 0 ? " ({$errorCount} échec(s))." : ""),
                'generated_count' => $generatedCount,
                'error_count' => $errorCount,
            ]);

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('❌ Erreur critique génération bulletins : ' . $e->getMessage());
            return response()->json([
                'message' => 'Erreur lors de la génération.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

        /**
     * ✅ NOUVELLE MÉTHODE : Envoie les bulletins par email aux employés
     */
    public function sendPayslipsByEmail(PayPeriod $payPeriod)
    {
        try {
            // 1. Récupérer les bulletins de la période
            $payslips = \App\Models\Payslip::where('pay_period_id', $payPeriod->id)
                ->with('employee', 'payPeriod')
                ->get();

            if ($payslips->isEmpty()) {
                return response()->json([
                    'message' => 'Aucun bulletin à envoyer. Veuillez d\'abord générer les bulletins.'
                ], 400);
            }

            $emailSentCount = 0;
            $emailFailedCount = 0;

            foreach ($payslips as $payslip) {
                $employee = $payslip->employee;

                // ✅ Utilise l'email de la table employees
                if (!$employee || !$employee->email) {
                    \Illuminate\Support\Facades\Log::warning("⚠️ Employé sans email : {$payslip->id}");
                    $emailFailedCount++;
                    continue;
                }

                try {
                    // Générer le lien de téléchargement sécurisé (valable 30 jours)
                    $downloadUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                        'payslip.download',
                        now()->addDays(30),
                        ['payslip' => $payslip->id]
                    );

                    // Construire le sujet personnalisé
                    $subjectTemplate = \App\Models\Setting::get('payslip_email_subject', 'Votre bulletin de paie du {month} {year}');
                    $monthNames = [
                        1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
                        5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
                        9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre'
                    ];
                    $monthName = $monthNames[$payPeriod->month] ?? $payPeriod->month;
                    
                    $subject = str_replace(
                        ['{month}', '{year}', '{employee_name}'],
                        [$monthName, $payPeriod->year, $employee->full_name],
                        $subjectTemplate
                    );

                    // Envoyer l'email
                    \Illuminate\Support\Facades\Mail::send('emails.payslip-available', [
                        'payslip' => $payslip,
                        'downloadUrl' => $downloadUrl,
                    ], function ($mail) use ($employee, $subject) {
                        $mail->to($employee->email)
                             ->subject('[VICO RH] ' . $subject);
                    });

                    $emailSentCount++;
                    \Illuminate\Support\Facades\Log::info("✅ Bulletin envoyé à {$employee->email}");

                } catch (\Throwable $e) {
                    $emailFailedCount++;
                    \Illuminate\Support\Facades\Log::error("❌ Erreur envoi à {$employee->email} : " . $e->getMessage());
                }
            }

            return response()->json([
                'message' => "{$emailSentCount} email(s) envoyé(s) avec succès." . 
                            ($emailFailedCount > 0 ? " {$emailFailedCount} échec(s)." : ""),
                'emails_sent' => $emailSentCount,
                'emails_failed' => $emailFailedCount,
            ]);

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Erreur envoi emails bulletins : ' . $e->getMessage());
            return response()->json([
                'message' => 'Erreur lors de l\'envoi des emails.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * ✅ FERMER la période (état "break")
     * - Verrouille tout (génération, modification, téléchargement)
     * - RÉVERSIBLE : peut être rouverte
     */
    public function break(PayPeriod $payPeriod)
    {
        if ($payPeriod->status !== 'open') {
            return response()->json([
                'message' => 'Seules les périodes ouvertes peuvent être fermées.'
            ], 400);
        }

        $payPeriod->update(['status' => 'break']);

        return response()->json([
            'message' => 'Période fermée. Toutes les actions sont verrouillées. Vous pouvez la rouvrir si nécessaire.',
            'data' => new \App\Http\Resources\PayPeriodResource($payPeriod)
        ]);
    }

    /**
     * ✅ ROUVRIR une période fermée (break → open)
     */
    public function reopen(PayPeriod $payPeriod)
    {
        if ($payPeriod->status !== 'break') {
            return response()->json([
                'message' => 'Seules les périodes fermées (break) peuvent être rouvertes.'
            ], 400);
        }

        $payPeriod->update(['status' => 'open']);

        return response()->json([
            'message' => 'Période rouverte. Vous pouvez à nouveau générer et modifier les bulletins.',
            'data' => new \App\Http\Resources\PayPeriodResource($payPeriod)
        ]);
    }

    /**
     * 🎯 CLÔTURER la période (peut générer les bulletins manquants si besoin)
     * Appelée par le bouton "Clôturer" du frontend
     */
    public function close(PayPeriod $payPeriod)
    {
        if ($payPeriod->status !== 'break') {
            return response()->json([
                'message' => 'Seules les périodes fermées (break) peuvent être clôturées définitivement.'
            ], 400);
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
        // On ne peut ouvrir qu'une période en brouillon ou fermée (break)
        if (!in_array($payPeriod->status, ['draft', 'break'])) {
            return response()->json([
                'message' => 'Seules les périodes en brouillon ou fermées peuvent être ouvertes.'
            ], 400);
        }

        // ✅ VÉRIFIER QU'AUCUNE AUTRE PÉRIODE N'EST OUVERTE
         \App\Models\PayPeriod::where('status', 'open')
            ->where('id', '!=', $payPeriod->id)
            ->update(['status' => 'draft']);

        $payPeriod->update([
            'status' => 'open',
            'closing_date' => null,
            'closure_type' => null,
            'closed_by' => null,
        ]);

        return response()->json([
            'message' => 'Période ouverte avec succès. Les autres périodes ont été remises en brouillon.',
            'data' => new \App\Http\Resources\PayPeriodResource($payPeriod)
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