<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    public function run(): void
    {
        // Créer l'utilisateur admin de test
        $admin = User::firstOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Admin Test',
                'login' => 'admin',
                'password' => Hash::make('password123'),
                'status' => 'active',
                'employee_id' => 1,
            ]
        );

        $admin->assignRole('admin');

        $this->command->info('✅ Utilisateur de test créé : admin@test.com / password123');
    }
}