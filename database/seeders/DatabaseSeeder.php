<?php

namespace Database\Seeders;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => 'admin',
            'role' => 'admin',
        ]);

        $member = User::create([
            'name' => 'Member',
            'username' => 'member',
            'email' => 'member@example.com',
            'password' => 'member',
            'role' => 'member',
        ]);

        $items = [
            ['Kabel USB-C 1 m', 'KBL-001', 48, 'pcs', 35000, 10, "Kabel **USB-C** untuk pengisian dan data.\n\n- Panjang 1 meter\n- Mendukung fast charging"],
            ['Mouse Nirkabel', 'MSE-014', 22, 'pcs', 89000, 8, 'Mouse nirkabel 2.4 GHz dengan baterai AA.'],
            ['Kertas A4 80 gsm', 'KRT-100', 6, 'rim', 52000, 10, 'Kertas HVS putih, isi 500 lembar per rim.'],
            ['Tinta Printer Hitam', 'TNT-210', 0, 'botol', 65000, 5, 'Tinta isi ulang botol 100 ml.'],
            ['Flashdisk 32 GB', 'FLD-032', 31, 'pcs', 70000, 10, null],
            ['Baterai AA (isi 4)', 'BTR-004', 4, 'pak', 28000, 6, 'Baterai alkaline isi 4.'],
            ['Spidol Papan Tulis', 'SPD-020', 75, 'pcs', 9000, 20, null],
            ['Lakban Bening', 'LKB-050', 15, 'roll', 7500, 10, null],
            ['Stapler Kecil', 'STP-002', 5, 'pcs', 18000, 5, 'Stapler untuk kantor, isi staples No. 10.'],
            ['Map Plastik Folder', 'MAP-120', 120, 'pcs', 3500, 30, null],
            ['Headset Kabel', 'HDS-007', 3, 'pcs', 115000, 5, 'Headset dengan mikrofon, jack 3,5 mm.'],
            ['Papan Tulis Kecil', 'PPT-001', 0, 'pcs', 145000, 2, null],
        ];

        foreach ($items as [$name, $sku, $quantity, $unit, $price, $minStock, $description]) {
            Stock::create([
                'user_id' => $member->id,
                'name' => $name,
                'sku' => $sku,
                'description' => $description,
                'quantity' => $quantity,
                'unit' => $unit,
                'price' => $price,
                'min_stock' => $minStock,
                'status' => StockStatus::fromQuantity($quantity, $minStock),
            ]);
        }
    }
}
