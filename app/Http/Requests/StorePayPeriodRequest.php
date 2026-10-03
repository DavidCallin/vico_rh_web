<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePayPeriodRequest extends FormRequest
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
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2000,2100', Rule::unique('pay_periods')->where(fn ($query) => $query->where('month', $this->integer('month')))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'month.required' => 'Le mois est obligatoire.',
            'year.required' => 'L\'année est obligatoire.',
            'year.unique' => 'Cette période de paie existe déjà.',
        ];
    }
}
