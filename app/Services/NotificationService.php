<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
        /**
     * Crée une notification in-app + envoie un email si activé
     */
    public function send(
        User $user,
        string $type,
        string $title,
        string $message,
        ?string $link = null,
        ?array $data = null,
        bool $sendEmail = true
    ): Notification {
        // 1. Créer la notification in-app
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'data' => $data,
            'is_read' => false,
            'email_sent' => false,
        ]);

        // 2. Vérifier si l'envoi d'email est activé
        $emailEnabled = Setting::get('notification_email_enabled', true);
        
        if (!$sendEmail || !$emailEnabled || empty($user->email)) {
            return $notification;
        }

        // 3. Déterminer le template et les données à utiliser
        $template = null;
        $viewData = ['user' => $user];

        switch ($type) {
            case 'leave_request':
                // Pour les demandes de congé, on utilise leave-requested
                if (isset($data['leave'])) {
                    $viewData['leave'] = $data['leave'];
                    $template = 'emails.leave-requested';
                }
                break;

            case 'leave_approved':
            case 'leave_rejected':
                // Pour les statuts de congé
                if (isset($data['leave'])) {
                    $viewData['leave'] = $data['leave'];
                    $viewData['status'] = str_replace('leave_', '', $type);
                    $template = 'emails.leave-status-changed';
                }
                break;

            case 'contract_expiring':
                // Pour les contrats qui expirent bientôt
                if (isset($data['contract']) && isset($data['days_remaining'])) {
                    $viewData['contract'] = $data['contract'];
                    $viewData['daysRemaining'] = $data['days_remaining'];
                    $template = 'emails.contract-expiring';
                }
                break;

            case 'contract_expired':
                // Pour les contrats expirés
                if (isset($data['contract'])) {
                    $viewData['contract'] = $data['contract'];
                    $template = 'emails.contract-expired';
                }
                break;

            case 'payslip_available':
                // Pour les bulletins disponibles
                if (isset($data['payslip'])) {
                    $viewData['payslip'] = $data['payslip'];
                    $template = 'emails.payslip-available';
                }
                break;

            default:
                // Template générique pour les autres notifications
                $viewData['title'] = $title;
                $viewData['message'] = $message;
                $viewData['link'] = $link;
                $template = 'emails.notification';
                break;
        }

        // 4. Envoyer l'email
        try {
            if ($template && \Illuminate\Support\Facades\View::exists($template)) {
                \Illuminate\Support\Facades\Mail::send($template, $viewData, function ($mail) use ($user, $title) {
                    $mail->to($user->email)
                         ->subject('[VICO RH] ' . $title);
                });
                $notification->update(['email_sent' => true]);
                \Log::info("✅ Email envoyé à {$user->email} avec le template {$template}");
            } else {
                \Log::warning("⚠️ Template {$template} introuvable, email non envoyé");
            }
        } catch (\Throwable $e) {
            \Log::error("❌ Erreur envoi email : " . $e->getMessage(), [
                'template' => $template,
                'user' => $user->email,
            ]);
        }

        return $notification;
    }

    /**
     * Notifie les managers/RH d'une nouvelle demande de congé
     */
    public function notifyNewLeaveRequest($leave): void
    {
        try {
            // 1. Charger les relations de manière sécurisée
            $leave->load(['employee', 'validator']);
            
            $employee = $leave->employee;
            if (!$employee) {
                \Illuminate\Support\Facades\Log::warning("Notification: Employé introuvable pour le congé #{$leave->id}");
                return;
            }

            // 2. Récupérer les managers
            $managers = $this->getManagersToNotify($employee);

            // 3. Envoyer les notifications
            foreach ($managers as $manager) {
                $this->send(
                    user: $manager,
                    type: 'leave_request',
                    title: 'Nouvelle demande de congé',
                    message: "Une nouvelle demande de congé a été soumise par {$employee->full_name}.",
                    link: "/leaves",
                    data: [
                        'leave' => $leave, // On passe l'objet, le template s'occupe du formatage
                    ],
                    sendEmail: true
                );
            }
        } catch (\Throwable $e) {
            // ⚠️ CRUCIAL : On log l'erreur mais on NE BLOQUE PAS la création du congé
            \Illuminate\Support\Facades\Log::error("Erreur notification nouveau congé: " . $e->getMessage());
        }
    }

    /**
     * Notifie les managers/RH d'une nouvelle demande d'absence
     */
    public function notifyNewAbsenceRequest($absence): void
    {
        $employee = $absence->employee;
        $managers = $this->getManagersToNotify($employee);

        foreach ($managers as $manager) {
            $this->send(
                user: $manager,
                type: 'absence_request',
                title: 'Nouvelle déclaration d\'absence',
                message: "{$employee->full_name} a déclaré une absence.",
                link: "/absences",
                data: ['absence_id' => $absence->id, 'employee_name' => $employee->full_name]
            );
        }
    }

        /**
     * Notifie les managers/RH d'un contrat qui expire bientôt
     * Utilise les settings : contract_expiry_reminder_days, contract_expiry_notify_employee
     */
    public function notifyContractExpiring($contract, int $daysRemaining): void
    {
        $employee = $contract->employee;
        $managers = $this->getManagersToNotify($employee);

        $type = $daysRemaining <= 7 ? 'contract_expired' : 'contract_expiring';
        $title = $daysRemaining <= 7 
            ? "⚠️ Contrat expiré - Action requise" 
            : "Rappel : Contrat expirant dans {$daysRemaining} jours";

        // 1. Notifier les managers/RH
        foreach ($managers as $manager) {
            $this->send(
                user: $manager,
                type: $type,
                title: $title,
                message: "Le contrat de {$employee->full_name} expire le " .
                         \Carbon\Carbon::parse($contract->end_date)->format('d/m/Y') . 
                         " (dans {$daysRemaining} jours).",
                link: "/contracts",
                data: [
                    'contract' => $contract->load('employee'),
                    'days_remaining' => $daysRemaining,
                ],
                sendEmail: true
            );
        }

        // 2. Notifier l'employé lui-même si le setting est activé
        $notifyEmployee = Setting::get('contract_expiry_notify_employee', false);
        if ($notifyEmployee && $employee->user && $employee->user->email) {
            $this->send(
                user: $employee->user,
                type: $type,
                title: $title,
                message: "Votre contrat arrive à expiration le " .
                         \Carbon\Carbon::parse($contract->end_date)->format('d/m/Y') . 
                         " (dans {$daysRemaining} jours). Merci de contacter le service RH.",
                link: "/contracts",
                data: [
                    'contract' => $contract->load('employee'),
                    'days_remaining' => $daysRemaining,
                ],
                sendEmail: true
            );
        }
    }

         /**
     * Récupère les managers/RH à notifier pour un employé donné
     * Compatible avec Spatie Permission
     */
    private function getManagersToNotify($employee): \Illuminate\Support\Collection
    {
        $managers = collect();

        // 1. Le manager du département de l'employé (le plus fiable)
        $department = $employee->position?->department;
        if ($department && $department->manager_id) {
            $manager = \App\Models\User::find($department->manager_id);
            if ($manager) {
                $managers->push($manager);
            }
        }

        // 2. Récupérer les utilisateurs avec les rôles admin/hr via Spatie Permission
        try {
            // Vérifie que Spatie Permission est bien installé
            if (trait_exists(\Spatie\Permission\Traits\HasRoles::class)) {
                // Récupère les admins
                $admins = \App\Models\User::role('admin')->get();
                $managers = $managers->merge($admins);

                // Récupère les RH (si le rôle existe)
                try {
                    $hrs = \App\Models\User::role('hr')->get();
                    $managers = $managers->merge($hrs);
                } catch (\Throwable $e) {
                    // Le rôle 'hr' n'existe peut-être pas, on ignore
                    \Illuminate\Support\Facades\Log::info('Notification : Le rôle "hr" n\'existe pas dans Spatie.');
                }

                // Optionnel : Récupère les managers (si tu as ce rôle)
                try {
                    $roleManagers = \App\Models\User::role('manager')->get();
                    $managers = $managers->merge($roleManagers);
                } catch (\Throwable $e) {
                    // Le rôle 'manager' n'existe peut-être pas
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Notification : Impossible d\'utiliser Spatie Permission. Détail : ' . $e->getMessage());
        }

        return $managers->unique('id')->filter(); // Filtre les nulls
    }
}