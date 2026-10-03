<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
    /**
     * Liste tous les employés (avec pagination)
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Employee::with(['position.department', 'category']);

        // Filtre par statut si fourni
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filtre par département si fourni
        if ($request->has('department_id')) {
            $query->whereHas('position', function ($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        // Recherche par nom ou matricule
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('matricule', 'like', "%{$search}%");
            });
        }

        $employees = $query->orderBy('last_name')->paginate(15);

        return EmployeeResource::collection($employees);
    }

    /**
     * Affiche un employé spécifique
     */
    public function show(Employee $employee): EmployeeResource
    {
        $employee->load(['position.department', 'category', 'contracts', 'leaves']);

        return new EmployeeResource($employee);
    }

    /**
     * Crée un nouvel employé
     */
    public function store(StoreEmployeeRequest $request): EmployeeResource
    {
        $employee = Employee::create($request->validated());

        $this->createUserForEmployee($employee);

        return new EmployeeResource($employee->load(['position.department', 'category', 'user']));
    }

    /**
     * Crée automatiquement un compte utilisateur pour l'employé
     */
    private function createUserForEmployee($employee): void
    {
        Log::info("🔍 Début création compte utilisateur pour : {$employee->full_name}");

        try {
            // 1. Vérifier si un utilisateur avec cet email existe déjà
            $existingUser = User::where('email', $employee->email)->first();

            if ($existingUser) {
                Log::info("⚠️ Un utilisateur existe déjà avec cet email : {$employee->email}");

                if (! $existingUser->employee_id) {
                    $existingUser->update([
                        'employee_id' => $employee->id,
                        'login' => $existingUser->login ?? $employee->email, // ✅ Sécurise le login
                    ]);
                    Log::info("✅ Utilisateur existant lié à l'employé {$employee->full_name}");
                }

                return;
            }

            // 2. Générer un mot de passe temporaire
            $temporaryPassword = Str::random(8);

            // 3. Créer l'utilisateur
            $user = User::create([
                'name' => $employee->full_name,
                'email' => $employee->email,
                'login' => $employee->email, // ✅ AJOUTÉ : Utilise l'email comme login (ou $employee->matricule)
                'password' => Hash::make($temporaryPassword),
                'employee_id' => $employee->id,
            ]);

            Log::info("✅ User créé avec ID : {$user->id}");

            // 4. Assigner le rôle par défaut "employee"
            try {
                $user->assignRole('employee');
                Log::info("✅ Rôle 'employee' assigné");
            } catch (\Throwable $e) {
                Log::warning("⚠️ Rôle 'employee' non trouvé : ".$e->getMessage());
            }

            // 5. Envoyer l'email de bienvenue
            try {
                $this->sendWelcomeEmail($user, $temporaryPassword);
            } catch (\Throwable $e) {
                Log::warning('⚠️ Email de bienvenue non envoyé : '.$e->getMessage());
            }

            Log::info("✅✅✅ Compte utilisateur créé AVEC SUCCÈS pour {$employee->full_name}");

        } catch (\Throwable $e) {
            Log::error('❌❌❌ ERREUR CRITIQUE création compte utilisateur : '.$e->getMessage(), [
                'employee_id' => $employee->id,
                'employee_name' => $employee->full_name,
            ]);
        }
    }

    /**
     * ✅ Envoie un email de bienvenue avec les identifiants temporaires
     */
    private function sendWelcomeEmail($user, string $temporaryPassword): void
    {
        try {
            Mail::send('emails.welcome', [
                'user' => $user,
                'temporaryPassword' => $temporaryPassword,
            ], function ($mail) use ($user) {
                $mail->to($user->email)
                    ->subject('[VICO RH] Bienvenue - Vos identifiants de connexion');
            });

            Log::info("✅ Email de bienvenue envoyé à {$user->email}");
        } catch (\Throwable $e) {
            Log::error('❌ Erreur envoi email bienvenue : '.$e->getMessage());
        }
    }

    /**
     * Met à jour un employé existant
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee): EmployeeResource
    {
        $employee->update($request->validated());

        return new EmployeeResource($employee->load(['position.department', 'category']));
    }

    /**
     * Supprime un employé
     */
    public function destroy(Employee $employee): Response
    {
        $employee->delete();

        return response()->noContent();
    }
}
