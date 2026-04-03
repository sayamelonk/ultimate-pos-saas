<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Outlet;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    #[OA\Post(
        path: '/auth/login',
        summary: 'Login with email and password',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password', 'device_name'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@example.com'),
                    new OA\Property(property: 'password', type: 'string', example: 'password'),
                    new OA\Property(property: 'device_name', type: 'string', example: 'POS Terminal 1'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login successful',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Login successful'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'user', type: 'object'),
                                new OA\Property(property: 'token', type: 'string'),
                                new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'required|string|max:255',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->is_active) {
            return $this->error('Your account is inactive. Please contact administrator.', 403);
        }

        // Update last login
        $user->update(['last_login_at' => now()]);

        // Create token
        $token = $user->createToken($request->device_name)->plainTextToken;

        return $this->success([
            'user' => $this->formatUserData($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Login successful');
    }

    #[OA\Post(
        path: '/auth/pin-login',
        summary: 'Login with PIN (quick login for POS)',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['outlet_id', 'pin', 'device_name'],
                properties: [
                    new OA\Property(property: 'outlet_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'pin', type: 'string', minLength: 6, maxLength: 6, example: '123456'),
                    new OA\Property(property: 'device_name', type: 'string', example: 'POS Terminal 1'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Login successful'),
            new OA\Response(response: 401, description: 'Invalid PIN'),
            new OA\Response(response: 403, description: 'Account inactive or no outlet access'),
            new OA\Response(response: 404, description: 'Outlet not found'),
        ]
    )]
    public function pinLogin(Request $request): JsonResponse
    {
        $request->validate([
            'outlet_id' => 'required|uuid|exists:outlets,id',
            'pin' => 'required|string|size:6',
            'device_name' => 'required|string|max:255',
        ]);

        // Find outlet first
        $outlet = Outlet::find($request->outlet_id);

        if (! $outlet || ! $outlet->is_active) {
            return $this->error('Outlet not found or inactive.', 404);
        }

        // Find user by PIN in the outlet's tenant
        $user = User::where('tenant_id', $outlet->tenant_id)
            ->whereHas('userPin', function ($query) {
                $query->where('is_active', true);
            })
            ->get()
            ->first(function ($user) use ($request) {
                return $user->verifyPin($request->pin);
            });

        if (! $user) {
            return $this->error('Invalid PIN.', 401);
        }

        if (! $user->is_active) {
            return $this->error('Your account is inactive.', 403);
        }

        // Check if user can access the outlet
        if (! $user->canAccessOutlet($request->outlet_id)) {
            return $this->error('You do not have access to this outlet.', 403);
        }

        // Update last login
        $user->update(['last_login_at' => now()]);

        // Create token
        $token = $user->createToken($request->device_name)->plainTextToken;

        return $this->success([
            'user' => $this->formatUserData($user, $outlet),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Login successful');
    }

    #[OA\Post(
        path: '/auth/logout',
        summary: 'Logout (revoke current token)',
        security: [['sanctum' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Logged out successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Logged out successfully');
    }

    #[OA\Get(
        path: '/auth/me',
        summary: 'Get current user profile',
        security: [['sanctum' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'User profile data'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $outlet = $this->currentOutlet($request);

        return $this->success([
            'user' => $this->formatUserData($user, $outlet),
        ]);
    }

    #[OA\Put(
        path: '/auth/profile',
        summary: 'Update profile',
        security: [['sanctum' => []]],
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255),
                    new OA\Property(property: 'phone', type: 'string', maxLength: 20),
                    new OA\Property(property: 'locale', type: 'string', enum: ['en', 'id']),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Profile updated successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'locale' => 'sometimes|string|in:en,id',
        ]);

        $user->update($validated);

        return $this->success([
            'user' => $this->formatUserData($user),
        ], 'Profile updated successfully');
    }

    #[OA\Put(
        path: '/auth/pin',
        summary: 'Update PIN',
        security: [['sanctum' => []]],
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['current_password', 'pin'],
                properties: [
                    new OA\Property(property: 'current_password', type: 'string'),
                    new OA\Property(property: 'pin', type: 'string', minLength: 6, maxLength: 6, example: '123456'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'PIN updated successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Current password incorrect'),
        ]
    )]
    public function updatePin(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'current_password' => 'required|string',
            'pin' => 'required|string|size:6|regex:/^[0-9]+$/',
        ]);

        // Verify current password
        if (! Hash::check($request->current_password, $user->password)) {
            return $this->error('Current password is incorrect.', 422);
        }

        // Update or create PIN
        $user->userPin()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'pin' => Hash::make($request->pin),
                'is_active' => true,
            ]
        );

        return $this->success(null, 'PIN updated successfully');
    }

    /**
     * Format user data for response
     */
    private function formatUserData(User $user, ?Outlet $currentOutlet = null): array
    {
        $user->load(['roles', 'outlets']);

        $roles = $user->roles->pluck('slug')->toArray();
        $permissions = $this->getUserPermissions($user);

        $outlets = $user->outlets->map(function ($outlet) {
            return [
                'id' => $outlet->id,
                'code' => $outlet->code,
                'name' => $outlet->name,
                'is_default' => (bool) $outlet->pivot->is_default,
            ];
        });

        $defaultOutlet = $currentOutlet ?? $user->defaultOutlet();

        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $user->avatar,
            'locale' => $user->locale ?? 'id',
            'has_pin' => $user->hasPin(),
            'roles' => $roles,
            'permissions' => $permissions,
            'outlets' => $outlets,
        ];

        if ($defaultOutlet) {
            $data['current_outlet'] = [
                'id' => $defaultOutlet->id,
                'code' => $defaultOutlet->code,
                'name' => $defaultOutlet->name,
                'address' => $defaultOutlet->address,
                'phone' => $defaultOutlet->phone,
                'tax_percentage' => (float) $defaultOutlet->tax_percentage,
                'service_charge_percentage' => (float) $defaultOutlet->service_charge_percentage,
                'receipt_header' => $defaultOutlet->receipt_header,
                'receipt_footer' => $defaultOutlet->receipt_footer,
                'receipt_show_logo' => (bool) $defaultOutlet->receipt_show_logo,
                'opening_time' => $defaultOutlet->opening_time,
                'closing_time' => $defaultOutlet->closing_time,
            ];
        }

        return $data;
    }

    /**
     * Get user permissions
     */
    private function getUserPermissions(User $user): array
    {
        if ($user->isSuperAdmin()) {
            return ['*'];
        }

        return $user->roles()
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->pluck('slug')
            ->unique()
            ->values()
            ->toArray();
    }
}
