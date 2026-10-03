<?php

namespace App\Console\Commands;

use App\Models\Leave;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
// Note: Tu pourras créer un LeavePendingReminderMail plus tard si besoin, 
// pour l'instant on réutilise LeaveRequestedMail ou on en crée un nouveau.
// Créons-en un nouveau rapidement pour la propreté :
// php artisan make:mail LeavePendingReminderMail (à faire si tu veux, sinon on skip l'envoi mail ici pour se concentrer sur le scheduler)

class CheckPendingLeaves extends Command
{
    protected $signature = 'leaves:check-pending';
    protected $description = 'Rappelle aux managers les demandes de congés en attente depuis trop longtemps';

    public function handle()
    {
        $this->info('Vérification des demandes de congés en attente...');

        $reminderDays = Setting::get('leave_pending_reminder_days', 3);
        $cutoffDate = now()->subDays($reminderDays);

        // Trouver les demandes en attente créées avant la date limite
        $pendingLeaves = Leave::where('status', 'pending')
            ->where('created_at', '<=', $cutoffDate)
            ->with('employee.position.department')
            ->get();

        if ($pendingLeaves->isEmpty()) {
            $this->info('Aucune demande de congé en attente depuis plus de ' . $reminderDays . ' jours.');
            return Command::SUCCESS;
        }

        $this->warn($pendingLeaves->count() . ' demande(s) en attente depuis trop longtemps.');

        // Ici, tu pourrais boucler et envoyer un email de relance aux managers concernés.
        // Pour faire simple et tester le scheduler, on se contente de l'affichage console pour l'instant.
        foreach ($pendingLeaves as $leave) {
            $dept = $leave->employee->position->department->name ?? 'Sans département';
            $this->line("- {$leave->employee->full_name} ({$dept}) : en attente depuis le {$leave->created_at->format('d/m/Y')}");
        }

        return Command::SUCCESS;
    }
}