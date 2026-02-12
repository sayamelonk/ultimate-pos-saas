<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\HeldOrder;
use App\Services\PosSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HeldOrderController extends Controller
{
    public function __construct(
        private PosSessionService $sessionService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $outletId = $request->header('X-Outlet-Id') ?? $user->defaultOutlet()?->id;

        if (! $outletId) {
            return response()->json(['held_orders' => []]);
        }

        $session = $this->sessionService->getOpenSession($user->id, $outletId);

        $query = HeldOrder::where('tenant_id', $user->tenant_id)
            ->where('outlet_id', $outletId)
            ->with('customer:id,name,phone')
            ->orderBy('created_at', 'desc');

        if ($request->boolean('current_session_only') && $session) {
            $query->where('pos_session_id', $session->id);
        }

        $heldOrders = $query->get()->map(fn ($order) => [
            'id' => $order->id,
            'uuid' => $order->uuid,
            'hold_number' => $order->hold_number,
            'reference' => $order->reference,
            'table_number' => $order->table_number,
            'display_name' => $order->getDisplayName(),
            'items' => $order->items,
            'discounts' => $order->discounts,
            'item_count' => $order->getItemCount(),
            'subtotal' => $order->subtotal,
            'discount_amount' => $order->discount_amount,
            'tax_amount' => $order->tax_amount,
            'service_charge_amount' => $order->service_charge_amount,
            'grand_total' => $order->grand_total,
            'notes' => $order->notes,
            'customer' => $order->customer ? [
                'id' => $order->customer->id,
                'name' => $order->customer->name,
                'phone' => $order->customer->phone,
            ] : null,
            'customer_id' => $order->customer_id,
            'is_expired' => $order->isExpired(),
            'created_at' => $order->created_at->format('H:i'),
            'created_at_full' => $order->created_at->format('Y-m-d H:i:s'),
        ]);

        return response()->json([
            'held_orders' => $heldOrders,
            'count' => $heldOrders->count(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required'],
            'items.*.name' => ['required', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'discounts' => ['nullable', 'array'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'reference' => ['nullable', 'string', 'max:100'],
            'table_number' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:500'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'service_charge_amount' => ['nullable', 'numeric', 'min:0'],
            'grand_total' => ['required', 'numeric', 'min:0'],
        ]);

        $user = auth()->user();
        $outletId = $request->header('X-Outlet-Id') ?? $user->defaultOutlet()?->id;

        if (! $outletId) {
            return response()->json([
                'success' => false,
                'message' => 'No outlet selected.',
            ], 400);
        }

        $session = $this->sessionService->getOpenSession($user->id, $outletId);

        if (! $session) {
            return response()->json([
                'success' => false,
                'message' => 'No open session. Please open a session first.',
            ], 400);
        }

        $heldOrder = HeldOrder::create([
            'tenant_id' => $user->tenant_id,
            'outlet_id' => $outletId,
            'pos_session_id' => $session->id,
            'user_id' => $user->id,
            'customer_id' => $request->customer_id,
            'hold_number' => HeldOrder::generateHoldNumber($outletId),
            'reference' => $request->reference,
            'table_number' => $request->table_number,
            'items' => $request->items,
            'discounts' => $request->discounts,
            'subtotal' => $request->subtotal,
            'discount_amount' => $request->discount_amount ?? 0,
            'tax_amount' => $request->tax_amount ?? 0,
            'service_charge_amount' => $request->service_charge_amount ?? 0,
            'grand_total' => $request->grand_total,
            'notes' => $request->notes,
            'expires_at' => now()->addHours(24),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order held successfully.',
            'held_order' => [
                'id' => $heldOrder->id,
                'uuid' => $heldOrder->uuid,
                'hold_number' => $heldOrder->hold_number,
                'display_name' => $heldOrder->getDisplayName(),
            ],
        ]);
    }

    public function show(HeldOrder $heldOrder): JsonResponse
    {
        if ($heldOrder->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $heldOrder->load('customer:id,name,phone,email,membership_level,total_points');

        return response()->json([
            'held_order' => [
                'id' => $heldOrder->id,
                'uuid' => $heldOrder->uuid,
                'hold_number' => $heldOrder->hold_number,
                'reference' => $heldOrder->reference,
                'table_number' => $heldOrder->table_number,
                'display_name' => $heldOrder->getDisplayName(),
                'items' => $heldOrder->items,
                'discounts' => $heldOrder->discounts,
                'item_count' => $heldOrder->getItemCount(),
                'subtotal' => $heldOrder->subtotal,
                'discount_amount' => $heldOrder->discount_amount,
                'tax_amount' => $heldOrder->tax_amount,
                'service_charge_amount' => $heldOrder->service_charge_amount,
                'grand_total' => $heldOrder->grand_total,
                'notes' => $heldOrder->notes,
                'customer' => $heldOrder->customer,
                'customer_id' => $heldOrder->customer_id,
                'is_expired' => $heldOrder->isExpired(),
                'created_at' => $heldOrder->created_at->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    public function update(Request $request, HeldOrder $heldOrder): JsonResponse
    {
        if ($heldOrder->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'discounts' => ['nullable', 'array'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'reference' => ['nullable', 'string', 'max:100'],
            'table_number' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:500'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'service_charge_amount' => ['nullable', 'numeric', 'min:0'],
            'grand_total' => ['required', 'numeric', 'min:0'],
        ]);

        $heldOrder->update([
            'items' => $request->items,
            'discounts' => $request->discounts,
            'customer_id' => $request->customer_id,
            'reference' => $request->reference,
            'table_number' => $request->table_number,
            'notes' => $request->notes,
            'subtotal' => $request->subtotal,
            'discount_amount' => $request->discount_amount ?? 0,
            'tax_amount' => $request->tax_amount ?? 0,
            'service_charge_amount' => $request->service_charge_amount ?? 0,
            'grand_total' => $request->grand_total,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Held order updated successfully.',
        ]);
    }

    public function destroy(HeldOrder $heldOrder): JsonResponse
    {
        if ($heldOrder->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $heldOrder->delete();

        return response()->json([
            'success' => true,
            'message' => 'Held order deleted successfully.',
        ]);
    }

    public function recall(HeldOrder $heldOrder): JsonResponse
    {
        if ($heldOrder->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $heldOrder->load('customer:id,name,phone,email,membership_level,total_points');

        $data = [
            'id' => $heldOrder->id,
            'uuid' => $heldOrder->uuid,
            'hold_number' => $heldOrder->hold_number,
            'reference' => $heldOrder->reference,
            'table_number' => $heldOrder->table_number,
            'items' => $heldOrder->items,
            'discounts' => $heldOrder->discounts,
            'subtotal' => $heldOrder->subtotal,
            'discount_amount' => $heldOrder->discount_amount,
            'tax_amount' => $heldOrder->tax_amount,
            'service_charge_amount' => $heldOrder->service_charge_amount,
            'grand_total' => $heldOrder->grand_total,
            'notes' => $heldOrder->notes,
            'customer' => $heldOrder->customer,
            'customer_id' => $heldOrder->customer_id,
        ];

        $heldOrder->delete();

        return response()->json([
            'success' => true,
            'message' => 'Order recalled successfully.',
            'order_data' => $data,
        ]);
    }
}
