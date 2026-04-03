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
            // Product-based items (new flow)
            'items.*.product_id' => ['nullable', 'uuid', 'exists:products,id'],
            'items.*.variant_id' => ['nullable', 'uuid', 'exists:product_variants,id'],
            'items.*.modifiers' => ['nullable', 'array'],
            'items.*.modifiers.*' => ['uuid', 'exists:modifiers,id'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
            // Legacy inventory-based items (backward compatibility)
            'items.*.inventory_item_id' => ['nullable', 'uuid', 'exists:inventory_items,id'],
            // Common fields
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            // Payment
            'payment_method_id' => ['required', 'uuid', 'exists:payment_methods,id'],
            'payment_amount' => ['required', 'numeric', 'min:0'],
            'customer_id' => ['nullable', 'uuid', 'exists:customers,id'],
            // Discounts
            'discounts' => ['nullable', 'array'],
            'discounts.*.discount_id' => ['nullable', 'uuid', 'exists:discounts,id'],
            'discounts.*.type' => ['nullable', 'in:percentage,fixed_amount'],
            'discounts.*.value' => ['nullable', 'numeric', 'min:0'],
            'discounts.*.name' => ['nullable', 'string', 'max:100'],
            // Others
            'points_to_redeem' => ['nullable', 'numeric', 'min:0'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $items = $this->input('items', []);
            foreach ($items as $index => $item) {
                // Each item must have either product_id or inventory_item_id
                if (empty($item['product_id']) && empty($item['inventory_item_id'])) {
                    $validator->errors()->add(
                        "items.{$index}",
                        'Each item must have either a product or inventory item.'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'items.required' => 'At least one item is required.',
            'items.min' => 'At least one item is required.',
            'items.*.product_id.exists' => 'Selected product does not exist.',
            'items.*.variant_id.exists' => 'Selected variant does not exist.',
            'items.*.inventory_item_id.exists' => 'Selected inventory item does not exist.',
            'items.*.quantity.required' => 'Quantity is required.',
            'items.*.quantity.min' => 'Quantity must be greater than zero.',
            'payment_method_id.required' => 'Payment method is required.',
            'payment_method_id.exists' => 'Selected payment method does not exist.',
            'payment_amount.required' => 'Payment amount is required.',
            'payment_amount.min' => 'Payment amount cannot be negative.',
        ];
    }
}
