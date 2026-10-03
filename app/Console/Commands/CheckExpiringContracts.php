<?php

namespace App\Console\Commands;

use App\Mail\ContractExpiringMail;
use App\Models\Contract;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class CheckExpiringContracts extends Command
{
    protected $signature = 'contracts:check-expiring';
    protected $description = 'Vérifie et envoie des rappels pour les contrats arrivant à expiration';

    public function handle()
    {
        $this->info('Vérification des contrats expirants...');

        // 1. Récupérer le délai configuré (défaut: 7 jours)
        $reminderDays = Setting::get('contract_expiry_reminder_days', 7);
        $targetDate = now()->addDays($reminderDays)->toDateString();

        // 2. Trouver les contrats qui expirent exactement à cette date
        $expiringContracts = Contract::whereDate('end_date', $targetDate)
            ->with('employee.user')
            ->get();

        if ($expiringContracts->isEmpty()) {
            $this->info('Aucun contrat n\'expire dans ' . $reminderDays . ' jours.');
            return Command::SUCCESS;
        }

        $this->info($expiringContracts->count() . ' contrat(s) trouvé(s).');

        // 3. Récupérer les utilisateurs RH pour les notifier
        $rhUsers = User::role('rh')->get();

        // 4. Envoyer les emails
        foreach ($expiringContracts as $contract) {
            $this->line("Envoi du rappel pour : {$contract->employee->full_name}");

            // Email aux RH
            foreach ($rhUsers as $rh) {
                Mail::to($rh->email)->send(new ContractExpiringMail($contract, $reminderDays));
            }

            // Email à l'employé (si activé dans les settings)
            if (Setting::get('contract_expiry_notify_employee', false) && $contract->employee->user) {
                Mail::to($contract->employee->user->email)->send(new ContractExpiringMail($contract, $reminderDays));
            }
        }

        $this->info('Rappels envoyés avec succès !');
        return Command::SUCCESS;
    }
}