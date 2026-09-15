<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'contract_type' => 'required|in:cdi,cdd,internship,interim,apprenticeship',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'base_salary' => 'required|numeric|min:0',
            'pdf' => 'nullable|file|mimes:pdf|max:10240', // 10MB max
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.exists' => 'L\'employé sélectionné n\'existe pas.',
            'end_date.after' => 'La date de fin doit être postérieure à la date de début.',
            'base_salary.min' => 'Le salaire ne peut pas être négatif.',
            'pdf.mimes' => 'Le contrat doit être un fichier PDF.',
            'pdf.max' => 'Le fichier ne doit pas dépasser 10 Mo.',
        ];
    }
}