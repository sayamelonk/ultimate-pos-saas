<?php

namespace App\Http\Requests\QrMenu;

use Illuminate\Foundation\Http\FormRequest;

class PlaceQrOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'uuid', 'exists:products,id'],
            'items.*.variant_id' => ['nullable', 'uuid', 'exists:product_variants,id'],
            'items.*.modifiers' => ['nullable', 'array'],
            'items.*.modifiers.*' => ['uuid', 'exists:modifiers,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
            'customer_name' => ['nullable', 'string', 'max:100'],
            'customer_phone' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['required', 'in:qris,pay_at_counter'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Please add at least one item to your order.',
            'items.min' => 'Please add at least one item to your order.',
            'items.*.product_id.required' => 'Product is required for each item.',
            'items.*.product_id.exists' => 'Selected product is not available.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
            'payment_method.required' => 'Please select a payment method.',
            'payment_method.in' => 'Invalid payment method selected.',
        ];
    }
}
