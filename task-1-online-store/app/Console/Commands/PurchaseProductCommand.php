<?php

namespace App\Console\Commands;

use App\Exceptions\InsufficientStockException;
use App\Services\OrderService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Places a single-item order from the command line.
 *
 * This exists so a race-condition test can spawn many real, independent OS
 * processes that all hit the database at once — a much more honest test of
 * concurrency safety than anything running inside a single PHP process.
 */
#[Signature('orders:purchase {product_id} {quantity=1} {--email=race-test@example.com}')]
#[Description('Place an order for a single product; used to simulate concurrent flash-sale buyers.')]
class PurchaseProductCommand extends Command
{
    public function handle(OrderService $orders): int
    {
        $productId = (int) $this->argument('product_id');
        $quantity = (int) $this->argument('quantity');

        try {
            $order = $orders->placeOrder($this->option('email'), [
                ['product_id' => $productId, 'quantity' => $quantity],
            ]);
        } catch (InsufficientStockException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Order #{$order->id} placed for product #{$productId} x{$quantity}.");

        return self::SUCCESS;
    }
}
