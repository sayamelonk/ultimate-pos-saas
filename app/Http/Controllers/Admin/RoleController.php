<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $query = Role::query()->withCount(['users', 'permissions']);

        // Non-super admin can only see their tenant's custom roles and system roles (except super-admin)
        if (! $user->isSuperAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->whereNull('tenant_id')
                    ->where('slug', '!=', 'super-admin')
                    ->orWhere('tenant_id', $user->tenant_id);
            });
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $roles = $query->orderBy('is_system', 'desc')->orderBy('name')->paginate(15)->withQueryString();

        return view('admin.roles.index', compact('roles'));
    }

    public function create(): View
    {
        return view('admin.roles.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_system'] = false;

        // Set tenant_id for non-super admin
        if (! $user->isSuperAdmin()) {
            $validated['tenant_id'] = $user->tenant_id;
        }

        // Check for duplicate slug
        $existsQuery = Role::where('slug', $validated['slug']);
        if (isset($validated['tenant_id'])) {
            $existsQuery->where(function ($q) use ($validated) {
                $q->whereNull('tenant_id')
                    ->orWhere('tenant_id', $validated['tenant_id']);
            });
        }

        if ($existsQuery->exists()) {
            return back()->withInput()->withErrors(['name' => 'A role with this name already exists.']);
        }

        $role = Role::create($validated);

        return redirect()->route('admin.roles.permissions', $role)
            ->with('success', 'Role created. Now assign permissions.');
    }

    public function show(Role $role): View
    {
        $this->authorizeRole($role);
        $role->load(['permissions', 'users' => fn ($q) => $q->limit(10)]);

        return view('admin.roles.show', compact('role'));
    }

    public function edit(Role $role): View
    {
        $this->authorizeRole($role);

        if ($role->is_system) {
            return redirect()->route('admin.roles.index')
                ->with('error', 'System roles cannot be edited.');
        }

        return view('admin.roles.edit', compact('role'));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->authorizeRole($role);

        if ($role->is_system) {
            return back()->with('error', 'System roles cannot be edited.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $role->update($validated);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorizeRole($role);

        if ($role->is_system) {
            return back()->with('error', 'System roles cannot be deleted.');
        }

        if ($role->users()->exists()) {
            return back()->with('error', 'Cannot delete role with assigned users.');
        }

        $role->permissions()->detach();
        $role->delete();

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role deleted successfully.');
    }

    public function permissions(Role $role): View
    {
        $this->authorizeRole($role);

        $permissions = Permission::orderBy('module')->orderBy('name')->get()->groupBy('module');
        $rolePermissions = $role->permissions->pluck('id')->toArray();

        return view('admin.roles.permissions', compact('role', 'permissions', 'rolePermissions'));
    }

    public function updatePermissions(Request $request, Role $role): RedirectResponse
    {
        $this->authorizeRole($role);

        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $role->permissions()->sync($validated['permissions'] ?? []);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Permissions updated successfully.');
    }

    private function authorizeRole(Role $role): void
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return;
        }

        // Non-super admin cannot access super-admin role at all
        if ($role->slug === 'super-admin') {
            abort(403, 'Access denied. Super Admin role is restricted.');
        }

        // Non-super admin can only manage their tenant's roles or view system roles
        if ($role->tenant_id !== null && $role->tenant_id !== $user->tenant_id) {
            abort(403, 'Access denied.');
        }
    }
}
