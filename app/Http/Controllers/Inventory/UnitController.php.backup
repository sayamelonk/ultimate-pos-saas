<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UnitController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $query = Unit::where('tenant_id', $user->tenant_id)
            ->with('baseUnit');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('abbreviation', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            if ($request->type === 'base') {
                $query->whereNull('base_unit_id');
            } else {
                $query->whereNotNull('base_unit_id');
            }
        }

        $units = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('inventory.units.index', compact('units'));
    }

    public function create(): View
    {
        $user = auth()->user();
        $baseUnits = Unit::where('tenant_id', $user->tenant_id)
            ->whereNull('base_unit_id')
            ->orderBy('name')
            ->get();

        return view('inventory.units.create', compact('baseUnits'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'abbreviation' => ['required', 'string', 'max:10'],
            'base_unit_id' => ['nullable', 'exists:units,id'],
            'conversion_factor' => ['required_with:base_unit_id', 'numeric', 'min:0.000001'],
            'is_active' => ['boolean'],
        ]);

        $validated['tenant_id'] = $user->tenant_id;
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['conversion_factor'] = $validated['conversion_factor'] ?? 1;

        Unit::create($validated);

        return redirect()->route('inventory.units.index')
            ->with('success', 'Unit created successfully.');
    }

    public function show(Unit $unit): View
    {
        $this->authorizeUnit($unit);
        $unit->load(['baseUnit', 'derivedUnits']);

        return view('inventory.units.show', compact('unit'));
    }

    public function edit(Unit $unit): View
    {
        $this->authorizeUnit($unit);
        $user = auth()->user();

        $baseUnits = Unit::where('tenant_id', $user->tenant_id)
            ->whereNull('base_unit_id')
            ->where('id', '!=', $unit->id)
            ->orderBy('name')
            ->get();

        return view('inventory.units.edit', compact('unit', 'baseUnits'));
    }

    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $this->authorizeUnit($unit);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'abbreviation' => ['required', 'string', 'max:10'],
            'base_unit_id' => ['nullable', 'exists:units,id'],
            'conversion_factor' => ['required_with:base_unit_id', 'numeric', 'min:0.000001'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['conversion_factor'] = $validated['conversion_factor'] ?? 1;

        $unit->update($validated);

        return redirect()->route('inventory.units.index')
            ->with('success', 'Unit updated successfully.');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        $this->authorizeUnit($unit);

        // Check if unit is used in inventory items
        if ($unit->inventoryItems()->exists()) {
            return back()->with('error', 'Cannot delete unit used by inventory items.');
        }

        // Check if unit has derived units
        if ($unit->derivedUnits()->exists()) {
            return back()->with('error', 'Cannot delete base unit with derived units.');
        }

        $unit->delete();

        return redirect()->route('inventory.units.index')
            ->with('success', 'Unit deleted successfully.');
    }

    private function authorizeUnit(Unit $unit): void
    {
        $user = auth()->user();

        if ($unit->tenant_id !== $user->tenant_id) {
            abort(403, 'Access denied.');
        }
    }
}
