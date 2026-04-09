<?php

namespace Database\Seeders;

use App\Models\Floor;
use App\Models\Outlet;
use Illuminate\Database\Seeder;

class FloorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $outlets = Outlet::all();

        foreach ($outlets as $outlet) {
            $floors = [
                [
                    'name' => 'Lantai 1',
                    'description' => 'Lantai utama dengan area indoor',
                    'sort_order' => 1,
                ],
                [
                    'name' => 'Lantai 2',
                    'description' => 'Lantai atas dengan pemandangan',
                    'sort_order' => 2,
                ],
                [
                    'name' => 'Outdoor',
                    'description' => 'Area terbuka di luar ruangan',
                    'sort_order' => 3,
                ],
            ];

            foreach ($floors as $floorData) {
                Floor::create([
                    'tenant_id' => $outlet->tenant_id,
                    'outlet_id' => $outlet->id,
                    'name' => $floorData['name'],
                    'description' => $floorData['description'],
                    'sort_order' => $floorData['sort_order'],
                    'is_active' => true,
                ]);
            }
        }

        $this->command->info('Floors seeded successfully!');
    }
}
