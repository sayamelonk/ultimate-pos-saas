<?php

namespace App\Http\Requests\POS;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'uuid', 'exists:inventory_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'payment_method_id' => ['required', 'uuid', 'exists:payment_methods,id'],
            'payment_amount' => ['required', 'numeric', 'min:0'],
            'customer_id' => ['nullable', 'uuid', 'exists:customers,id'],
            'discounts' => ['nullable', 'array'],
            'discounts.*.discount_id' => ['nullable', 'uuid', 'exists:discounts,id'],
            'discounts.*.type' => ['nullable', 'in:percentage,fixed_amount'],
            'discounts.*.value' => ['nullable', 'numeric', 'min:0'],
            'discounts.*.name' => ['nullable', 'string', 'max:100'],
            'points_to_redeem' => ['nullable', 'numeric', 'min:0'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'At least one item is required.',
            'items.min' => 'At least one item is required.',
            'items.*.inventory_item_id.required' => 'Item is required.',
            'items.*.inventory_item_id.exists' => 'Selected item does not exist.',
            'items.*.quantity.required' => 'Quantity is required.',
            'items.*.quantity.min' => 'Quantity must be greater than zero.',
            'payment_method_id.required' => 'Payment method is required.',
            'payment_method_id.exists' => 'Selected payment method does not exist.',
            'payment_amount.required' => 'Payment amount is required.',
            'payment_amount.min' => 'Payment amount cannot be negative.',
        ];
    }
}
