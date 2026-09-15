<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContractRequest;
use App\Http\Requests\UpdateContractRequest;
use App\Http\Resources\ContractResource;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ContractController extends Controller
{
    /**
     * Liste des contrats
     */
    public function index(Request $request)
    {
        $query = Contract::with('employee.position.department');

        // Filtre par type de contrat
        if ($request->has('contract_type')) {
            $query->where('contract_type', $request->contract_type);
        }

        // Filtre par employé
        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        // Filtre : contrats actifs uniquement
        if ($request->boolean('active_only')) {
            $query->where(function ($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>', now());
            });
        }

        $contracts = $query->orderBy('start_date', 'desc')->paginate(15);

        return ContractResource::collection($contracts);
    }

    /**
     * Créer un contrat
     */
    public function store(StoreContractRequest $request)
    {
        // Upload du PDF si fourni
        $pdfPath = null;
        if ($request->hasFile('pdf')) {
            $pdfPath = $request->file('pdf')->store('contracts', 'public');
        }

        $contract = Contract::create([
            'employee_id' => $request->employee_id,
            'contract_type' => $request->contract_type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'base_salary' => $request->base_salary,
            'pdf_path' => $pdfPath,
        ]);

        return new ContractResource($contract->load('employee'));
    }

    /**
     * Afficher un contrat
     */
    public function show(Contract $contract)
    {
        $contract->load('employee.position.department');
        return new ContractResource($contract);
    }

    /**
     * Mettre à jour un contrat
     */
    public function update(UpdateContractRequest $request, Contract $contract)
    {
        $data = $request->validated();

        // Si un nouveau PDF est uploadé, supprimer l'ancien
        if ($request->hasFile('pdf')) {
            if ($contract->pdf_path) {
                Storage::disk('public')->delete($contract->pdf_path);
            }
            $data['pdf_path'] = $request->file('pdf')->store('contracts', 'public');
        }

        $contract->update($data);

        return new ContractResource($contract->load('employee'));
    }

    /**
     * Supprimer un contrat
     */
    public function destroy(Contract $contract)
    {
        // Supprimer le PDF s'il existe
        if ($contract->pdf_path) {
            Storage::disk('public')->delete($contract->pdf_path);
        }

        $contract->delete();

        return response()->noContent();
    }
}