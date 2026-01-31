<?php

namespace App\Http\Requests\Pricing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = auth()->user()->tenant_id;
        $discountId = $this->route('discount')->id;

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('discounts')->where(fn ($query) => $query->where('tenant_id', $tenantId))->ignore($discountId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:percentage,fixed_amount,buy_x_get_y'],
            'scope' => ['required', 'in:order,item'],
            'value' => ['required', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'min_purchase' => ['nullable', 'numeric', 'min:0'],
            'min_qty' => ['nullable', 'integer', 'min:1'],
            'member_only' => ['nullable', 'boolean'],
            'membership_levels' => ['nullable', 'array'],
            'membership_levels.*' => ['in:regular,silver,gold,platinum'],
            'applicable_outlets' => ['nullable', 'array'],
            'applicable_outlets.*' => ['uuid', 'exists:outlets,id'],
            'applicable_items' => ['nullable', 'array'],
            'applicable_items.*' => ['uuid', 'exists:inventory_items,id'],
            'valid_from' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'is_auto_apply' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Discount code is required.',
            'code.unique' => 'Discount code already exists.',
            'name.required' => 'Discount name is required.',
            'type.required' => 'Discount type is required.',
            'scope.required' => 'Discount scope is required.',
            'value.required' => 'Discount value is required.',
            'valid_from.required' => 'Valid from date is required.',
            'valid_until.after_or_equal' => 'Valid until must be after or equal to valid from date.',
        ];
    }
}
