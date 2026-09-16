<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ContractTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
{
    $types = ['cdi', 'cdd', 'internship', 'apprenticeship'];
    foreach ($types as $type) {
        \App\Models\ContractTemplate::updateOrCreate(
            ['type' => $type],
            ['content' => 'Template par défaut pour ' . $type] // Le vrai contenu est dans la vue Blade pour l'instant
        );
    }
}
}
