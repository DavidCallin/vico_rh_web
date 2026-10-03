<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('contracts:remind')->daily();
// Exécuter la vérification des contrats tous les jours à 08h00
Schedule::command('contracts:check-expiring')->dailyAt('08:00');

// Exécuter la vérification des congés en attente tous les jours à 09h00
Schedule::command('leaves:check-pending')->dailyAt('09:00');
