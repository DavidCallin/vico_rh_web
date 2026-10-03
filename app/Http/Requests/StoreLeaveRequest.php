<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveRequest extends FormRequest
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
            'leave_type' => ['required', Rule::in(['annual', 'sick', 'unpaid', 'maternity', 'paternity', 'family', 'other'])],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:1000'],
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
            'leave_type.required' => 'Le type de congé est obligatoire.',
            'start_date.required' => 'La date de début est obligatoire.',
            'end_date.required' => 'La date de fin est obligatoire.',
            'end_date.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
            'employee_id.required' => 'Le salarié est obligatoire.',
        ];
    }
}
        return true; // La permission est gérée par le middleware 'can:leaves.create'
    }

    public function rules(): array
    {
        return [
            'leave_type' => 'required|in:annual,sick,unpaid,maternity,paternity,family,other',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'end_date.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
            'start_date.after_or_equal' => 'La date de début doit être aujourd\'hui ou dans le futur.',
        ];
    }
}
