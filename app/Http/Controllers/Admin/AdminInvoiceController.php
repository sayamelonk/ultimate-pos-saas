<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionInvoice;
use App\Models\Tenant;
use Illuminate\Http\Request;

class AdminInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = SubscriptionInvoice::with(['tenant', 'plan'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('tenant')) {
            $query->where('tenant_id', $request->tenant);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('tenant', function ($tq) use ($search) {
                        $tq->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $invoices = $query->paginate(20)->withQueryString();
        $tenants = Tenant::orderBy('name')->get();

        // Stats
        $stats = [
            'total_paid' => SubscriptionInvoice::where('status', 'paid')->sum('amount'),
            'total_pending' => SubscriptionInvoice::where('status', 'pending')->sum('amount'),
            'count_paid' => SubscriptionInvoice::where('status', 'paid')->count(),
            'count_pending' => SubscriptionInvoice::where('status', 'pending')->count(),
        ];

        return view('admin.invoices.index', compact('invoices', 'tenants', 'stats'));
    }

    public function show(SubscriptionInvoice $invoice)
    {
        $invoice->load(['tenant', 'plan', 'subscription']);

        return view('admin.invoices.show', compact('invoice'));
    }

    public function updateStatus(Request $request, SubscriptionInvoice $invoice)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,paid,failed,cancelled,expired',
        ]);

        $invoice->update($validated);

        if ($validated['status'] === 'paid' && ! $invoice->paid_at) {
            $invoice->update(['paid_at' => now()]);
        }

        return back()->with('success', __('admin.invoice_status_updated'));
    }
}
