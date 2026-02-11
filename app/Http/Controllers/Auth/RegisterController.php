<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function showRegistrationForm(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'business_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $user = DB::transaction(function () use ($validated) {
            // Generate unique tenant code
            $baseCode = strtoupper(Str::substr(Str::slug($validated['business_name'], ''), 0, 6));
            $code = $baseCode.strtoupper(Str::random(4));

            // Ensure code is unique
            while (Tenant::where('code', $code)->exists()) {
                $code = $baseCode.strtoupper(Str::random(4));
            }

            // Create tenant
            $tenant = Tenant::create([
                'code' => $code,
                'name' => $validated['business_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'is_active' => true,
            ]);

            // Create default outlet for the tenant
            $outlet = Outlet::create([
                'tenant_id' => $tenant->id,
                'name' => 'Main Outlet',
                'code' => 'MAIN',
                'address' => null,
                'phone' => $validated['phone'] ?? null,
                'is_active' => true,
            ]);

            // Create user
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'] ?? null,
                'is_active' => true,
            ]);

            // Assign tenant-owner role
            $ownerRole = Role::where('slug', 'tenant-owner')
                ->whereNull('tenant_id')
                ->first();

            if ($ownerRole) {
                $user->roles()->attach($ownerRole->id);
            }

            // Assign user to the default outlet
            $user->outlets()->attach($outlet->id, ['is_default' => true]);

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('admin.dashboard')
            ->with('success', 'Welcome! Your account has been created successfully.');
    }
}
