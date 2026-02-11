<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeSuperAdmin();
        $query = Tenant::query()->withCount(['outlets', 'users']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $tenants = $query->latest()->paginate(15)->withQueryString();

        return view('admin.tenants.index', compact('tenants'));
    }

    public function create(): View
    {
        $this->authorizeSuperAdmin();

        return view('admin.tenants.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ]);

        $validated['code'] = strtoupper(Str::slug($validated['name'], '')).'-'.strtoupper(Str::random(4));
        $validated['is_active'] = $request->boolean('is_active');

        Tenant::create($validated);

        return redirect()->route('admin.tenants.index')
            ->with('success', 'Tenant created successfully.');
    }

    public function show(Tenant $tenant): View
    {
        $this->authorizeSuperAdmin();

        $tenant->loadCount(['outlets', 'users']);
        $tenant->load(['outlets', 'users' => fn ($q) => $q->limit(10)]);

        return view('admin.tenants.show', compact('tenant'));
    }

    public function edit(Tenant $tenant): View
    {
        $this->authorizeSuperAdmin();

        return view('admin.tenants.edit', compact('tenant'));
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $tenant->update($validated);

        return redirect()->route('admin.tenants.index')
            ->with('success', 'Tenant updated successfully.');
    }

    public function destroy(Tenant $tenant): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        if ($tenant->users()->exists() || $tenant->outlets()->exists()) {
            return back()->with('error', 'Cannot delete tenant with existing users or outlets.');
        }

        $tenant->delete();

        return redirect()->route('admin.tenants.index')
            ->with('success', 'Tenant deleted successfully.');
    }

    /**
     * Switch to manage a specific tenant (Super Admin only)
     */
    public function switchTenant(Tenant $tenant): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        session(['current_tenant_id' => $tenant->id, 'current_tenant_name' => $tenant->name]);

        return redirect()->back()
            ->with('success', "Switched to tenant: {$tenant->name}");
    }

    /**
     * Clear the current tenant context (Super Admin only)
     */
    public function clearTenant(): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        session()->forget(['current_tenant_id', 'current_tenant_name']);

        return redirect()->route('admin.tenants.index')
            ->with('success', 'Tenant context cleared.');
    }

    private function authorizeSuperAdmin(): void
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403, 'Access denied. Super Admin only.');
        }
    }
}
