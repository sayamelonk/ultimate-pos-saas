<?php

namespace Database\Seeders;

use App\Models\Floor;
use App\Models\Table;
use Illuminate\Database\Seeder;

class TableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $floors = Floor::all();

        foreach ($floors as $floor) {
            $tablesConfig = $this->getTablesForFloor($floor->name);

            foreach ($tablesConfig as $index => $tableData) {
                Table::create([
                    'tenant_id' => $floor->tenant_id,
                    'outlet_id' => $floor->outlet_id,
                    'floor_id' => $floor->id,
                    'number' => $tableData['number'],
                    'name' => $tableData['name'],
                    'capacity' => $tableData['capacity'],
                    'position_x' => $tableData['position_x'],
                    'position_y' => $tableData['position_y'],
                    'width' => $tableData['width'] ?? 100,
                    'height' => $tableData['height'] ?? 100,
                    'shape' => $tableData['shape'] ?? 'square',
                    'status' => $tableData['status'] ?? 'available',
                    'is_active' => true,
                ]);
            }
        }

        $this->command->info('Tables seeded successfully!');
    }

    private function getTablesForFloor(string $floorName): array
    {
        // Different table configurations based on floor
        return match ($floorName) {
            'Lantai 1' => [
                // Row 1
                ['number' => '1', 'name' => 'Meja 1', 'capacity' => 4, 'position_x' => 50, 'position_y' => 50, 'shape' => 'square', 'status' => 'available'],
                ['number' => '2', 'name' => 'Meja 2', 'capacity' => 4, 'position_x' => 170, 'position_y' => 50, 'shape' => 'square', 'status' => 'available'],
                ['number' => '3', 'name' => 'Meja 3', 'capacity' => 2, 'position_x' => 290, 'position_y' => 50, 'shape' => 'square', 'status' => 'occupied'],
                ['number' => '4', 'name' => 'Meja 4', 'capacity' => 6, 'position_x' => 410, 'position_y' => 50, 'shape' => 'rectangle', 'width' => 120, 'status' => 'available'],
                // Row 2
                ['number' => '5', 'name' => 'Meja 5', 'capacity' => 4, 'position_x' => 50, 'position_y' => 170, 'shape' => 'square', 'status' => 'occupied'],
                ['number' => '6', 'name' => 'Meja 6', 'capacity' => 2, 'position_x' => 170, 'position_y' => 170, 'shape' => 'circle', 'status' => 'available'],
                ['number' => '7', 'name' => 'Meja 7', 'capacity' => 8, 'position_x' => 290, 'position_y' => 170, 'shape' => 'rectangle', 'width' => 150, 'status' => 'available'],
                ['number' => '8', 'name' => 'Meja 8', 'capacity' => 4, 'position_x' => 460, 'position_y' => 170, 'shape' => 'square', 'status' => 'reserved'],
            ],
            'Lantai 2' => [
                // VIP area - larger tables
                ['number' => '9', 'name' => 'VIP 1', 'capacity' => 8, 'position_x' => 50, 'position_y' => 50, 'shape' => 'rectangle', 'width' => 150, 'height' => 120, 'status' => 'available'],
                ['number' => '10', 'name' => 'VIP 2', 'capacity' => 10, 'position_x' => 220, 'position_y' => 50, 'shape' => 'rectangle', 'width' => 180, 'height' => 120, 'status' => 'available'],
                ['number' => '11', 'name' => 'VIP 3', 'capacity' => 6, 'position_x' => 420, 'position_y' => 50, 'shape' => 'circle', 'width' => 120, 'height' => 120, 'status' => 'occupied'],
                // Regular tables
                ['number' => '12', 'name' => 'Meja 12', 'capacity' => 4, 'position_x' => 50, 'position_y' => 200, 'shape' => 'square', 'status' => 'available'],
                ['number' => '13', 'name' => 'Meja 13', 'capacity' => 4, 'position_x' => 170, 'position_y' => 200, 'shape' => 'square', 'status' => 'available'],
                ['number' => '14', 'name' => 'Meja 14', 'capacity' => 2, 'position_x' => 290, 'position_y' => 200, 'shape' => 'circle', 'status' => 'available'],
            ],
            'Outdoor' => [
                // Outdoor garden tables
                ['number' => '15', 'name' => 'Garden 1', 'capacity' => 4, 'position_x' => 50, 'position_y' => 50, 'shape' => 'circle', 'status' => 'available'],
                ['number' => '16', 'name' => 'Garden 2', 'capacity' => 4, 'position_x' => 180, 'position_y' => 50, 'shape' => 'circle', 'status' => 'available'],
                ['number' => '17', 'name' => 'Garden 3', 'capacity' => 6, 'position_x' => 310, 'position_y' => 50, 'shape' => 'rectangle', 'width' => 130, 'status' => 'reserved'],
                ['number' => '18', 'name' => 'Garden 4', 'capacity' => 2, 'position_x' => 50, 'position_y' => 180, 'shape' => 'circle', 'status' => 'available'],
                ['number' => '19', 'name' => 'Garden 5', 'capacity' => 4, 'position_x' => 180, 'position_y' => 180, 'shape' => 'square', 'status' => 'occupied'],
                ['number' => '20', 'name' => 'Garden 6', 'capacity' => 8, 'position_x' => 310, 'position_y' => 180, 'shape' => 'rectangle', 'width' => 160, 'status' => 'available'],
            ],
            default => [
                // Default tables for any other floor
                ['number' => 'A1', 'name' => 'Meja A1', 'capacity' => 4, 'position_x' => 50, 'position_y' => 50, 'shape' => 'square', 'status' => 'available'],
                ['number' => 'A2', 'name' => 'Meja A2', 'capacity' => 4, 'position_x' => 170, 'position_y' => 50, 'shape' => 'square', 'status' => 'available'],
                ['number' => 'A3', 'name' => 'Meja A3', 'capacity' => 2, 'position_x' => 290, 'position_y' => 50, 'shape' => 'square', 'status' => 'available'],
                ['number' => 'A4', 'name' => 'Meja A4', 'capacity' => 6, 'position_x' => 410, 'position_y' => 50, 'shape' => 'rectangle', 'status' => 'available'],
            ],
        };
    }
}
