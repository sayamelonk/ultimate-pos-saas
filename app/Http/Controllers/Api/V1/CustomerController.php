<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class CustomerController extends Controller
{
    #[OA\Get(
        path: '/customers',
        summary: 'List customers with pagination',
        security: [['sanctum' => []]],
        tags: ['Customers'],
        parameters: [
            new OA\Parameter(name: 'level', in: 'query', description: 'Filter by membership level', schema: new OA\Schema(type: 'string', enum: ['regular', 'silver', 'gold', 'platinum'])),
            new OA\Parameter(name: 'q', in: 'query', description: 'Search by name, phone, code, email', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Items per page (max 50)', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of customers'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    /**
     * List customers
     *
     * GET /api/v1/customers
     *
     * Schema customers:
     * - id (uuid)
     * - tenant_id (uuid)
     * - code (string 50, nullable)
     * - name (string 200)
     * - email (string, nullable)
     * - phone (string 20, nullable)
     * - address (text, nullable)
     * - birth_date (date, nullable)
     * - gender (string: male, female, nullable)
     * - membership_level (string: regular, silver, gold, platinum)
     * - total_points (decimal)
     * - total_spent (decimal)
     * - total_visits (int)
     * - joined_at (date)
     * - membership_expires_at (date, nullable)
     * - notes (text, nullable)
     * - is_active (boolean)
     * - created_at, updated_at, deleted_at
     */
    public function index(Request $request): JsonResponse
    {
        $query = Customer::query()
            ->where('tenant_id', $this->tenantId())
            ->where('is_active', true);

        // Filter by membership level
        if ($request->has('level')) {
            $query->where('membership_level', $request->level);
        }

        // Search
        if ($request->has('q')) {
            $keyword = $request->q;
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%")
                    ->orWhere('code', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        $perPage = min($request->get('per_page', 20), 50);

        $customers = $query->orderBy('name')
            ->paginate($perPage);

        $data = $customers->map(fn ($customer) => $this->formatCustomer($customer));

        return $this->successWithPagination($data, $this->paginationMeta($customers));
    }

    #[OA\Post(
        path: '/customers',
        summary: 'Create new customer',
        security: [['sanctum' => []]],
        tags: ['Customers'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 200),
                    new OA\Property(property: 'phone', type: 'string', maxLength: 20),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'address', type: 'string', maxLength: 500),
                    new OA\Property(property: 'birth_date', type: 'string', format: 'date'),
                    new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female']),
                    new OA\Property(property: 'notes', type: 'string', maxLength: 500),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Customer created successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error or duplicate phone'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
            'notes' => 'nullable|string|max:500',
        ]);

        // Check duplicate phone
        if (! empty($validated['phone'])) {
            $exists = Customer::where('tenant_id', $this->tenantId())
                ->where('phone', $validated['phone'])
                ->exists();

            if ($exists) {
                return $this->error('Customer with this phone number already exists.', 422);
            }
        }

        // Generate customer code
        $code = $this->generateCustomerCode();

        $customer = Customer::create([
            'tenant_id' => $this->tenantId(),
            'code' => $code,
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'birth_date' => $validated['birth_date'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'membership_level' => Customer::LEVEL_REGULAR,
            'total_points' => 0,
            'total_spent' => 0,
            'total_visits' => 0,
            'joined_at' => now(),
            'is_active' => true,
        ]);

        return $this->created($this->formatCustomer($customer), 'Customer created successfully');
    }

    #[OA\Get(
        path: '/customers/search',
        summary: 'Search customers (for quick lookup in POS)',
        security: [['sanctum' => []]],
        tags: ['Customers'],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', required: true, description: 'Search keyword (min 2 chars)', schema: new OA\Schema(type: 'string', minLength: 2)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of matching customers (max 20)'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
        ]);

        $keyword = $request->q;

        $customers = Customer::query()
            ->where('tenant_id', $this->tenantId())
            ->where('is_active', true)
            ->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%")
                    ->orWhere('code', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            })
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn ($customer) => $this->formatCustomerCompact($customer));

        return $this->success($customers);
    }

    #[OA\Get(
        path: '/customers/{customer}',
        summary: 'Get customer detail',
        security: [['sanctum' => []]],
        tags: ['Customers'],
        parameters: [
            new OA\Parameter(name: 'customer', in: 'path', required: true, description: 'Customer UUID', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Customer detail'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Customer not found'),
        ]
    )]
    public function show(Customer $customer): JsonResponse
    {
        if ($customer->tenant_id !== $this->tenantId()) {
            return $this->notFound('Customer not found');
        }

        return $this->success($this->formatCustomer($customer));
    }

    #[OA\Put(
        path: '/customers/{customer}',
        summary: 'Update customer',
        security: [['sanctum' => []]],
        tags: ['Customers'],
        parameters: [
            new OA\Parameter(name: 'customer', in: 'path', required: true, description: 'Customer UUID', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 200),
                    new OA\Property(property: 'phone', type: 'string', maxLength: 20),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'address', type: 'string', maxLength: 500),
                    new OA\Property(property: 'birth_date', type: 'string', format: 'date'),
                    new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female']),
                    new OA\Property(property: 'notes', type: 'string', maxLength: 500),
                    new OA\Property(property: 'is_active', type: 'boolean'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Customer updated successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Customer not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(Request $request, Customer $customer): JsonResponse
    {
        if ($customer->tenant_id !== $this->tenantId()) {
            return $this->notFound('Customer not found');
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:200',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
            'notes' => 'nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
        ]);

        // Check duplicate phone if changing
        if (isset($validated['phone']) && $validated['phone'] !== $customer->phone) {
            $exists = Customer::where('tenant_id', $this->tenantId())
                ->where('phone', $validated['phone'])
                ->where('id', '!=', $customer->id)
                ->exists();

            if ($exists) {
                return $this->error('Customer with this phone number already exists.', 422);
            }
        }

        $customer->update($validated);

        return $this->success($this->formatCustomer($customer), 'Customer updated successfully');
    }

    #[OA\Get(
        path: '/customers/{customer}/transactions',
        summary: 'Get customer transaction history',
        security: [['sanctum' => []]],
        tags: ['Customers'],
        parameters: [
            new OA\Parameter(name: 'customer', in: 'path', required: true, description: 'Customer UUID', schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Items per page (max 50)', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Transaction history with pagination'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Customer not found'),
        ]
    )]
    public function transactions(Request $request, Customer $customer): JsonResponse
    {
        if ($customer->tenant_id !== $this->tenantId()) {
            return $this->notFound('Customer not found');
        }

        $perPage = min($request->get('per_page', 20), 50);

        $transactions = $customer->transactions()
            ->with(['user:id,name', 'outlet:id,name'])
            ->orderByDesc('created_at')
            ->paginate($perPage);

        $data = $transactions->map(fn ($txn) => [
            'id' => $txn->id,
            'transaction_number' => $txn->transaction_number,
            'type' => $txn->type,
            'order_type' => $txn->order_type,
            'outlet_name' => $txn->outlet?->name,
            'user_name' => $txn->user?->name,
            'grand_total' => (float) $txn->grand_total,
            'points_earned' => (float) $txn->points_earned,
            'points_redeemed' => (float) $txn->points_redeemed,
            'status' => $txn->status,
            'created_at' => $txn->created_at?->toIso8601String(),
        ]);

        return $this->successWithPagination($data, $this->paginationMeta($transactions));
    }

    /**
     * Generate unique customer code
     */
    private function generateCustomerCode(): string
    {
        $prefix = 'CUS';
        $random = strtoupper(Str::random(6));

        // Ensure unique
        while (Customer::where('tenant_id', $this->tenantId())->where('code', $prefix.$random)->exists()) {
            $random = strtoupper(Str::random(6));
        }

        return $prefix.$random;
    }

    /**
     * Format customer for response
     */
    private function formatCustomer(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'code' => $customer->code,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'email' => $customer->email,
            'address' => $customer->address,
            'birth_date' => $customer->birth_date?->toDateString(),
            'gender' => $customer->gender,
            'membership_level' => $customer->membership_level,
            'total_points' => (float) $customer->total_points,
            'points_value' => (float) $customer->getPointsValue(),
            'total_spent' => (float) $customer->total_spent,
            'total_visits' => (int) $customer->total_visits,
            'joined_at' => $customer->joined_at?->toDateString(),
            'membership_expires_at' => $customer->membership_expires_at?->toDateString(),
            'is_member' => $customer->isMember(),
            'is_membership_active' => $customer->isMembershipActive(),
            'notes' => $customer->notes,
            'is_active' => (bool) $customer->is_active,
            'created_at' => $customer->created_at?->toIso8601String(),
            'updated_at' => $customer->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Format customer compact (for search results)
     */
    private function formatCustomerCompact(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'code' => $customer->code,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'email' => $customer->email,
            'membership_level' => $customer->membership_level,
            'total_points' => (float) $customer->total_points,
            'total_spent' => (float) $customer->total_spent,
        ];
    }
}
