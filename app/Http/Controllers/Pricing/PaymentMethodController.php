<?php

namespace App\Http\Controllers\Pricing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pricing\StorePaymentMethodRequest;
use App\Http\Requests\Pricing\UpdatePaymentMethodRequest;
use App\Models\PaymentMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentMethodController extends Controller
{
    public function index(Request $request): View
    {
        $query = PaymentMethod::where('tenant_id', auth()->user()->tenant_id);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $paymentMethods = $query->orderBy('sort_order')->orderBy('name')->paginate(15)->withQueryString();

        return view('pricing.payment-methods.index', [
            'paymentMethods' => $paymentMethods,
            'types' => PaymentMethod::getTypes(),
        ]);
    }

    public function create(): View
    {
        return view('pricing.payment-methods.create', [
            'types' => PaymentMethod::getTypes(),
        ]);
    }

    public function store(StorePaymentMethodRequest $request): RedirectResponse
    {
        PaymentMethod::create([
            'tenant_id' => auth()->user()->tenant_id,
            'code' => strtoupper($request->code),
            'name' => $request->name,
            'type' => $request->type,
            'provider' => $request->provider,
            'icon' => $request->icon,
            'charge_percentage' => $request->charge_percentage ?? 0,
            'charge_fixed' => $request->charge_fixed ?? 0,
            'requires_reference' => $request->boolean('requires_reference'),
            'opens_cash_drawer' => $request->boolean('opens_cash_drawer'),
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('pricing.payment-methods.index')
            ->with('success', 'Payment method created successfully.');
    }

    public function edit(PaymentMethod $paymentMethod): View
    {
        $this->authorizePaymentMethod($paymentMethod);

        return view('pricing.payment-methods.edit', [
            'paymentMethod' => $paymentMethod,
            'types' => PaymentMethod::getTypes(),
        ]);
    }

    public function update(UpdatePaymentMethodRequest $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        $this->authorizePaymentMethod($paymentMethod);

        $paymentMethod->update([
            'code' => strtoupper($request->code),
            'name' => $request->name,
            'type' => $request->type,
            'provider' => $request->provider,
            'icon' => $request->icon,
            'charge_percentage' => $request->charge_percentage ?? 0,
            'charge_fixed' => $request->charge_fixed ?? 0,
            'requires_reference' => $request->boolean('requires_reference'),
            'opens_cash_drawer' => $request->boolean('opens_cash_drawer'),
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('pricing.payment-methods.index')
            ->with('success', 'Payment method updated successfully.');
    }

    public function destroy(PaymentMethod $paymentMethod): RedirectResponse
    {
        $this->authorizePaymentMethod($paymentMethod);

        if ($paymentMethod->transactionPayments()->exists()) {
            return redirect()->route('pricing.payment-methods.index')
                ->with('error', 'Cannot delete payment method with transaction history.');
        }

        $paymentMethod->delete();

        return redirect()->route('pricing.payment-methods.index')
            ->with('success', 'Payment method deleted successfully.');
    }

    private function authorizePaymentMethod(PaymentMethod $paymentMethod): void
    {
        if ($paymentMethod->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }
    }
}
