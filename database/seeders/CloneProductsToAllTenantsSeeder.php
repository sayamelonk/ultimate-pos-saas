<?php

namespace Database\Seeders;

use App\Models\ModifierGroup;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tenant;
use App\Models\VariantGroup;
use App\Services\Menu\ProductService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CloneProductsToAllTenantsSeeder extends Seeder
{
    public function run(): void
    {
        // Get the template products from the first tenant (DEMO001)
        $templateProducts = Product::with([
            'category',
            'variantGroups',
            'modifierGroups',
            'variants',
        ])->whereHas('category', function ($q) {
            // Only clone standard product categories (not inventory-based ones)
            $q->whereIn('code', [
                'HOT-COF', 'ICE-COF', 'ESP', 'TEA', 'CHOCO',
                'SMOOTH', 'PASTRY', 'CAKE', 'SAND',
            ]);
        })->get();

        if ($templateProducts->isEmpty()) {
            $this->command->warn('No template products found. Run ProductSeeder first.');

            return;
        }

        // Get all tenants
        $tenants = Tenant::all();

        // Get categories for each tenant
        $tenantCategories = [];
        foreach ($tenants as $tenant) {
            $tenantCategories[$tenant->id] = ProductCategory::where('tenant_id', $tenant->id)
                ->get()
                ->keyBy('code');
        }

        // Get variant groups for each tenant
        $tenantVariantGroups = [];
        foreach ($tenants as $tenant) {
            $tenantVariantGroups[$tenant->id] = VariantGroup::where('tenant_id', $tenant->id)
                ->get()
                ->keyBy('name');
        }

        $this->command->info("Cloning {$templateProducts->count()} products to {$tenants->count()} tenants...");

        $totalCloned = 0;

        foreach ($tenants as $tenant) {
            // Skip if tenant already has products
            $existingCount = Product::where('tenant_id', $tenant->id)->count();
            if ($existingCount > 0) {
                $this->command->info("Tenant {$tenant->name} already has {$existingCount} products. Skipping...");

                continue;
            }

            foreach ($templateProducts as $templateProduct) {
                // Map category to tenant's category
                $templateCategoryCode = $templateProduct->category?->code;
                $newCategoryId = $tenantCategories[$tenant->id][$templateCategoryCode]?->id
                    ?? ProductCategory::where('tenant_id', $tenant->id)->first()?->id;

                // Map variant groups
                $newVariantGroups = [];
                foreach ($templateProduct->variantGroups as $vg) {
                    $newGroup = $tenantVariantGroups[$tenant->id][$vg->name] ?? null;
                    if ($newGroup) {
                        $newVariantGroups[$vg->pivot->sort_order ?? 0] = $newGroup->id;
                    }
                }

                // Map modifier groups
                $newModifierGroups = [];
                $tenantModifierGroups = ModifierGroup::where('tenant_id', $tenant->id)
                    ->get()
                    ->keyBy('name');
                foreach ($templateProduct->modifierGroups as $mg) {
                    // Try to find matching modifier group by name for this tenant
                    $newModifierGroup = $tenantModifierGroups[$mg->name] ?? null;
                    if ($newModifierGroup) {
                        $newModifierGroups[$mg->pivot->sort_order ?? 0] = $newModifierGroup->id;
                    }
                }

                // Create new product
                $newProduct = Product::create([
                    'tenant_id' => $tenant->id,
                    'category_id' => $newCategoryId,
                    'sku' => $templateProduct->sku.'-'.strtoupper(substr($tenant->code, 0, 3)),
                    'barcode' => $templateProduct->barcode,
                    'name' => $templateProduct->name,
                    'slug' => Str::slug($templateProduct->name).'-'.$tenant->id,
                    'description' => $templateProduct->description,
                    'base_price' => $templateProduct->base_price,
                    'cost_price' => $templateProduct->cost_price,
                    'product_type' => $templateProduct->product_type,
                    'track_stock' => $templateProduct->track_stock,
                    'is_active' => $templateProduct->is_active,
                    'is_featured' => $templateProduct->is_featured,
                    'show_in_pos' => $templateProduct->show_in_pos,
                    'show_in_menu' => $templateProduct->show_in_menu,
                    'allow_notes' => $templateProduct->allow_notes,
                    'sort_order' => $templateProduct->sort_order,
                ]);

                // Attach variant groups
                ksort($newVariantGroups);
                foreach ($newVariantGroups as $index => $groupId) {
                    $newProduct->variantGroups()->attach($groupId, [
                        'id' => Str::uuid(),
                        'is_required' => true,
                        'sort_order' => $index,
                    ]);
                }

                // Attach modifier groups
                ksort($newModifierGroups);
                foreach ($newModifierGroups as $index => $groupId) {
                    $newProduct->modifierGroups()->attach($groupId, [
                        'id' => Str::uuid(),
                        'is_required' => false,
                        'min_selections' => 0,
                        'max_selections' => null,
                        'sort_order' => $index,
                    ]);
                }

                // Generate variants if it's a variant product
                if ($newProduct->product_type === Product::TYPE_VARIANT && $newProduct->variantGroups->isNotEmpty()) {
                    $productService = app(ProductService::class);
                    $productService->generateVariants($newProduct);
                }

                $totalCloned++;
            }

            $this->command->info("Cloned products to tenant: {$tenant->name}");
        }

        $this->command->info("Total products cloned: {$totalCloned}");

        // Now run ProductOutletSeeder to assign products to all outlets
        $this->command->info('Running ProductOutletSeeder...');
        $this->call(ProductOutletSeeder::class);
    }
}
