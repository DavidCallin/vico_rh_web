<?php

namespace Database\Seeders;

use App\Models\PayslipTemplate;
use App\Models\PayrollItem;
use Illuminate\Database\Seeder;

class PayslipTemplateItemsSeeder extends Seeder
{
    public function run(): void
    {
        // Récupérer le template "Standard" (ou le premier)
        $template = PayslipTemplate::where('name', 'Standard')->first()
                    ?? PayslipTemplate::first();

        if (!$template) {
            $this->command->warn('⚠️ Aucun template trouvé. Lance d\'abord PayslipTemplateSeeder.');
            return;
        }

        // Récupérer toutes les rubriques
        $allItems = PayrollItem::orderBy('displayOrder')->get();

        if ($allItems->isEmpty()) {
            $this->command->warn('⚠️ Aucune rubrique trouvée. Lance d\'abord PayrollItemSeeder.');
            return;
        }

        // Construire le tableau de synchronisation
        $syncData = [];
        foreach ($allItems as $item) {
            $syncData[$item->id] = [
                'isActive' => true,
                'displayOrder' => $item->displayOrder,
            ];
        }

        // Lier toutes les rubriques au template
        $template->payrollItems()->sync($syncData);

        $this->command->info("✅ {$allItems->count()} rubriques liées au template '{$template->name}'.");
    }
}