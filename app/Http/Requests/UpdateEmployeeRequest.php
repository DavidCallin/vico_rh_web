<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
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
        $employeeId = $this->route('employee')->id;

        return [
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'email' => 'sometimes|email|unique:employees,email,' . $employeeId,
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date|before:today',
            'gender' => 'nullable|in:M,F',
            'address' => 'nullable|string|max:255',
            'hire_date' => 'sometimes|date|before_or_equal:today',
            'site' => 'nullable|string|max:100',
            'status' => 'sometimes|in:active,on_leave,terminated,trial',
            'position_id' => 'sometimes|exists:positions,id',
            'category_id' => 'sometimes|exists:categories,id',
        ];
    }
}
