<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
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