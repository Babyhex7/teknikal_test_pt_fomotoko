<?php

namespace Tests\Feature;

use App\Models\FlashSale;
use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_requires_at_least_one_order_item(): void
    {
        $response = $this->postJson('/api/orders', [
            'customer_email' => 'buyer@example.com',
            'items' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('items');
    }

    public function test_it_places_an_order_at_the_active_flash_sale_price(): void
    {
        $product = Product::create([
            'name' => 'Discounted Widget',
            'sku' => 'WIDGET-1',
            'price' => 100,
        ]);
        Inventory::create(['product_id' => $product->id, 'quantity' => 5]);
        FlashSale::create([
            'product_id' => $product->id,
            'discounted_price' => 40,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addHour(),
        ]);

        $response = $this->postJson('/api/orders', [
            'customer_email' => 'buyer@example.com',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ]);

        $response->assertStatus(201);
        $this->assertEquals(80.0, $response->json('data.total'));
        $this->assertEquals(40.0, $response->json('data.items.0.unit_price'));

        $this->assertSame(3, $product->inventory->fresh()->quantity);
    }

    public function test_it_rejects_an_order_that_exceeds_available_stock(): void
    {
        $product = Product::create(['name' => 'Scarce Item', 'sku' => 'SCARCE-1', 'price' => 10]);
        Inventory::create(['product_id' => $product->id, 'quantity' => 1]);

        $response = $this->postJson('/api/orders', [
            'customer_email' => 'buyer@example.com',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ]);

        $response->assertStatus(409)
            ->assertJsonPath('error', 'insufficient_stock');

        $this->assertSame(1, $product->inventory->fresh()->quantity);
    }

    public function test_it_returns_404_for_a_nonexistent_product(): void
    {
        $response = $this->postJson('/api/orders', [
            'customer_email' => 'buyer@example.com',
            'items' => [['product_id' => 999999, 'quantity' => 1]],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('items.0.product_id');
    }
}
