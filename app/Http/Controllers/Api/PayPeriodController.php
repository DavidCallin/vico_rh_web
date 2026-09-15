<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PayPeriodResource;
use App\Models\PayPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Mail\PayslipAvailableMail;
use App\Models\Employee;
use App\Models\Setting;
use App\Services\PayslipGeneratorService;
use Illuminate\Support\Facades\Mail;

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
     * Ouvrir une nouvelle période de paie
     */
    public function open(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2100',
        ]);

        // Vérifier qu'elle n'existe pas déjà
        $exists = PayPeriod::where('month', $request->month)
            ->where('year', $request->year)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Cette période de paie existe déjà.',
            ], 409);
        }

        $period = PayPeriod::create([
            'month' => $request->month,
            'year' => $request->year,
            'status' => 'open',
            'opening_date' => now(),
        ]);

        return new PayPeriodResource($period);
    }

        /**
     * Clôturer une période de paie (avec génération auto des bulletins)
     */
    public function close(PayPeriod $payPeriod)
    {
        if ($payPeriod->status === 'closed') {
            return response()->json(['message' => 'Cette période est déjà clôturée.'], 400);
        }

        if ($payPeriod->status === 'archived') {
            return response()->json(['message' => 'Cette période est archivée.'], 400);
        }

        // 1. Récupérer tous les employés actifs ayant un contrat sur cette période
        $activeEmployees = Employee::where('status', 'active')
            ->whereHas('contracts', function ($query) use ($payPeriod) {
                $query->where('start_date', '<=', $payPeriod->closing_date ?? now())
                      ->where(function ($q) use ($payPeriod) {
                          $q->whereNull('end_date')
                            ->orWhere('end_date', '>=', $payPeriod->opening_date);
                      });
            })
            ->with('user') // Pour l'envoi d'email
            ->get();

        $generatedCount = 0;
        $errors = [];

        // 2. Générer les bulletins pour chaque employé
        foreach ($activeEmployees as $employee) {
            try {
                // Tente de générer le bulletin (le service gère déjà la vérification "existe déjà")
                $payslip = $this->generator->generate($employee, $payPeriod);
                $generatedCount++;

                // 3. Envoyer l'email si l'option est activée dans les settings
                if (Setting::get('payslip_auto_email', false) && $employee->user) {
                    Mail::to($employee->user->email)->send(new PayslipAvailableMail($payslip));
                }
            } catch (\Exception $e) {
                // On capture l'erreur pour ne pas bloquer les autres employés, mais on la log
                $errors[] = "Employé {$employee->full_name} : " . $e->getMessage();
            }
        }

        // 4. Clôturer officiellement la période
        $payPeriod->update([
            'status' => 'closed',
            'closing_date' => now(),
            'closure_type' => 'normal',
            'closed_by' => auth()->id(),
        ]);

        return response()->json([
            'message' => "Période clôturée avec succès. {$generatedCount} bulletin(s) généré(s).",
            'generated_count' => $generatedCount,
            'errors' => $errors, // Utile pour le debug : ex: "Aucun contrat actif trouvé"
            'pay_period' => new \App\Http\Resources\PayPeriodResource($payPeriod->load('closedByUser')),
        ]);
    }
}