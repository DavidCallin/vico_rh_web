<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestMail extends Command
{
    protected $signature = 'mail:test {email}';
    protected $description = 'Envoie un email de test pour vérifier la configuration SMTP';

    public function handle(): int
    {
        $email = $this->argument('email');
        
        $this->info("📧 Envoi d'un email de test à : {$email}");

        try {
            Mail::raw('✅ Bravo ! Ton SMTP fonctionne parfaitement avec VICO RH.', function ($message) use ($email) {
                $message->to($email)
                        ->subject('[VICO RH] Test de configuration SMTP');
            });

            $this->info('✅ Email envoyé avec succès !');
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('❌ Erreur : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}