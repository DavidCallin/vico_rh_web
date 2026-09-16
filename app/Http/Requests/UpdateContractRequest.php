<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateContractRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'employee_id'   => 'required|exists:employees,id',
            'contract_type' => 'required|string|in:cdi,cdd,internship,apprenticeship',
            'start_date'    => 'required|date',
            'end_date'      => 'nullable|date|after_or_equal:start_date',
            'base_salary'   => 'required|numeric|min:0',
            'pdf_path'      => 'nullable|file|mimes:pdf|max:10240', // 10MB max
        ];
    }
}
