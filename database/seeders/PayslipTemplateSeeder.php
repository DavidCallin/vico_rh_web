<?php

namespace Database\Seeders;

use App\Models\PayslipTemplate;
use Illuminate\Database\Seeder;

class PayslipTemplateSeeder extends Seeder
{
    public function run(): void
    {
        PayslipTemplate::create([
            'name' => 'Standard',
            'content' => '<h1>BULLETIN DE PAIE</h1><p>Période : {{period}}</p><p>Employé : {{employee_name}} ({{matricule}})</p><table><tr><td>Salaire brut</td><td>{{gross_salary}}</td></tr><tr><td>Déductions</td><td>{{deductions}}</td></tr><tr><td><strong>Net à payer</strong></td><td><strong>{{net_salary}}</strong></td></tr></table>',
            'is_default' => true,
        ]);
    }
}