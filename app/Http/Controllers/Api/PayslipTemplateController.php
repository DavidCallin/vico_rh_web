<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PayslipTemplate;
use Illuminate\Http\Request;

class PayslipTemplateController extends Controller
{
    public function index()
    {
        $templates = PayslipTemplate::with(['category', 'payrollItems'])->get();
        return response()->json(['data' => $templates]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'is_default' => 'boolean',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        if ($validated['is_default'] ?? false) {
            PayslipTemplate::where('is_default', true)->update(['is_default' => false]);
        }

        $template = PayslipTemplate::create($validated);

        return response()->json([
            'message' => 'Template créé.', 
            'data' => $template->load(['category', 'payrollItems'])
        ], 201);
    }

    public function update(Request $request, PayslipTemplate $payslipTemplate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'is_default' => 'boolean',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        if ($validated['is_default'] ?? false) {
            PayslipTemplate::where('is_default', true)
                ->where('id', '!=', $payslipTemplate->id)
                ->update(['is_default' => false]);
        }

        $payslipTemplate->update($validated);

        return response()->json([
            'message' => 'Template mis à jour.', 
            'data' => $payslipTemplate->load(['category', 'payrollItems'])
        ]);
    }

    public function destroy(PayslipTemplate $payslipTemplate)
    {
        $payslipTemplate->delete();
        return response()->json(['message' => 'Template supprimé.']);
    }

    /**
     * 🎯 Synchroniser les rubriques d'un template
     */
    public function syncPayrollItems(Request $request, PayslipTemplate $payslipTemplate)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.payroll_item_id' => 'required|exists:payroll_items,id',
            'items.*.isActive' => 'required|boolean',
            'items.*.displayOrder' => 'required|integer|min:0',
        ]);

        $syncData = [];
        foreach ($validated['items'] as $item) {
            $syncData[$item['payroll_item_id']] = [
                'isActive' => $item['isActive'],
                'displayOrder' => $item['displayOrder'],
            ];
        }

        $payslipTemplate->payrollItems()->sync($syncData);

        return response()->json([
            'message' => 'Rubriques synchronisées avec succès.',
            'data' => $payslipTemplate->load('payrollItems')
        ]);
    }
}