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
        $query = Outlet::query()->with('tenant')->withCount('users');

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

    public function create(): View
    {
        $user = auth()->user();
        $tenants = [];

        if ($user->isSuperAdmin()) {
            $tenants = Tenant::where('is_active', true)->orderBy('name')->get();
        }

        return view('admin.outlets.create', compact('tenants'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();

        // Get tenant_id from request or user
        $tenantId = $user->isSuperAdmin() ? $request->tenant_id : $user->tenant_id;

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', 'unique:outlets,code,NULL,id,tenant_id,'.$tenantId],
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

        $validated['is_active'] = $request->boolean('is_active', true);

        $outlet = Outlet::create($validated);

        // Auto-assign the creating user to the outlet
        // Only for non-super admin users
        if (! $user->isSuperAdmin()) {
            $user->outlets()->attach($outlet->id, ['is_default' => false]);
        }

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
            'code' => ['required', 'string', 'max:20', 'unique:outlets,code,'.$outlet->id.',id,tenant_id,'.$outlet->tenant_id],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $outlet->update($validated);

        return redirect()->route('admin.outlets.index')
            ->with('success', 'Outlet updated successfully.');
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
}
