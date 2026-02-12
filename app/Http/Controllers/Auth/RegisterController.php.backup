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
    /**
     * Tampilkan halaman register
     */
    public function showRegistrationForm(): View
    {
        return view('auth.register');
    }

    /**
     * Proses register
     */
    public function register(Request $request): RedirectResponse
    {
        // 1. Validasi input
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'business_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        // 2. Gunakan Database Transaction
        // Ini memastikan SEMUA operasi berhasil bersama-sama
        // Jika ada yang gagal, semua akan di-rollback
        $user = DB::transaction(function () use ($validated) {
            // 2a. Create Tenant
            $tenant = Tenant::create([
                'name' => $validated['business_name'],
                'code' => strtoupper(Str::random(6)), // Generate random code
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'currency' => 'IDR',
                'timezone' => 'Asia/Jakarta',
                'tax_percentage' => 11.00,
                'service_charge_percentage' => 0,
                'subscription_plan' => 'free', // Default plan
                'max_outlets' => 1, // Free plan: 1 outlet
                'is_active' => true,
            ]);

            // 2b. Create Default Outlet
            $outlet = Outlet::create([
                'tenant_id' => $tenant->id,
                'code' => 'MAIN',
                'name' => 'Main Outlet',
                'address' => null,
                'city' => null,
                'province' => null,
                'postal_code' => null,
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'],
                'opening_time' => '08:00',
                'closing_time' => '22:00',
                'is_active' => true,
            ]);

            // 2c. Create User
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'] ?? null,
                'is_active' => true,
            ]);

            // 2d. Assign Tenant Owner Role
            $ownerRole = Role::where('slug', 'tenant-owner')
                ->whereNull('tenant_id') // System role
                ->first();

            if ($ownerRole) {
                $user->roles()->attach($ownerRole->id);
            }

            // 2e. Assign User ke Default Outlet
            $user->outlets()->attach($outlet->id, ['is_default' => true]);

            return $user;
        });

        // 3. Fire Registered Event
        // Untuk notifikasi, email verification, dll (optional)
        event(new Registered($user));

        // 4. Login user otomatis
        Auth::login($user);

        // 5. Redirect ke dashboard
        return redirect()->route('admin.dashboard')
            ->with('success', 'Welcome! Your account has been created successfully.');
    }
}
