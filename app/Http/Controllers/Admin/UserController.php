<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuthorizationSetting;
use App\Models\Outlet;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Authorization\AuthorizationService;
use Illuminate\Http\JsonResponse;
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

        // For non-super admin, get outlets from their tenant
        // For super admin, outlets will be loaded dynamically via AJAX
        $outlets = collect();
        $tenants = collect();

        if ($user->isSuperAdmin()) {
            $tenants = Tenant::where('is_active', true)
                ->orderBy('name')
                ->get();
        } else {
            $outlets = Outlet::where('tenant_id', $user->tenant_id)
                ->where('is_active', true)
                ->get();
        }

        $pinLength = 4;
        if (! $user->isSuperAdmin() && $user->tenant_id) {
            $pinLength = AuthorizationSetting::getForTenant($user->tenant_id)->pin_length;
        }

        return view('admin.users.create', compact('roles', 'outlets', 'tenants', 'pinLength'));
    }

    public function store(Request $request, AuthorizationService $authorizationService): RedirectResponse
    {
        $authUser = auth()->user();

        $pinLength = 4;
        if (! $authUser->isSuperAdmin() && $authUser->tenant_id) {
            $pinLength = AuthorizationSetting::getForTenant($authUser->tenant_id)->pin_length;
        }

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
            'pin' => ['nullable', 'digits:'.$pinLength, 'confirmed'],
        ]);

        // Determine tenant_id
        $tenantId = null;
        if ($authUser->isSuperAdmin()) {
            // For super admin, get tenant from first selected outlet
            if (! empty($validated['outlets'])) {
                $firstOutlet = Outlet::find($validated['outlets'][0]);
                $tenantId = $firstOutlet?->tenant_id;
            }
        } else {
            $tenantId = $authUser->tenant_id;
        }

        $user = User::create([
            'tenant_id' => $tenantId,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        // Set PIN if provided
        if (! empty($validated['pin'])) {
            $authorizationService->setUserPin($user->id, $validated['pin']);
        }

        // Attach roles
        $user->roles()->attach($validated['roles']);

        // Attach outlets and update tenant_id if needed
        if (! empty($validated['outlets'])) {
            $outletsData = [];
            foreach ($validated['outlets'] as $index => $outletId) {
                $outletsData[$outletId] = ['is_default' => $index === 0];
            }
            $user->outlets()->attach($outletsData);

            // Ensure tenant_id is set from first outlet if still null
            if (! $user->tenant_id) {
                $firstOutlet = Outlet::find($validated['outlets'][0]);
                if ($firstOutlet) {
                    $user->update(['tenant_id' => $firstOutlet->tenant_id]);
                }
            }
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

        // For edit, show outlets from the user's tenant (not from logged-in user's tenant)
        // This allows super admin to edit users from any tenant
        $targetTenantId = $user->tenant_id ?? $authUser->tenant_id;

        $outlets = Outlet::where('tenant_id', $targetTenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $userRoles = $user->roles->pluck('id')->toArray();
        $userOutlets = $user->outlets->pluck('id')->toArray();

        $pinLength = AuthorizationSetting::getForTenant($targetTenantId)->pin_length;
        $hasPin = $user->hasPin();

        return view('admin.users.edit', compact('user', 'roles', 'outlets', 'userRoles', 'userOutlets', 'pinLength', 'hasPin'));
    }

    public function update(Request $request, User $user, AuthorizationService $authorizationService): RedirectResponse
    {
        $this->authorizeUser($user);

        $targetTenantId = $user->tenant_id ?? auth()->user()->tenant_id;
        $pinLength = AuthorizationSetting::getForTenant($targetTenantId)->pin_length;

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
            'pin' => ['nullable', 'digits:'.$pinLength, 'confirmed'],
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        // Set PIN if provided
        if (! empty($validated['pin'])) {
            $authorizationService->setUserPin($user->id, $validated['pin']);
        }

        // Sync roles
        $user->roles()->sync($validated['roles']);

        // Sync outlets and update tenant_id
        if (! empty($validated['outlets'])) {
            $outletsData = [];
            foreach ($validated['outlets'] as $index => $outletId) {
                $outletsData[$outletId] = ['is_default' => $index === 0];
            }
            $user->outlets()->sync($outletsData);

            // Update tenant_id from first outlet
            $firstOutlet = Outlet::find($validated['outlets'][0]);
            if ($firstOutlet && $user->tenant_id !== $firstOutlet->tenant_id) {
                $user->update(['tenant_id' => $firstOutlet->tenant_id]);
            }
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

    /**
     * Get outlets for a specific tenant (AJAX endpoint for Super Admin)
     */
    public function getOutletsByTenant(Tenant $tenant): JsonResponse
    {
        // Only super admin can access this
        if (! auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $outlets = Outlet::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return response()->json($outlets);
    }

    private function authorizeUser(User $user): void
    {
        $authUser = auth()->user();

        if (! $authUser->isSuperAdmin() && $user->tenant_id !== $authUser->tenant_id) {
            abort(403, 'Access denied.');
        }
    }
}
