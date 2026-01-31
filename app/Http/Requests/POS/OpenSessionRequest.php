<?php

namespace App\Http\Requests\POS;

use Illuminate\Foundation\Http\FormRequest;

class OpenSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'outlet_id' => ['required', 'uuid', 'exists:outlets,id'],
            'opening_cash' => ['required', 'numeric', 'min:0'],
            'opening_notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'outlet_id.required' => 'Please select an outlet.',
            'outlet_id.exists' => 'Selected outlet does not exist.',
            'opening_cash.required' => 'Opening cash amount is required.',
            'opening_cash.min' => 'Opening cash cannot be negative.',
        ];
    }
}
