<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = auth()->user()->tenant_id;
        $customerId = $this->route('customer')->id;

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('customers')->where(fn ($query) => $query->where('tenant_id', $tenantId))->ignore($customerId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('customers')->where(fn ($query) => $query->where('tenant_id', $tenantId))->ignore($customerId),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:male,female,other'],
            'membership_level' => ['nullable', 'in:regular,silver,gold,platinum'],
            'membership_expires_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Customer code is required.',
            'code.unique' => 'Customer code already exists.',
            'name.required' => 'Customer name is required.',
            'email.unique' => 'Email address already exists.',
            'email.email' => 'Please enter a valid email address.',
        ];
    }
}
