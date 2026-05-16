<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        StockMovement::query()->delete();
        Product::query()->delete();

        User::query()->firstOrCreate(
            ['email' => 'admin@stockflow.local'],
            ['name' => 'Natienza Admin', 'role' => 'Admin', 'password' => Hash::make('password')]
        );

        $user = User::query()->where('email', 'admin@stockflow.local')->first();

        Setting::setValue('company_name', 'StockFlow POS & Inventory');
        Setting::setValue('company_address', 'Main Kitchen');

        $products = collect([
            ['FLR-001', 'flour', 'General', 'kg', 150.00, 100, 10, true],
            ['TPE-001', 'tape', 'General', 'pc', 1.00, 100, 10, false],
        ])->mapWithKeys(function (array $item): array {
            [$sku, $name, $category, $unit, $price, $stock, $threshold, $costing] = $item;

            $product = Product::query()->create([
                'sku' => $sku,
                'name' => $name,
                'category' => $category,
                'unit' => $unit,
                'price' => $price,
                'starting_stock' => $stock,
                'current_stock' => $stock,
                'low_stock_threshold' => $threshold,
                'include_in_costing' => $costing,
            ]);

            return [$name => $product];
        });

        $today = Carbon::today()->setTime(20, 33);
        $movements = [
            ['flour', 'In', 10, 'Supplier', 'Stock Arrival', $today->copy()],
            ['flour', 'Out', 10, 'Kitchen', 'Kitchen usage', $today->copy()->addMinute()],
            ['tape', 'Out', 50, 'Bakery', 'Bakery usage', $today->copy()->addMinutes(8)],
            ['flour', 'Out', 20, 'Kitchen', 'Kitchen usage', $today->copy()->addMinutes(9)],
        ];

        foreach ($movements as [$name, $type, $quantity, $department, $reason, $createdAt]) {
            StockMovement::query()->create([
                'product_id' => $products[$name]->id,
                'type' => $type,
                'quantity' => $quantity,
                'department' => $department,
                'reason' => $reason,
                'user_id' => $user->id,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        $products['flour']->update(['current_stock' => 80]);
        $products['tape']->update(['current_stock' => 50]);
    }
}
