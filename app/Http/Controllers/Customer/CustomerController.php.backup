<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(private CustomerService $customerService) {}

    public function index(Request $request): View
    {
        $query = Customer::where('tenant_id', auth()->user()->tenant_id);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('membership_level')) {
            $query->where('membership_level', $request->membership_level);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $customers = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('customers.index', [
            'customers' => $customers,
            'membershipLevels' => Customer::getMembershipLevels(),
        ]);
    }

    public function create(): View
    {
        $code = $this->customerService->generateCustomerCode(auth()->user()->tenant_id);

        return view('customers.create', [
            'membershipLevels' => Customer::getMembershipLevels(),
            'suggestedCode' => $code,
        ]);
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        Customer::create([
            'tenant_id' => auth()->user()->tenant_id,
            'code' => $request->code,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'birth_date' => $request->birth_date,
            'gender' => $request->gender,
            'membership_level' => $request->membership_level ?? Customer::LEVEL_REGULAR,
            'membership_expires_at' => $request->membership_expires_at,
            'notes' => $request->notes,
            'joined_at' => now(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('customers.index')
            ->with('success', 'Customer created successfully.');
    }

    public function show(Customer $customer): View
    {
        $this->authorizeCustomer($customer);

        $customer->load('transactions.items', 'transactions.payments.paymentMethod');
        $pointHistory = $this->customerService->getPointHistory($customer);

        return view('customers.show', [
            'customer' => $customer,
            'pointHistory' => $pointHistory,
            'membershipLevels' => Customer::getMembershipLevels(),
        ]);
    }

    public function edit(Customer $customer): View
    {
        $this->authorizeCustomer($customer);

        return view('customers.edit', [
            'customer' => $customer,
            'membershipLevels' => Customer::getMembershipLevels(),
        ]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $this->authorizeCustomer($customer);

        $customer->update([
            'code' => $request->code,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'birth_date' => $request->birth_date,
            'gender' => $request->gender,
            'membership_level' => $request->membership_level ?? $customer->membership_level,
            'membership_expires_at' => $request->membership_expires_at,
            'notes' => $request->notes,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('customers.index')
            ->with('success', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->authorizeCustomer($customer);

        if ($customer->transactions()->exists()) {
            return redirect()->route('customers.index')
                ->with('error', 'Cannot delete customer with transaction history.');
        }

        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', 'Customer deleted successfully.');
    }

    public function points(Customer $customer): View
    {
        $this->authorizeCustomer($customer);

        $pointHistory = $this->customerService->getPointHistory($customer, 50);

        return view('customers.points', [
            'customer' => $customer,
            'pointHistory' => $pointHistory,
        ]);
    }

    public function adjustPoints(Request $request, Customer $customer): RedirectResponse
    {
        $this->authorizeCustomer($customer);

        $request->validate([
            'points' => ['required', 'numeric'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $this->customerService->adjustPoints(
            $customer,
            $request->points,
            auth()->id(),
            $request->description
        );

        return redirect()->route('customers.show', $customer)
            ->with('success', 'Points adjusted successfully.');
    }

    private function authorizeCustomer(Customer $customer): void
    {
        if ($customer->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }
    }
}
