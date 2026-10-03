<?php

namespace Database\Seeders;

use App\Models\PayrollItem;
use Illuminate\Database\Seeder;

class PayrollItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            // === GAINS ===
            [
                'code' => 'SALAIRE_BASE',
                'name' => 'Salaire de base',
                'type' => 'gain',                    // ✅ 'gain' et non 'earning'
                'taxStatus' => 'taxable',
                'calculation' => 'fixed',            // ✅ 'calculation' et non 'calculationType'
                'amount_or_rate' => 0,               // ✅ 'amount_or_rate' et non 'defaultValue'
                'defaultValue' => 0,
                'displayOrder' => 1,
                'isActive' => true,
            ],
            [
                'code' => 'PRIME_TRANSPORT',
                'name' => 'Prime de transport',
                'type' => 'gain',
                'taxStatus' => 'non_taxable',
                'calculation' => 'fixed',
                'amount_or_rate' => 50000,
                'defaultValue' => 50000,
                'displayOrder' => 2,
                'isActive' => true,
            ],
            [
                'code' => 'PRIME_REND',
                'name' => 'Prime de rendement',
                'type' => 'gain',
                'taxStatus' => 'taxable',
                'calculation' => 'percentage',       // ✅ 'percentage' et non 'percentage_base'
                'amount_or_rate' => 10,
                'defaultValue' => 10,
                'displayOrder' => 3,
                'isActive' => true,
            ],
            [
                'code' => 'HEURE_SUP',
                'name' => 'Heures supplémentaires',
                'type' => 'gain',
                'taxStatus' => 'taxable',
                'calculation' => 'fixed',
                'amount_or_rate' => 0,
                'defaultValue' => 0,
                'displayOrder' => 4,
                'isActive' => true,
            ],

            // === DÉDUCTIONS ===
            [
                'code' => 'CNSS',
                'name' => 'Cotisation CNSS',
                'type' => 'deduction',
                'taxStatus' => 'non_taxable',
                'calculation' => 'percentage',
                'amount_or_rate' => 5.43,
                'defaultValue' => 5.43,
                'displayOrder' => 10,
                'isActive' => true,
            ],
            [
                'code' => 'ITS',
                'name' => 'Impôt sur le Traitement et les Salaires',
                'type' => 'deduction',
                'taxStatus' => 'non_taxable',
                'calculation' => 'percentage',
                'amount_or_rate' => 0,
                'defaultValue' => 0,
                'displayOrder' => 11,
                'isActive' => true,
            ],
            [
                'code' => 'RTPS',
                'name' => 'Régime de Travail et de Prévoyance Sociale',
                'type' => 'deduction',
                'taxStatus' => 'non_taxable',
                'calculation' => 'percentage',
                'amount_or_rate' => 2,
                'defaultValue' => 2,
                'displayOrder' => 12,
                'isActive' => true,
            ],
            [
                'code' => 'RETARD',
                'name' => 'Déduction pour retard',
                'type' => 'deduction',
                'taxStatus' => 'non_taxable',
                'calculation' => 'fixed',
                'amount_or_rate' => 0,
                'defaultValue' => 0,
                'displayOrder' => 13,
                'isActive' => true,
            ],
        ];

        foreach ($items as $item) {
            PayrollItem::updateOrCreate(
                ['code' => $item['code']],
                $item
            );
        }

        $this->command->info('✅ 8 rubriques de paie créées/mises à jour avec succès !');
    }
}