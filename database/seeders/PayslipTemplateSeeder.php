<?php

namespace Database\Seeders;

use App\Models\PayslipTemplate;
use Illuminate\Database\Seeder;

class PayslipTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $html = '
        <div style="font-family: Arial, sans-serif; font-size: 12px; color: #333; padding: 20px;">
            <h1 style="text-align: center; color: #2563eb; border-bottom: 2px solid #2563eb; padding-bottom: 10px;">BULLETIN DE PAIE</h1>
            
            <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
                <div>
                    <p><strong>Employé :</strong> {{employee_name}}</p>
                    <p><strong>Matricule :</strong> {{matricule}}</p>
                </div>
                <div style="text-align: right;">
                    <p><strong>Période :</strong> {{period}}</p>
                    <p><strong>Solde Congés :</strong> Pris: {{leaves_taken}}j | Restant: {{leaves_remaining}}j</p>
                </div>
            </div>

            <h3 style="background-color: #f0f9ff; padding: 8px; color: #1e40af;">📈 GAINS</h3>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                <tr style="background-color: #e2e8f0;">
                    <th style="padding: 8px; text-align: left; border: 1px solid #cbd5e1;">Rubrique</th>
                    <th style="padding: 8px; text-align: right; border: 1px solid #cbd5e1; width: 120px;">Montant</th>
                </tr>
                {{earnings_lines}}
            </table>

            <h3 style="background-color: #fef2f2; padding: 8px; color: #991b1b;">📉 DÉDUCTIONS</h3>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                <tr style="background-color: #e2e8f0;">
                    <th style="padding: 8px; text-align: left; border: 1px solid #cbd5e1;">Rubrique</th>
                    <th style="padding: 8px; text-align: right; border: 1px solid #cbd5e1; width: 120px;">Montant</th>
                </tr>
                {{deductions_lines}}
            </table>

            <div style="background-color: #dcfce7; padding: 15px; border-radius: 5px; text-align: right; font-size: 16px; font-weight: bold; color: #166534;">
                NET À PAYER : {{net_salary}}
            </div>
        </div>
        ';

        PayslipTemplate::updateOrCreate(
            ['name' => 'Standard'],
            [
                'content' => $html,
                'is_default' => true,
            ]
        );

        $this->command->info('✅ Template de bulletin de paie mis à jour avec les groupes et les congés !');
    }
}