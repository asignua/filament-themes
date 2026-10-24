<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use Workbench\App\Models\Order;
use Workbench\App\Models\Product;
use Workbench\App\Models\User;

/**
 * Screenshot data (`vendor/bin/testbench db:seed --class='Workbench\Database\Seeders\DemoSeeder'` after
 * `workbench:build`). Log in as emma@example.com / password.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->firstOrCreate(['email' => 'emma@example.com'], ['name' => 'Emma Carter', 'password' => 'password']);

        // [name, sku, category, price, stock, active]
        $products = [
            ['Corrugated box 40x30x20', 'BX-4030', 'Boxes', 1.20, 480, true],
            ['Euro pallet EPAL', 'PL-EPAL', 'Pallets', 14.50, 62, true],
            ['Stretch film 500 mm', 'FL-500', 'Film', 8.90, 0, true],
            ['Thermal label roll 100x150', 'LB-100150', 'Labels', 5.40, 210, true],
            ['Packing tape, brown', 'TP-BRN', 'Tape', 1.75, 35, false],
            ['Bubble wrap 50 m', 'BW-50', 'Film', 19.00, 14, true],
        ];

        foreach ($products as [$name, $sku, $category, $price, $stock, $active]) {
            Product::query()->firstOrCreate(['sku' => $sku], [
                'name' => $name, 'category' => $category, 'price' => $price, 'stock' => $stock, 'is_active' => $active,
                'description' => 'Sample product for the theme screenshots.',
            ]);
        }

        // [number, customer, status, total]
        $orders = [
            ['SO-1001', 'Northwind Traders', 'paid', 1240.00],
            ['SO-1002', 'Acme Logistics', 'pending', 318.40],
            ['SO-1003', 'Globex GmbH', 'shipped', 2890.95],
            ['SO-1004', 'Initech', 'cancelled', 76.00],
            ['SO-1005', 'Umbrella Foods', 'paid', 540.10],
            ['SO-1006', 'Stark Industries', 'refunded', 99.99],
        ];

        foreach ($orders as $i => [$number, $customer, $status, $total]) {
            Order::query()->firstOrCreate(['number' => $number], [
                'customer' => $customer, 'status' => $status, 'total' => $total, 'placed_at' => now()->subDays($i + 1),
            ]);
        }
    }
}
