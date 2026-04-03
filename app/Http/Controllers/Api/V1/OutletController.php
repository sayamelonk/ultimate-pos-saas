<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Outlet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OutletController extends Controller
{
    /**
     * List user's accessible outlets
     *
     * GET /api/v1/outlets
     */
    public function index(): JsonResponse
    {
        $user = $this->user();

        if ($user->isSuperAdmin()) {
            $outlets = Outlet::where('is_active', true)->get();
        } elseif ($user->isTenantOwner()) {
            $outlets = Outlet::where('tenant_id', $user->tenant_id)
                ->where('is_active', true)
                ->get();
        } else {
            $outlets = $user->outlets()->where('is_active', true)->get();
        }

        $data = $outlets->map(fn ($outlet) => $this->formatOutlet($outlet, $user));

        return $this->success($data);
    }

    /**
     * Get outlet details
     *
     * GET /api/v1/outlets/{outlet}
     */
    public function show(Outlet $outlet): JsonResponse
    {
        if (! $this->canAccessOutlet($outlet->id)) {
            return $this->forbidden('You do not have access to this outlet.');
        }

        return $this->success($this->formatOutlet($outlet, $this->user()));
    }

    /**
     * Switch active outlet (for session)
     *
     * POST /api/v1/outlets/switch
     */
    public function switch(Request $request): JsonResponse
    {
        $request->validate([
            'outlet_id' => 'required|uuid|exists:outlets,id',
        ]);

        if (! $this->canAccessOutlet($request->outlet_id)) {
            return $this->forbidden('You do not have access to this outlet.');
        }

        $outlet = Outlet::find($request->outlet_id);

        return $this->success([
            'outlet' => $this->formatOutlet($outlet, $this->user()),
        ], 'Outlet switched successfully');
    }

    /**
     * Format outlet data
     */
    private function formatOutlet(Outlet $outlet, $user): array
    {
        $isDefault = false;

        if ($user->outlets()->where('outlets.id', $outlet->id)->exists()) {
            $pivot = $user->outlets()->where('outlets.id', $outlet->id)->first()?->pivot;
            $isDefault = $pivot?->is_default ?? false;
        }

        return [
            'id' => $outlet->id,
            'code' => $outlet->code,
            'name' => $outlet->name,
            'address' => $outlet->address,
            'city' => $outlet->city,
            'province' => $outlet->province,
            'postal_code' => $outlet->postal_code,
            'phone' => $outlet->phone,
            'email' => $outlet->email,
            'latitude' => $outlet->latitude,
            'longitude' => $outlet->longitude,
            'opening_time' => $outlet->opening_time,
            'closing_time' => $outlet->closing_time,
            'tax_percentage' => (float) $outlet->tax_percentage,
            'service_charge_percentage' => (float) $outlet->service_charge_percentage,
            'receipt_header' => $outlet->receipt_header,
            'receipt_footer' => $outlet->receipt_footer,
            'receipt_show_logo' => (bool) $outlet->receipt_show_logo,
            'is_default' => $isDefault,
            'is_active' => (bool) $outlet->is_active,
        ];
    }
}
