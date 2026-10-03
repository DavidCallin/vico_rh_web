<?php

namespace App\Console\Commands;

use App\Services\ContractReminderService;
use Illuminate\Console\Command;

class RemindExpiringContracts extends Command
{
    protected $signature = 'contracts:remind';

    protected $description = 'Prévenir des contrats échus ou proches de l\'échéance';

    public function handle(ContractReminderService $reminders): int
    {
        $sent = $reminders->dispatchDue();
        $this->info($sent.' rappel(s) de contrat envoyé(s).');

        return self::SUCCESS;
    }
}
