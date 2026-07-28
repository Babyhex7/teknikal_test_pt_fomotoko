<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * Proves the flash-sale purchase path can't oversell stock under real
 * concurrency.
 *
 * A single PHP process can't produce a genuine race condition — it only
 * ever runs one line of code at a time. So instead of RefreshDatabase (which
 * wraps the test in an uncommitted transaction that other processes can't
 * see), this test commits real rows to the `online_store_testing` MySQL
 * database, then spawns many independent `php artisan orders:purchase`
 * child processes and starts them all before waiting on any of them. That
 * gives true OS-level concurrency: multiple real connections racing to
 * lock and decrement the same inventory row.
 */
class FlashSaleRaceConditionTest extends TestCase
{
    private ?Product $product = null;

    protected function tearDown(): void
    {
        if ($this->product) {
            $orderIds = OrderItem::where('product_id', $this->product->id)->pluck('order_id');
            OrderItem::where('product_id', $this->product->id)->delete();
            Order::whereIn('id', $orderIds)->delete();
            $this->product->delete(); // cascades to inventory & flash_sales
        }

        parent::tearDown();
    }

    public function test_concurrent_purchases_never_oversell_stock(): void
    {
        $startingStock = 10;
        $concurrentBuyers = 30; // deliberately more buyers than stock

        $this->product = Product::create([
            'name' => 'Race Condition Test Product',
            'sku' => 'RACE-TEST-'.uniqid(),
            'price' => 100,
        ]);

        $inventory = Inventory::create([
            'product_id' => $this->product->id,
            'quantity' => $startingStock,
        ]);

        $processes = [];
        for ($i = 0; $i < $concurrentBuyers; $i++) {
            $process = new Process([
                PHP_BINARY,
                base_path('artisan'),
                'orders:purchase',
                (string) $this->product->id,
                '1',
                '--email=buyer'.$i.'@example.com',
            ]);
            $process->setTimeout(30);
            $process->start();
            $processes[] = $process;
        }

        // All processes were started above without waiting, so they run
        // concurrently. Only now do we block until each one finishes.
        $succeeded = 0;
        $failed = 0;
        foreach ($processes as $process) {
            $process->wait();
            $process->isSuccessful() ? $succeeded++ : $failed++;
        }

        $inventory->refresh();

        $this->assertSame(
            $startingStock,
            $succeeded,
            "Expected exactly {$startingStock} purchases to succeed (one per unit of stock)."
        );
        $this->assertSame($concurrentBuyers - $startingStock, $failed);
        $this->assertGreaterThanOrEqual(0, $inventory->quantity, 'Inventory must never go negative.');
        $this->assertSame(0, $inventory->quantity, 'Inventory should be fully depleted, not left over.');

        // Every successful purchase must be reflected by exactly one
        // order_items row, confirming no order was lost or double-counted.
        $this->assertSame(
            $startingStock,
            OrderItem::where('product_id', $this->product->id)->count()
        );

        // And every order created during the burst is for exactly 1 unit —
        // no buyer's quantity was corrupted by another buyer's transaction.
        $this->assertSame(
            $startingStock,
            Order::whereHas('items', fn ($q) => $q->where('product_id', $this->product->id))->count()
        );
    }
}
