<?php

namespace Database\Seeders;

use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductOutlet;
use Illuminate\Database\Seeder;

class ProductOutletSeeder extends Seeder
{
    public function run(): void
    {
        $count = 0;

        // Get all products
        $products = Product::where('is_active', true)
            ->where('show_in_pos', true)
            ->get();

        if ($products->isEmpty()) {
            $this->command->warn('No products found. Run ProductSeeder first.');

            return;
        }

        // Get all outlets
        $outlets = Outlet::where('is_active', true)->get();

        if ($outlets->isEmpty()) {
            $this->command->warn('No outlets found.');

            return;
        }

        $this->command->info("Assigning {$products->count()} products to {$outlets->count()} outlets...");

        foreach ($outlets as $outlet) {
            foreach ($products as $product) {
                // Skip if product already assigned to this outlet
                $exists = ProductOutlet::where('product_id', $product->id)
                    ->where('outlet_id', $outlet->id)
                    ->exists();

                if (! $exists) {
                    ProductOutlet::create([
                        'product_id' => $product->id,
                        'outlet_id' => $outlet->id,
                        'is_available' => true,
                        'is_featured' => $product->is_featured,
                        'sort_order' => $product->sort_order ?? 0,
                    ]);
                    $count++;
                }
            }
        }

        $this->command->info("Created {$count} product-outlet assignments.");
    }
}
