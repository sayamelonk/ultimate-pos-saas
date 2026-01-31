<?php

namespace App\Http\Controllers\Pricing;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\Outlet;
use App\Models\Price;
use App\Services\PriceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PriceController extends Controller
{
    public function __construct(private PriceService $priceService) {}

    public function index(Request $request): View
    {
        $tenantId = auth()->user()->tenant_id;
        $outlets = Outlet::where('tenant_id', $tenantId)->where('is_active', true)->get();

        $selectedOutletId = $request->outlet_id ?? $outlets->first()?->id;

        $query = InventoryItem::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with(['category', 'unit']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $items = $query->orderBy('name')->paginate(20)->withQueryString();

        if ($selectedOutletId) {
            $prices = Price::where('outlet_id', $selectedOutletId)
                ->pluck('selling_price', 'inventory_item_id')
                ->toArray();

            $memberPrices = Price::where('outlet_id', $selectedOutletId)
                ->pluck('member_price', 'inventory_item_id')
                ->toArray();
        } else {
            $prices = [];
            $memberPrices = [];
        }

        return view('pricing.prices.index', [
            'items' => $items,
            'outlets' => $outlets,
            'selectedOutletId' => $selectedOutletId,
            'prices' => $prices,
            'memberPrices' => $memberPrices,
        ]);
    }

    public function bulkEdit(Request $request): View
    {
        $tenantId = auth()->user()->tenant_id;
        $outlets = Outlet::where('tenant_id', $tenantId)->where('is_active', true)->get();

        $selectedOutletId = $request->outlet_id ?? $outlets->first()?->id;

        $items = InventoryItem::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with(['category', 'unit'])
            ->orderBy('name')
            ->get();

        $prices = [];
        $memberPrices = [];

        if ($selectedOutletId) {
            $priceRecords = Price::where('outlet_id', $selectedOutletId)->get();
            foreach ($priceRecords as $price) {
                $prices[$price->inventory_item_id] = $price->selling_price;
                $memberPrices[$price->inventory_item_id] = $price->member_price;
            }
        }

        return view('pricing.prices.bulk-edit', [
            'items' => $items,
            'outlets' => $outlets,
            'selectedOutletId' => $selectedOutletId,
            'prices' => $prices,
            'memberPrices' => $memberPrices,
        ]);
    }

    public function bulkUpdate(Request $request): RedirectResponse
    {
        $request->validate([
            'outlet_id' => ['required', 'uuid', 'exists:outlets,id'],
            'prices' => ['required', 'array'],
            'prices.*.selling_price' => ['nullable', 'numeric', 'min:0'],
            'prices.*.member_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $tenantId = auth()->user()->tenant_id;
        $outletId = $request->outlet_id;

        foreach ($request->prices as $itemId => $priceData) {
            if (isset($priceData['selling_price']) && $priceData['selling_price'] !== null) {
                $this->priceService->setPrice(
                    $tenantId,
                    $itemId,
                    $outletId,
                    $priceData['selling_price'],
                    $priceData['member_price'] ?? null
                );
            }
        }

        return redirect()->route('pricing.prices.index', ['outlet_id' => $outletId])
            ->with('success', 'Prices updated successfully.');
    }

    public function update(Request $request, Price $price): RedirectResponse
    {
        if ($price->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $request->validate([
            'selling_price' => ['required', 'numeric', 'min:0'],
            'member_price' => ['nullable', 'numeric', 'min:0'],
            'min_selling_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $price->update([
            'selling_price' => $request->selling_price,
            'member_price' => $request->member_price,
            'min_selling_price' => $request->min_selling_price,
        ]);

        return back()->with('success', 'Price updated successfully.');
    }

    public function copy(Request $request): RedirectResponse
    {
        $request->validate([
            'source_outlet_id' => ['required', 'uuid', 'exists:outlets,id'],
            'target_outlet_id' => ['required', 'uuid', 'exists:outlets,id', 'different:source_outlet_id'],
            'adjustment_percentage' => ['nullable', 'numeric', 'min:-100', 'max:1000'],
        ]);

        $count = $this->priceService->copyPrices(
            $request->source_outlet_id,
            $request->target_outlet_id,
            $request->adjustment_percentage
        );

        return redirect()->route('pricing.prices.index', ['outlet_id' => $request->target_outlet_id])
            ->with('success', "{$count} prices copied successfully.");
    }
}
