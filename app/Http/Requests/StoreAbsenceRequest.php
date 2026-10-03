<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAbsenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ($user->hasRole('rh') || $user->employee_id !== null);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'type' => ['required', Rule::in(['unjustified', 'medical', 'family_emergency', 'other'])],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'proof' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:4096'],
        ];

        if ($this->user()?->hasRole('rh')) {
            $rules['employee_id'] = ['required', 'exists:employees,id'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Le type d\'absence est obligatoire.',
            'start_date.required' => 'La date de début est obligatoire.',
            'end_date.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
            'proof.mimes' => 'Le justificatif doit être un PDF ou une image.',
            'employee_id.required' => 'Le salarié est obligatoire.',
        ];
    }
}
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:unjustified,medical,family_emergency,other',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:500',
            'proof' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
        ];
    }

    public function messages(): array
    {
        return [
            'end_date.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
            'proof.file' => 'Le justificatif doit être un fichier.',
            'proof.mimes' => 'Le justificatif doit être un PDF ou une image.',
            'proof.max' => 'Le justificatif ne doit pas dépasser 5 Mo.',
        ];
    }
}
