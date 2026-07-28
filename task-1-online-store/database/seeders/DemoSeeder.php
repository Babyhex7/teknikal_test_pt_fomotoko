<?php

namespace Database\Seeders;

use App\Models\FlashSale;
use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Seeds a couple of regular products plus one product that's mid flash-sale,
 * so the API is browsable and the race-condition scenario is reproducible
 * by hand right after a fresh install.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $flashSaleProduct = Product::create([
            'name' => 'Limited Edition Sneakers',
            'sku' => 'SNEAKER-FLASH-001',
            'description' => 'Flash-sale item with very limited stock.',
            'price' => 199.99,
        ]);

        Inventory::create([
            'product_id' => $flashSaleProduct->id,
            'quantity' => 10,
        ]);

        FlashSale::create([
            'product_id' => $flashSaleProduct->id,
            'discounted_price' => 49.99,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addHour(),
        ]);

        $regularProduct = Product::create([
            'name' => 'Everyday Backpack',
            'sku' => 'BACKPACK-STD-001',
            'description' => 'Regular catalog item, no flash sale.',
            'price' => 79.50,
        ]);

        Inventory::create([
            'product_id' => $regularProduct->id,
            'quantity' => 100,
        ]);
    }
}
