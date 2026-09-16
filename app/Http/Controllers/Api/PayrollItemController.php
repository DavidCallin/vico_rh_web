<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PayrollItem;
use App\Models\PayslipLine;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PayrollItemController extends Controller
{
        /**
     * Afficher toutes les rubriques de paie
     */
    public function index()
    {
        // ⚠️ Utiliser le nom réel de la colonne en base de données (snake_case) pour le ORDER BY SQL
        $items = PayrollItem::orderBy('displayOrder', 'asc')->get();
        
        return response()->json([
            'data' => $items
        ]);
    }
    /**
     * Créer une nouvelle rubrique de paie
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:payroll_items,code|max:50',
            'name' => 'required|string|max:255',
            'type' => 'required|in:earning,deduction',
            'taxStatus' => 'required|in:taxable,non_taxable',
            'calculationType' => 'required|in:fixed,percentage_base,percentage_brut',
            'defaultValue' => 'required|numeric|min:0',
            'displayOrder' => 'required|integer|min:0',
            'isActive' => 'boolean',
            'description' => 'nullable|string|max:500',
        ]);

        $item = PayrollItem::create($validated);

        return response()->json([
            'message' => 'Rubrique créée avec succès.',
            'data' => $item
        ], 201);
    }

    /**
     * Mettre à jour une rubrique de paie
     */
    public function update(Request $request, PayrollItem $payrollItem)
    {
        $validated = $request->validate([
            'code' => [
                'required', 
                'string', 
                'max:50', 
                Rule::unique('payroll_items', 'code')->ignore($payrollItem->id)
            ],
            'name' => 'required|string|max:255',
            'type' => 'required|in:earning,deduction',
            'taxStatus' => 'required|in:taxable,non_taxable',
            'calculationType' => 'required|in:fixed,percentage_base,percentage_brut',
            'defaultValue' => 'required|numeric|min:0',
            'displayOrder' => 'required|integer|min:0',
            'isActive' => 'boolean',
            'description' => 'nullable|string|max:500',
        ]);

        $payrollItem->update($validated);

        return response()->json([
            'message' => 'Rubrique mise à jour avec succès.',
            'data' => $payrollItem
        ]);
    }

    /**
     * Supprimer une rubrique de paie
     * Protection : Impossible de supprimer si elle est déjà utilisée dans des bulletins
     */
    public function destroy(PayrollItem $payrollItem)
    {
        // Vérifier si la rubrique est liée à des lignes de bulletin existantes
        $isUsed = PayslipLine::where('payroll_item_id', $payrollItem->id)->exists();

        if ($isUsed) {
            return response()->json([
                'message' => 'Impossible de supprimer cette rubrique car elle est déjà utilisée dans des bulletins de paie. Vous pouvez la désactiver à la place.'
            ], 400);
        }

        $payrollItem->delete();

        return response()->json([
            'message' => 'Rubrique supprimée avec succès.'
        ]);
    }

    /**
     * Activer ou désactiver une rubrique rapidement
     */
    public function toggleActive(Request $request, PayrollItem $payrollItem)
    {
        $validated = $request->validate([
            'isActive' => 'required|boolean'
        ]);

        $payrollItem->update(['isActive' => $validated['isActive']]);

        return response()->json([
            'message' => $validated['isActive'] ? 'Rubrique activée.' : 'Rubrique désactivée.',
            'data' => $payrollItem
        ]);
    }
}