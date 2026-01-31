<?php

namespace App\Http\Controllers\Pricing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pricing\StoreDiscountRequest;
use App\Http\Requests\Pricing\UpdateDiscountRequest;
use App\Models\Discount;
use App\Models\InventoryItem;
use App\Models\Outlet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiscountController extends Controller
{
    public function index(Request $request): View
    {
        $query = Discount::where('tenant_id', auth()->user()->tenant_id);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $discounts = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        return view('pricing.discounts.index', [
            'discounts' => $discounts,
            'types' => Discount::getTypes(),
        ]);
    }

    public function create(): View
    {
        $tenantId = auth()->user()->tenant_id;

        return view('pricing.discounts.create', [
            'types' => Discount::getTypes(),
            'scopes' => Discount::getScopes(),
            'outlets' => Outlet::where('tenant_id', $tenantId)->where('is_active', true)->get(),
            'items' => InventoryItem::where('tenant_id', $tenantId)->where('is_active', true)->get(),
        ]);
    }

    public function store(StoreDiscountRequest $request): RedirectResponse
    {
        Discount::create([
            'tenant_id' => auth()->user()->tenant_id,
            'code' => strtoupper($request->code),
            'name' => $request->name,
            'description' => $request->description,
            'type' => $request->type,
            'scope' => $request->scope,
            'value' => $request->value,
            'max_discount' => $request->max_discount,
            'min_purchase' => $request->min_purchase,
            'min_qty' => $request->min_qty,
            'member_only' => $request->boolean('member_only'),
            'membership_levels' => $request->membership_levels,
            'applicable_outlets' => $request->applicable_outlets,
            'applicable_items' => $request->applicable_items,
            'valid_from' => $request->valid_from,
            'valid_until' => $request->valid_until,
            'usage_limit' => $request->usage_limit,
            'is_auto_apply' => $request->boolean('is_auto_apply'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('pricing.discounts.index')
            ->with('success', 'Discount created successfully.');
    }

    public function show(Discount $discount): View
    {
        $this->authorizeDiscount($discount);

        return view('pricing.discounts.show', [
            'discount' => $discount,
            'types' => Discount::getTypes(),
            'scopes' => Discount::getScopes(),
        ]);
    }

    public function edit(Discount $discount): View
    {
        $this->authorizeDiscount($discount);
        $tenantId = auth()->user()->tenant_id;

        return view('pricing.discounts.edit', [
            'discount' => $discount,
            'types' => Discount::getTypes(),
            'scopes' => Discount::getScopes(),
            'outlets' => Outlet::where('tenant_id', $tenantId)->where('is_active', true)->get(),
            'items' => InventoryItem::where('tenant_id', $tenantId)->where('is_active', true)->get(),
        ]);
    }

    public function update(UpdateDiscountRequest $request, Discount $discount): RedirectResponse
    {
        $this->authorizeDiscount($discount);

        $discount->update([
            'code' => strtoupper($request->code),
            'name' => $request->name,
            'description' => $request->description,
            'type' => $request->type,
            'scope' => $request->scope,
            'value' => $request->value,
            'max_discount' => $request->max_discount,
            'min_purchase' => $request->min_purchase,
            'min_qty' => $request->min_qty,
            'member_only' => $request->boolean('member_only'),
            'membership_levels' => $request->membership_levels,
            'applicable_outlets' => $request->applicable_outlets,
            'applicable_items' => $request->applicable_items,
            'valid_from' => $request->valid_from,
            'valid_until' => $request->valid_until,
            'usage_limit' => $request->usage_limit,
            'is_auto_apply' => $request->boolean('is_auto_apply'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('pricing.discounts.index')
            ->with('success', 'Discount updated successfully.');
    }

    public function destroy(Discount $discount): RedirectResponse
    {
        $this->authorizeDiscount($discount);

        $discount->delete();

        return redirect()->route('pricing.discounts.index')
            ->with('success', 'Discount deleted successfully.');
    }

    private function authorizeDiscount(Discount $discount): void
    {
        if ($discount->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }
    }
}
