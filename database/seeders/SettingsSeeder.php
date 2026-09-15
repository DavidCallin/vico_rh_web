<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        // ===== CONTRATS =====
        Setting::updateOrCreate(
            ['key' => 'contract_expiry_reminder_days'],
            [
                'value' => '7',
                'type' => 'integer',
                'group' => 'contracts',
                'label' => 'Délai de rappel avant expiration (jours)',
                'description' => 'Nombre de jours avant expiration pour envoyer un rappel au RH',
            ]
        );

        Setting::updateOrCreate(
            ['key' => 'contract_expiry_notify_employee'],
            [
                'value' => '1',
                'type' => 'boolean',
                'group' => 'contracts',
                'label' => 'Notifier l\'employé avant expiration',
                'description' => 'Envoyer un email à l\'employé concerné',
            ]
        );

        // ===== CONGÉS =====
        Setting::updateOrCreate(
            ['key' => 'leave_pending_reminder_days'],
            [
                'value' => '3',
                'type' => 'integer',
                'group' => 'leaves',
                'label' => 'Rappel demandes en attente (jours)',
                'description' => 'Rappeler aux managers les demandes non traitées après X jours',
            ]
        );

        // ===== PAIE =====
        Setting::updateOrCreate(
            ['key' => 'payslip_auto_email'],
            [
                'value' => '1',
                'type' => 'boolean',
                'group' => 'payroll',
                'label' => 'Envoi automatique des bulletins par email',
                'description' => 'À la clôture de la période, envoyer le bulletin par email',
            ]
        );

        Setting::updateOrCreate(
            ['key' => 'payslip_email_subject'],
            [
                'value' => 'Votre bulletin de paie du {month} {year}',
                'type' => 'string',
                'group' => 'payroll',
                'label' => 'Sujet de l\'email du bulletin',
                'description' => 'Variables disponibles : {month}, {year}, {employee_name}',
            ]
        );

        // ===== GÉNÉRAL =====
        Setting::updateOrCreate(
            ['key' => 'company_name'],
            [
                'value' => 'VICO RH',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Nom de l\'entreprise',
            ]
        );

        Setting::updateOrCreate(
            ['key' => 'company_email'],
            [
                'value' => 'rh@vico.com',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Email de l\'entreprise',
            ]
        );

        $this->command->info('✅ Settings par défaut créés');
    }
}