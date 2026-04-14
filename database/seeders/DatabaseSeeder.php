<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\{Branch, BranchStock, Customer, LicensePlan, Product, Shop, User};

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Shop ─────────────────────────────────────────────────────────────
        $shop = Shop::create([
            'name'            => 'Demo Store',
            'slug'            => 'demo-store',
            'email'           => 'admin@demo.com',
            'phone'           => '0244000000',
            'address'         => 'Accra, Ghana',
            'currency'        => 'GHS',
            'currency_symbol' => '₵',
        ]);

        // ─── Branches ─────────────────────────────────────────────────────────
        $mainBranch = Branch::create([
            'shop_id' => $shop->id,
            'name'    => 'Main Branch',
            'address' => 'Accra Central',
            'phone'   => '0244000001',
        ]);

        $branch2 = Branch::create([
            'shop_id' => $shop->id,
            'name'    => 'Kumasi Branch',
            'address' => 'Kumasi Adum',
            'phone'   => '0244000002',
        ]);

        // ─── Users ────────────────────────────────────────────────────────────
        $owner = User::create([
            'shop_id'   => $shop->id,
            'branch_id' => $mainBranch->id,
            'name'      => 'Shop Owner',
            'email'     => 'owner@demo.com',
            'role'      => 'owner',
            'password'  => Hash::make('password'),
        ]);

        User::create([
            'shop_id'   => $shop->id,
            'branch_id' => $mainBranch->id,
            'name'      => 'Main Cashier',
            'email'     => 'cashier@demo.com',
            'role'      => 'cashier',
            'password'  => Hash::make('password'),
        ]);

        User::create([
            'shop_id'   => $shop->id,
            'branch_id' => $branch2->id,
            'name'      => 'Kumasi Manager',
            'email'     => 'manager@demo.com',
            'role'      => 'manager',
            'password'  => Hash::make('password'),
        ]);

        // ─── Products ─────────────────────────────────────────────────────────
        $products = [
            ['name' => 'Coca-Cola 500ml',     'barcode' => '5000112637922', 'category' => 'Beverages',  'price' => 5.00,  'cost' => 3.50,  'unit' => 'bottle'],
            ['name' => 'Fanta Orange 500ml',  'barcode' => '5000112000070', 'category' => 'Beverages',  'price' => 5.00,  'cost' => 3.50,  'unit' => 'bottle'],
            ['name' => 'Malt Can 330ml',      'barcode' => '6001007101756', 'category' => 'Beverages',  'price' => 6.00,  'cost' => 4.00,  'unit' => 'can'],
            ['name' => 'Uncle Ben\'s Rice 1kg','barcode'=> '5000077015941', 'category' => 'Grains',     'price' => 18.00, 'cost' => 12.00, 'unit' => 'bag'],
            ['name' => 'Indomie Noodles',     'barcode' => '8850135010170', 'category' => 'Noodles',    'price' => 3.50,  'cost' => 2.20,  'unit' => 'pack'],
            ['name' => 'Bread Loaf',          'barcode' => '0001234567890', 'category' => 'Bakery',     'price' => 12.00, 'cost' => 8.00,  'unit' => 'loaf'],
            ['name' => 'Peak Milk Tin 400g',  'barcode' => '5010029012004', 'category' => 'Dairy',      'price' => 28.00, 'cost' => 20.00, 'unit' => 'tin'],
            ['name' => 'Milo 400g',           'barcode' => '5000159364701', 'category' => 'Beverages',  'price' => 32.00, 'cost' => 22.00, 'unit' => 'tin'],
            ['name' => 'Sugar 1kg',           'barcode' => '0009876543210', 'category' => 'Essentials', 'price' => 10.00, 'cost' => 7.00,  'unit' => 'kg'],
            ['name' => 'Vegetable Oil 1L',    'barcode' => '0001111111111', 'category' => 'Essentials', 'price' => 22.00, 'cost' => 16.00, 'unit' => 'bottle'],
        ];

        foreach ($products as $pd) {
            $product = Product::create(array_merge($pd, ['shop_id' => $shop->id]));

            // Stock in main branch
            BranchStock::create([
                'branch_id'       => $mainBranch->id,
                'product_id'      => $product->id,
                'quantity'        => rand(20, 100),
                'low_stock_alert' => 5,
            ]);

            // Stock in branch 2
            BranchStock::create([
                'branch_id'       => $branch2->id,
                'product_id'      => $product->id,
                'quantity'        => rand(10, 60),
                'low_stock_alert' => 5,
            ]);
        }

        // ─── Customers ────────────────────────────────────────────────────────
        $customers = [
            ['name' => 'Kwame Mensah',   'phone' => '0241000001', 'outstanding_balance' => 45.00],
            ['name' => 'Akosua Boateng', 'phone' => '0241000002', 'outstanding_balance' => 0],
            ['name' => 'Yaw Darko',      'phone' => '0241000003', 'outstanding_balance' => 120.00],
            ['name' => 'Ama Asante',     'phone' => '0241000004', 'outstanding_balance' => 0],
        ];

        foreach ($customers as $c) {
            Customer::create(array_merge($c, ['shop_id' => $shop->id]));
        }

        $this->command->info('✅ Demo data seeded!');
        $this->command->info('   Owner:   owner@demo.com / password');
        $this->command->info('   Cashier: cashier@demo.com / password');
        $this->command->info('   Manager: manager@demo.com / password');

        // LicensePlan::updateOrCreate(
        //     ['slug' => 'month_1'],
        //     ['name' => '1 Month', 'duration_days' => 30, 'price' => 50.00, 'currency' => 'GHS', 'is_active' => true]
        // );
        // LicensePlan::updateOrCreate(
        //     ['slug' => 'month_2'],
        //     ['name' => '2 Months', 'duration_days' => 60, 'price' => 90.00, 'currency' => 'GHS', 'is_active' => true]
        // );
        // LicensePlan::updateOrCreate(
        //     ['slug' => 'year_1'],
        //     ['name' => '1 Year', 'duration_days' => 365, 'price' => 300.00, 'currency' => 'GHS', 'is_active' => true]
        // );
    }
}