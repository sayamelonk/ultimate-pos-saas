<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    /**
     * Show onboarding wizard
     */
    public function index(): View|RedirectResponse
    {
        $user = auth()->user();
        $tenant = $user->tenant;

        // Check if onboarding is completed
        if ($tenant->onboarding_completed_at) {
            return redirect()->route('dashboard');
        }

        // Calculate current step based on what's completed
        $currentStep = $this->getCurrentStep($tenant);

        return view('onboarding.index', [
            'tenant' => $tenant,
            'user' => $user,
            'currentStep' => $currentStep,
            'steps' => $this->getSteps($tenant),
        ]);
    }

    /**
     * Update business settings (Step 1)
     */
    public function updateBusiness(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'phone' => ['nullable', 'string', 'max:20'],
            'timezone' => ['required', 'string', 'timezone'],
            'currency' => ['required', 'string', 'size:3'],
            'tax_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'service_charge_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $tenant = auth()->user()->tenant;

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($tenant->logo) {
                Storage::disk('public')->delete($tenant->logo);
            }
            $validated['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $tenant->update($validated);

        return redirect()->route('onboarding.index')
            ->with('success', 'Business settings saved successfully!');
    }

    /**
     * Add first product (Step 2)
     */
    public function storeProduct(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:50'],
            'category_name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $user = auth()->user();
        $tenant = $user->tenant;
        $outlet = $user->defaultOutlet();

        // Create or find category
        $category = ProductCategory::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'outlet_id' => $outlet->id,
                'name' => $validated['category_name'],
            ],
            [
                'slug' => \Str::slug($validated['category_name']),
                'sort_order' => 0,
                'is_active' => true,
            ]
        );

        // Create product
        $product = Product::create([
            'tenant_id' => $tenant->id,
            'outlet_id' => $outlet->id,
            'category_id' => $category->id,
            'name' => $validated['name'],
            'sku' => $validated['sku'] ?? strtoupper(\Str::random(8)),
            'description' => $validated['description'] ?? null,
            'type' => 'single',
            'base_price' => $validated['price'],
            'is_active' => true,
        ]);

        return redirect()->route('onboarding.index')
            ->with('success', 'Product added successfully!');
    }

    /**
     * Setup payment methods (Step 3)
     */
    public function storePaymentMethods(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'methods' => ['required', 'array', 'min:1'],
            'methods.*' => ['string', 'in:cash,bank_transfer,qris,credit_card,debit_card,e_wallet'],
        ]);

        $user = auth()->user();
        $tenant = $user->tenant;
        $outlet = $user->defaultOutlet();

        $methodConfig = [
            'cash' => ['name' => 'Cash', 'type' => PaymentMethod::TYPE_CASH],
            'bank_transfer' => ['name' => 'Bank Transfer', 'type' => PaymentMethod::TYPE_TRANSFER],
            'qris' => ['name' => 'QRIS', 'type' => PaymentMethod::TYPE_DIGITAL_WALLET],
            'credit_card' => ['name' => 'Credit Card', 'type' => PaymentMethod::TYPE_CARD],
            'debit_card' => ['name' => 'Debit Card', 'type' => PaymentMethod::TYPE_CARD],
            'e_wallet' => ['name' => 'E-Wallet', 'type' => PaymentMethod::TYPE_DIGITAL_WALLET],
        ];

        foreach ($validated['methods'] as $index => $method) {
            $config = $methodConfig[$method] ?? ['name' => ucfirst($method), 'type' => PaymentMethod::TYPE_OTHER];
            PaymentMethod::firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'outlet_id' => $outlet->id,
                    'code' => $method,
                ],
                [
                    'name' => $config['name'],
                    'type' => $config['type'],
                    'is_active' => true,
                    'sort_order' => $index,
                ]
            );
        }

        return redirect()->route('onboarding.index')
            ->with('success', 'Payment methods configured!');
    }

    /**
     * Invite staff (Step 4 - Optional)
     */
    public function inviteStaff(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'staff' => ['nullable', 'array', 'max:3'],
            'staff.*.name' => ['required_with:staff', 'string', 'max:255'],
            'staff.*.email' => ['required_with:staff', 'email', 'unique:users,email'],
            'staff.*.role' => ['required_with:staff', 'string', 'in:cashier,waiter,manager'],
        ]);

        if (! empty($validated['staff'])) {
            $user = auth()->user();
            $tenant = $user->tenant;
            $outlet = $user->defaultOutlet();

            foreach ($validated['staff'] as $staffData) {
                // Create user with temporary password
                $tempPassword = \Str::random(10);

                $newUser = User::create([
                    'tenant_id' => $tenant->id,
                    'name' => $staffData['name'],
                    'email' => $staffData['email'],
                    'password' => Hash::make($tempPassword),
                    'is_active' => true,
                ]);

                // Assign role
                $role = Role::where('slug', $staffData['role'])
                    ->whereNull('tenant_id')
                    ->first();

                if ($role) {
                    $newUser->roles()->attach($role->id);
                }

                // Assign to outlet
                $newUser->outlets()->attach($outlet->id, ['is_default' => true]);

                // TODO: Send invitation email with temporary password
            }
        }

        return redirect()->route('onboarding.index')
            ->with('success', 'Staff invitations sent!');
    }

    /**
     * Complete onboarding
     */
    public function complete(): RedirectResponse
    {
        $tenant = auth()->user()->tenant;

        $tenant->update([
            'onboarding_completed_at' => now(),
        ]);

        return redirect()->route('dashboard')
            ->with('success', 'Setup complete! Welcome to Ultimate POS.');
    }

    /**
     * Skip onboarding
     */
    public function skip(): RedirectResponse
    {
        $tenant = auth()->user()->tenant;

        $tenant->update([
            'onboarding_completed_at' => now(),
        ]);

        return redirect()->route('dashboard')
            ->with('info', 'You can configure these settings later from the Settings menu.');
    }

    /**
     * Get current step based on tenant data
     */
    protected function getCurrentStep($tenant): int
    {
        $user = auth()->user();
        $outlet = $user->defaultOutlet();

        // Step 1: Business settings
        if (! $tenant->timezone || $tenant->timezone === 'UTC') {
            return 1;
        }

        // Step 2: First product
        if (! $outlet || Product::where('outlet_id', $outlet->id)->count() === 0) {
            return 2;
        }

        // Step 3: Payment methods
        if (! $outlet || PaymentMethod::where('outlet_id', $outlet->id)->count() === 0) {
            return 3;
        }

        // Step 4: Invite staff (optional, show if only 1 user)
        if ($tenant->users()->count() === 1) {
            return 4;
        }

        return 4;
    }

    /**
     * Get steps with completion status
     */
    protected function getSteps($tenant): array
    {
        $user = auth()->user();
        $outlet = $user->defaultOutlet();

        return [
            1 => [
                'title' => 'Business Settings',
                'description' => 'Configure your business details',
                'completed' => $tenant->timezone && $tenant->timezone !== 'UTC',
            ],
            2 => [
                'title' => 'Add First Product',
                'description' => 'Create your first menu item',
                'completed' => $outlet && Product::where('outlet_id', $outlet->id)->count() > 0,
            ],
            3 => [
                'title' => 'Payment Methods',
                'description' => 'Setup how you accept payments',
                'completed' => $outlet && PaymentMethod::where('outlet_id', $outlet->id)->count() > 0,
            ],
            4 => [
                'title' => 'Invite Staff',
                'description' => 'Add team members (optional)',
                'completed' => $tenant->users()->count() > 1,
                'optional' => true,
            ],
        ];
    }
}
