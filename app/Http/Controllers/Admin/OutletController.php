<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OutletController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $query = Outlet::query()->with('tenant');

        // Non-super admin can only see their tenant's outlets
        if (! $user->isSuperAdmin()) {
            $query->where('tenant_id', $user->tenant_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $outlets = $query->latest()->paginate(15)->withQueryString();

        return view('admin.outlets.index', compact('outlets'));
    }

    public function create(): View|RedirectResponse
    {
        $user = auth()->user();
        $tenants = [];

        if ($user->isSuperAdmin()) {
            $tenants = Tenant::where('is_active', true)->orderBy('name')->get();
        } else {
            // Check if tenant can add more outlets
            $tenant = $user->tenant;
            if ($tenant && ! $tenant->canAddOutlet()) {
                return redirect()->route('admin.outlets.index')
                    ->with('error', 'Anda telah mencapai batas maksimal outlet ('.$tenant->getMaxOutlets().' outlet). Upgrade paket untuk menambah outlet.');
            }
        }

        return view('admin.outlets.create', compact('tenants'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();

        // Check outlet limit for non-super admin
        if (! $user->isSuperAdmin()) {
            $tenant = $user->tenant;
            if ($tenant && ! $tenant->canAddOutlet()) {
                return redirect()->route('admin.outlets.index')
                    ->with('error', 'Anda telah mencapai batas maksimal outlet ('.$tenant->getMaxOutlets().' outlet). Upgrade paket untuk menambah outlet.');
            }
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['boolean'],
        ];

        // Super Admin must select a tenant
        if ($user->isSuperAdmin()) {
            $rules['tenant_id'] = ['required', 'exists:tenants,id'];
        }

        $validated = $request->validate($rules);

        // Set tenant_id based on user
        if (! $user->isSuperAdmin()) {
            $validated['tenant_id'] = $user->tenant_id;
        }

        // Double check for super admin creating outlet for a tenant
        if ($user->isSuperAdmin() && isset($validated['tenant_id'])) {
            $tenant = Tenant::find($validated['tenant_id']);
            if ($tenant && ! $tenant->canAddOutlet()) {
                return redirect()->route('admin.outlets.index')
                    ->with('error', 'Tenant telah mencapai batas maksimal outlet ('.$tenant->getMaxOutlets().' outlet).');
            }
        }

        $validated['is_active'] = $request->boolean('is_active');

        Outlet::create($validated);

        return redirect()->route('admin.outlets.index')
            ->with('success', 'Outlet created successfully.');
    }

    public function show(Outlet $outlet): View
    {
        $this->authorizeOutlet($outlet);
        $outlet->load('tenant');

        return view('admin.outlets.show', compact('outlet'));
    }

    public function edit(Outlet $outlet): View
    {
        $this->authorizeOutlet($outlet);

        return view('admin.outlets.edit', compact('outlet'));
    }

    public function update(Request $request, Outlet $outlet): RedirectResponse
    {
        $this->authorizeOutlet($outlet);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['boolean'],
            'tax_enabled' => ['nullable', 'boolean'],
            'tax_mode' => ['nullable', 'in:exclusive,inclusive'],
            'tax_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'service_charge_enabled' => ['nullable', 'boolean'],
            'service_charge_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        // Handle tax settings - always set the value since the form always shows these checkboxes
        // When checkbox is unchecked, HTML doesn't send the field, so $request->boolean() returns false
        $validated['tax_enabled'] = $request->boolean('tax_enabled');
        $validated['service_charge_enabled'] = $request->boolean('service_charge_enabled');

        // Handle tax_mode - empty string means inherit from tenant (null)
        $validated['tax_mode'] = $request->input('tax_mode') ?: null;

        $outlet->update($validated);

        return redirect()->route('admin.outlets.index')
            ->with('success', __('admin.outlet_updated'));
    }

    public function destroy(Outlet $outlet): RedirectResponse
    {
        $this->authorizeOutlet($outlet);

        // Check if outlet has related data
        if ($outlet->users()->exists()) {
            return back()->with('error', 'Cannot delete outlet with assigned users.');
        }

        $outlet->delete();

        return redirect()->route('admin.outlets.index')
            ->with('success', 'Outlet deleted successfully.');
    }

    private function authorizeOutlet(Outlet $outlet): void
    {
        $user = auth()->user();

        if (! $user->isSuperAdmin() && $outlet->tenant_id !== $user->tenant_id) {
            abort(403, 'Access denied.');
        }
    }

    /**
     * Switch the current user's active outlet.
     */
    public function switchOutlet(Request $request, Outlet $outlet): RedirectResponse
    {
        $user = auth()->user();

        // Verify outlet belongs to user's tenant
        if (! $user->isSuperAdmin() && $outlet->tenant_id !== $user->tenant_id) {
            abort(403, 'Access denied.');
        }

        // Verify user has access to this outlet (is assigned to it or is tenant-owner)
        $isTenantOwner = $user->roles()->where('slug', 'tenant-owner')->exists();
        $hasOutletAccess = $user->outlets()->where('outlets.id', $outlet->id)->exists();

        if (! $isTenantOwner && ! $hasOutletAccess) {
            abort(403, 'You do not have access to this outlet.');
        }

        // If user doesn't have the outlet assigned yet (tenant-owner), auto-assign it
        if (! $hasOutletAccess && $isTenantOwner) {
            $user->outlets()->attach($outlet->id, ['is_default' => false]);
        }

        // Update all user's outlet pivots to set is_default = false
        $user->outlets()->updateExistingPivot(
            $user->outlets()->pluck('outlets.id')->toArray(),
            ['is_default' => false]
        );

        // Set the selected outlet as default
        $user->outlets()->updateExistingPivot($outlet->id, ['is_default' => true]);

        return redirect()->back()->with('success', __('admin.outlet_switched', ['outlet' => $outlet->name]));
    }
}
