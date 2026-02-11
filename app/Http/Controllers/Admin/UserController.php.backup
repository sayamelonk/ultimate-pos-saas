<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $query = User::query()->with(['tenant', 'roles', 'outlets']);

        // Non-super admin can only see their tenant's users
        if (! $user->isSuperAdmin()) {
            $query->where('tenant_id', $user->tenant_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('slug', $request->role));
        }

        $users = $query->latest()->paginate(15)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        $user = auth()->user();

        $roles = Role::query()
            ->when(! $user->isSuperAdmin(), fn ($q) => $q->where('slug', '!=', 'super-admin'))
            ->get();

        $outlets = Outlet::query()
            ->when(! $user->isSuperAdmin(), fn ($q) => $q->where('tenant_id', $user->tenant_id))
            ->where('is_active', true)
            ->get();

        return view('admin.users.create', compact('roles', 'outlets'));
    }

    public function store(Request $request): RedirectResponse
    {
        $authUser = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'phone' => ['nullable', 'string', 'max:20'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['exists:roles,id'],
            'outlets' => ['nullable', 'array'],
            'outlets.*' => ['exists:outlets,id'],
            'is_active' => ['boolean'],
        ]);

        // Set tenant_id based on user
        if ($authUser->isSuperAdmin()) {
            $validated['tenant_id'] = $request->input('tenant_id');
        } else {
            $validated['tenant_id'] = $authUser->tenant_id;
        }

        $user = User::create([
            'tenant_id' => $validated['tenant_id'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Attach roles
        $user->roles()->attach($validated['roles']);

        // Attach outlets
        if (! empty($validated['outlets'])) {
            $outletsData = [];
            foreach ($validated['outlets'] as $index => $outletId) {
                $outletsData[$outletId] = ['is_default' => $index === 0];
            }
            $user->outlets()->attach($outletsData);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function show(User $user): View
    {
        $this->authorizeUser($user);
        $user->load(['tenant', 'roles', 'outlets']);

        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user): View
    {
        $this->authorizeUser($user);

        $authUser = auth()->user();

        $roles = Role::query()
            ->when(! $authUser->isSuperAdmin(), fn ($q) => $q->where('slug', '!=', 'super-admin'))
            ->get();

        $outlets = Outlet::query()
            ->when(! $authUser->isSuperAdmin(), fn ($q) => $q->where('tenant_id', $authUser->tenant_id))
            ->where('is_active', true)
            ->get();

        $userRoles = $user->roles->pluck('id')->toArray();
        $userOutlets = $user->outlets->pluck('id')->toArray();

        return view('admin.users.edit', compact('user', 'roles', 'outlets', 'userRoles', 'userOutlets'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeUser($user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'phone' => ['nullable', 'string', 'max:20'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['exists:roles,id'],
            'outlets' => ['nullable', 'array'],
            'outlets.*' => ['exists:outlets,id'],
            'is_active' => ['boolean'],
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        // Sync roles
        $user->roles()->sync($validated['roles']);

        // Sync outlets
        if (! empty($validated['outlets'])) {
            $outletsData = [];
            foreach ($validated['outlets'] as $index => $outletId) {
                $outletsData[$outletId] = ['is_default' => $index === 0];
            }
            $user->outlets()->sync($outletsData);
        } else {
            $user->outlets()->detach();
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorizeUser($user);

        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        // Prevent deleting super admin
        if ($user->isSuperAdmin()) {
            return back()->with('error', 'Cannot delete super admin user.');
        }

        $user->roles()->detach();
        $user->outlets()->detach();
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }

    private function authorizeUser(User $user): void
    {
        $authUser = auth()->user();

        if (! $authUser->isSuperAdmin() && $user->tenant_id !== $authUser->tenant_id) {
            abort(403, 'Access denied.');
        }
    }
}
