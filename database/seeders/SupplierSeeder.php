<?php

namespace Database\Seeders;

use App\Models\Supplier;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $this->seedSuppliersForTenant($tenant);
        }
    }

    private function seedSuppliersForTenant(Tenant $tenant): void
    {
        $suppliers = [
            [
                'code' => 'SUP-001',
                'name' => 'PT Indofood Sukses Makmur',
                'contact_person' => 'Budi Santoso',
                'email' => 'budi@indofood.co.id',
                'phone' => '021-5795-8822',
                'address' => 'Jl. Jend. Sudirman Kav. 76-78',
                'city' => 'Jakarta',
                'tax_number' => '01.234.567.8-012.000',
                'payment_terms' => 30,
                'notes' => 'Supplier utama bahan kering dan bumbu',
            ],
            [
                'code' => 'SUP-002',
                'name' => 'PT Japfa Comfeed Indonesia',
                'contact_person' => 'Siti Rahayu',
                'email' => 'siti@japfa.co.id',
                'phone' => '021-5795-7000',
                'address' => 'Jl. Daan Mogot Km. 12',
                'city' => 'Tangerang',
                'tax_number' => '02.345.678.9-012.000',
                'payment_terms' => 14,
                'notes' => 'Supplier ayam dan daging',
            ],
            [
                'code' => 'SUP-003',
                'name' => 'PT Sumber Alfaria Trijaya',
                'contact_person' => 'Ahmad Wijaya',
                'email' => 'ahmad@alfamart.co.id',
                'phone' => '021-5494-1818',
                'address' => 'Jl. M.H. Thamrin No. 9',
                'city' => 'Tangerang',
                'tax_number' => '03.456.789.0-012.000',
                'payment_terms' => 7,
                'notes' => 'Supplier kebutuhan packaging',
            ],
            [
                'code' => 'SUP-004',
                'name' => 'PT Fresh Produce Indonesia',
                'contact_person' => 'Lisa Permata',
                'email' => 'lisa@freshproduce.id',
                'phone' => '021-7883-5500',
                'address' => 'Pasar Induk Kramat Jati',
                'city' => 'Jakarta Timur',
                'tax_number' => '04.567.890.1-012.000',
                'payment_terms' => 0,
                'notes' => 'Supplier sayur dan buah segar',
            ],
            [
                'code' => 'SUP-005',
                'name' => 'PT Unilever Indonesia',
                'contact_person' => 'Dewi Anggraini',
                'email' => 'dewi@unilever.com',
                'phone' => '021-526-2112',
                'address' => 'Jl. BSD Boulevard Barat',
                'city' => 'Tangerang Selatan',
                'tax_number' => '05.678.901.2-012.000',
                'payment_terms' => 45,
                'notes' => 'Supplier saus, bumbu, dan cleaning supplies',
            ],
            [
                'code' => 'SUP-006',
                'name' => 'PT Seafood Nusantara',
                'contact_person' => 'Rudi Hartono',
                'email' => 'rudi@seafoodnusantara.id',
                'phone' => '021-4360-8888',
                'address' => 'Muara Angke, Pelabuhan',
                'city' => 'Jakarta Utara',
                'tax_number' => '06.789.012.3-012.000',
                'payment_terms' => 0,
                'notes' => 'Supplier seafood segar',
            ],
            [
                'code' => 'SUP-007',
                'name' => 'PT Frisian Flag Indonesia',
                'contact_person' => 'Maria Chen',
                'email' => 'maria@frisianflag.com',
                'phone' => '021-460-8900',
                'address' => 'Jl. Raya Bogor Km. 26',
                'city' => 'Depok',
                'tax_number' => '07.890.123.4-012.000',
                'payment_terms' => 30,
                'notes' => 'Supplier dairy products',
            ],
            [
                'code' => 'SUP-008',
                'name' => 'CV Berkah Rempah',
                'contact_person' => 'Hasan Basri',
                'email' => 'hasan@berkahrempah.com',
                'phone' => '022-720-3456',
                'address' => 'Jl. Pasar Baru No. 45',
                'city' => 'Bandung',
                'tax_number' => '08.901.234.5-012.000',
                'payment_terms' => 14,
                'notes' => 'Supplier rempah-rempah dan bumbu tradisional',
            ],
        ];

        foreach ($suppliers as $supplierData) {
            Supplier::create(array_merge($supplierData, [
                'tenant_id' => $tenant->id,
                'is_active' => true,
            ]));
        }
    }
}
