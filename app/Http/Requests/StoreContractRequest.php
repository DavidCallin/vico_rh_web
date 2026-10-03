<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('rh') === true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'contract_type' => ['required', Rule::in(['cdi', 'cdd', 'internship', 'interim', 'apprenticeship'])],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date', 'required_unless:contract_type,cdi'],
            'base_salary' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'employee_id.required' => 'Le salarié est obligatoire.',
            'contract_type.required' => 'Le type de contrat est obligatoire.',
            'start_date.required' => 'La date de début est obligatoire.',
            'end_date.required_unless' => 'La date de fin est obligatoire pour ce type de contrat.',
            'end_date.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
            'base_salary.required' => 'Le salaire de base est obligatoire.',
        ];
    }
}
