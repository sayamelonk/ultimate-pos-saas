<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $this->seedUnitsForTenant($tenant);
        }
    }

    private function seedUnitsForTenant(Tenant $tenant): void
    {
        // Weight Units
        $kg = Unit::create([
            'tenant_id' => $tenant->id,
            'name' => 'Kilogram',
            'abbreviation' => 'kg',
            'base_unit_id' => null,
            'conversion_factor' => 1,
            'is_active' => true,
        ]);

        Unit::create([
            'tenant_id' => $tenant->id,
            'name' => 'Gram',
            'abbreviation' => 'g',
            'base_unit_id' => $kg->id,
            'conversion_factor' => 0.001,
            'is_active' => true,
        ]);

        Unit::create([
            'tenant_id' => $tenant->id,
            'name' => 'Miligram',
            'abbreviation' => 'mg',
            'base_unit_id' => $kg->id,
            'conversion_factor' => 0.000001,
            'is_active' => true,
        ]);

        Unit::create([
            'tenant_id' => $tenant->id,
            'name' => 'Ons',
            'abbreviation' => 'ons',
            'base_unit_id' => $kg->id,
            'conversion_factor' => 0.1,
            'is_active' => true,
        ]);

        // Volume Units
        $liter = Unit::create([
            'tenant_id' => $tenant->id,
            'name' => 'Liter',
            'abbreviation' => 'L',
            'base_unit_id' => null,
            'conversion_factor' => 1,
            'is_active' => true,
        ]);

        Unit::create([
            'tenant_id' => $tenant->id,
            'name' => 'Mililiter',
            'abbreviation' => 'ml',
            'base_unit_id' => $liter->id,
            'conversion_factor' => 0.001,
            'is_active' => true,
        ]);

        Unit::create([
            'tenant_id' => $tenant->id,
            'name' => 'Centiliter',
            'abbreviation' => 'cl',
            'base_unit_id' => $liter->id,
            'conversion_factor' => 0.01,
            'is_active' => true,
        ]);

        // Count Units
        Unit::create([
            'tenant_id' => $tenant->id,
            'name' => 'Piece',
            'abbreviation' => 'pcs',
            'base_unit_id' => null,
            'conversion_factor' => 1,
            'is_active' => true,
        ]);

        Unit::create([
            'tenant_id' => $tenant->id,
            'name' => 'Dozen',
            'abbreviation' => 'doz',
            'base_unit_id' => null,
            'conversion_factor' => 1,
            'is_active' => true,
        ]);

        Unit::create([
            'tenant_id' => $tenant->id,
            'name' => 'Box',
            'abbreviation' => 'box',
            'base_unit_id' => null,
            'conversion_factor' => 1,
            'is_active' => true,
        ]);

        Unit::create([
            'tenant_id' => $tenant->id,
            'name' => 'Pack',
            'abbreviation' => 'pack',
            'base_unit_id' => null,
            'conversion_factor' => 1,
            'is_active' => true,
        ]);

        Unit::create([
            'tenant_id' => $tenant->id,
            'name' => 'Carton',
            'abbreviation' => 'ctn',
            'base_unit_id' => null,
            'conversion_factor' => 1,
            'is_active' => true,
        ]);

        // Food Service Units
        Unit::create([
            'tenant_id' => $tenant->id,
            'name' => 'Portion',
            'abbreviation' => 'por',
            'base_unit_id' => null,
            'conversion_factor' => 1,
            'is_active' => true,
        ]);

        Unit::create([
            'tenant_id' => $tenant->id,
            'name' => 'Slice',
            'abbreviation' => 'slc',
            'base_unit_id' => null,
            'conversion_factor' => 1,
            'is_active' => true,
        ]);

        Unit::create([
            'tenant_id' => $tenant->id,
            'name' => 'Cup',
            'abbreviation' => 'cup',
            'base_unit_id' => null,
            'conversion_factor' => 1,
            'is_active' => true,
        ]);

        Unit::create([
            'tenant_id' => $tenant->id,
            'name' => 'Tablespoon',
            'abbreviation' => 'tbsp',
            'base_unit_id' => null,
            'conversion_factor' => 1,
            'is_active' => true,
        ]);

        Unit::create([
            'tenant_id' => $tenant->id,
            'name' => 'Teaspoon',
            'abbreviation' => 'tsp',
            'base_unit_id' => null,
            'conversion_factor' => 1,
            'is_active' => true,
        ]);
    }
}
