<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionPlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price_monthly' => (int) $this->price_monthly,
            'price_yearly' => (int) $this->price_yearly,
            'price_monthly_formatted' => $this->getFormattedPriceMonthly(),
            'price_yearly_formatted' => $this->getFormattedPriceYearly(),
            'max_outlets' => $this->max_outlets,
            'max_users' => $this->max_users,
            'max_products' => $this->max_products,
            'features' => $this->features ?? [],
            'is_popular' => $this->slug === 'growth', // Growth is popular
            'sort_order' => $this->sort_order,
        ];
    }
}
