<?php

namespace App\Http\Requests\Pricing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = auth()->user()->tenant_id;
        $paymentMethodId = $this->route('payment_method')->id;

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('payment_methods')->where(fn ($query) => $query->where('tenant_id', $tenantId))->ignore($paymentMethodId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:cash,card,digital_wallet,transfer,other'],
            'provider' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:50'],
            'charge_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'charge_fixed' => ['nullable', 'numeric', 'min:0'],
            'requires_reference' => ['nullable', 'boolean'],
            'opens_cash_drawer' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Payment method code is required.',
            'code.unique' => 'Payment method code already exists.',
            'name.required' => 'Payment method name is required.',
            'type.required' => 'Payment type is required.',
            'type.in' => 'Invalid payment type.',
        ];
    }
}
