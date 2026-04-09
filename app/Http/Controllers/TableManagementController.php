<?php

namespace App\Http\Controllers;

use App\Models\Floor;
use App\Models\Table;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TableManagementController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $outletId = $user->defaultOutlet()?->id;

        $floors = Floor::where('outlet_id', $outletId)
            ->where('is_active', true)
            ->with('tables')
            ->orderBy('sort_order')
            ->get();

        $tables = Table::where('outlet_id', $outletId)
            ->with('floor')
            ->orderBy('floor_id')
            ->orderBy('number')
            ->get();

        return view('tables.index', [
            'floors' => $floors,
            'tables' => $tables,
        ]);
    }

    public function storeFloor(Request $request): JsonResponse
    {
        $user = auth()->user();
        $outletId = $user->defaultOutlet()?->id;

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        $maxSort = Floor::where('outlet_id', $outletId)->max('sort_order') ?? 0;

        $floor = Floor::create([
            'tenant_id' => $user->tenant_id,
            'outlet_id' => $outletId,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'sort_order' => $maxSort + 1,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Floor berhasil ditambahkan.',
            'floor' => $floor,
        ]);
    }

    public function storeTable(Request $request): JsonResponse
    {
        $user = auth()->user();
        $outletId = $user->defaultOutlet()?->id;

        $validated = $request->validate([
            'floor_id' => [
                'required',
                'uuid',
                Rule::exists('floors', 'id')->where(fn ($q) => $q->where('outlet_id', $outletId)),
            ],
            'number' => 'required|string|max:20',
            'name' => 'nullable|string|max:100',
            'capacity' => 'nullable|integer|min:1|max:100',
        ]);

        $exists = Table::where('tenant_id', $user->tenant_id)
            ->where('floor_id', $validated['floor_id'])
            ->where('number', $validated['number'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor meja sudah ada di floor ini.',
            ], 422);
        }

        $table = Table::create([
            'tenant_id' => $user->tenant_id,
            'outlet_id' => $outletId,
            'floor_id' => $validated['floor_id'],
            'number' => $validated['number'],
            'name' => $validated['name'] ?? null,
            'capacity' => $validated['capacity'] ?? 4,
            'shape' => Table::SHAPE_RECTANGLE,
            'status' => Table::STATUS_AVAILABLE,
            'is_active' => true,
        ]);

        $table->load('floor');

        return response()->json([
            'success' => true,
            'message' => 'Meja berhasil ditambahkan.',
            'table' => $table,
        ]);
    }

    public function updateTable(Request $request, Table $table): JsonResponse
    {
        if ($table->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'number' => 'sometimes|string|max:20',
            'name' => 'nullable|string|max:100',
            'capacity' => 'nullable|integer|min:1|max:100',
            'is_active' => 'sometimes|boolean',
        ]);

        if (isset($validated['number']) && $validated['number'] !== $table->number) {
            $exists = Table::where('tenant_id', auth()->user()->tenant_id)
                ->where('floor_id', $table->floor_id)
                ->where('number', $validated['number'])
                ->where('id', '!=', $table->id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nomor meja sudah ada di floor ini.',
                ], 422);
            }
        }

        $table->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Meja berhasil diupdate.',
            'table' => $table->fresh()->load('floor'),
        ]);
    }

    public function destroyTable(Table $table): JsonResponse
    {
        if ($table->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $table->delete();

        return response()->json([
            'success' => true,
            'message' => 'Meja berhasil dihapus.',
        ]);
    }

    public function destroyFloor(Floor $floor): JsonResponse
    {
        if ($floor->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        if ($floor->tables()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Floor masih memiliki meja. Hapus semua meja terlebih dahulu.',
            ], 422);
        }

        $floor->delete();

        return response()->json([
            'success' => true,
            'message' => 'Floor berhasil dihapus.',
        ]);
    }
}
