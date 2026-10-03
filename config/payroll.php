<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Employeur figurant sur les contrats générés
    |--------------------------------------------------------------------------
    */

    'employer' => [
        'name' => env('PAYROLL_EMPLOYER_NAME', env('APP_NAME', 'Entreprise')),
        'address' => env('PAYROLL_EMPLOYER_ADDRESS', 'Cotonou, République du Bénin'),
        'city' => env('PAYROLL_EMPLOYER_CITY', 'Cotonou'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Contrats à rappeler avant leur échéance
    |--------------------------------------------------------------------------
    |
    | Nombre de jours avant la date de fin. Les contrats déjà échus sont
    | aussi signalés.
    |
    */

    'contract_reminder_days' => (int) env('PAYROLL_CONTRACT_REMINDER_DAYS', 30),

];
