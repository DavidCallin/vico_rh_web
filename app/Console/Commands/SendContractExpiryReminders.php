<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\Setting;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class SendContractExpiryReminders extends Command
{
    protected $signature = 'contracts:check-expiry';
    protected $description = 'Vérifie les contrats qui expirent bientôt et envoie des rappels';

    public function handle(NotificationService $notificationService): int
    {
        // ✅ Utilise le setting de la base de données
        $reminderDays = Setting::get('contract_expiry_reminder_days', 7);
        
        $today = Carbon::today();
        $warningDate = $today->copy()->addDays($reminderDays);
        
        $this->info("🔍 Recherche des contrats expirant d'ici {$reminderDays} jours...");

        // Contrats qui expirent dans les X prochains jours
        $expiringContracts = Contract::whereNotNull('end_date')
            ->where('end_date', '>=', $today)
            ->where('end_date', '<=', $warningDate)
            ->with('employee')
            ->get();

        $this->info("📋 {$expiringContracts->count()} contrat(s) trouvé(s).");

        $sentCount = 0;

        foreach ($expiringContracts as $contract) {
            $daysRemaining = $today->diffInDays(Carbon::parse($contract->end_date));
            
            // ✅ Éviter les doublons : vérifier si une notif similaire a déjà été envoyée aujourd'hui
            $alreadyNotified = Notification::where('type', 'like', 'contract_expir%')
                ->where('data->contract_id', $contract->id)
                ->whereDate('created_at', $today)
                ->exists();

            if (!$alreadyNotified) {
                $notificationService->notifyContractExpiring($contract, $daysRemaining);
                $this->info("  ✅ Rappel envoyé pour {$contract->employee->full_name} ({$daysRemaining} jours restants)");
                $sentCount++;
            } else {
                $this->line("  ⏭️  Déjà notifié aujourd'hui : {$contract->employee->full_name}");
            }
        }

        $this->info("✅ Terminé. {$sentCount} rappel(s) envoyé(s).");
        return Command::SUCCESS;
    }
}