<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * Places an order for one or more products, decrementing inventory
     * atomically so that concurrent flash-sale purchases can never push
     * stock below zero.
     *
     * @param  array<int, array{product_id: int, quantity: int}>  $items
     *
     * @throws InsufficientStockException When any line item can't be fully stocked.
     * @throws ModelNotFoundException When a product doesn't exist.
     */
    public function placeOrder(string $customerEmail, array $items): Order
    {
        // Lock rows in a consistent order (ascending product_id) across all
        // requests. This is what prevents two concurrent multi-item orders
        // from deadlocking each other by locking the same rows in opposite
        // order.
        $items = collect($items)->sortBy('product_id')->values()->all();

        return DB::transaction(function () use ($customerEmail, $items) {
            $order = Order::create([
                'customer_email' => $customerEmail,
                'status' => 'completed',
                'total' => 0,
            ]);

            $total = 0;

            foreach ($items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $quantity = (int) $item['quantity'];

                // SELECT ... FOR UPDATE: blocks any other transaction from
                // reading/locking this same inventory row until we commit or
                // roll back, so two concurrent requests can never both see
                // "enough stock" for the same last unit.
                $inventory = Inventory::where('product_id', $product->id)
                    ->lockForUpdate()
                    ->first();

                if (! $inventory || $inventory->quantity < $quantity) {
                    throw new InsufficientStockException(
                        productId: $product->id,
                        requested: $quantity,
                        available: $inventory->quantity ?? 0,
                    );
                }

                $inventory->decrement('quantity', $quantity);

                $unitPrice = $product->currentPrice();
                $subtotal = round($unitPrice * $quantity, 2);

                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ]);

                $total += $subtotal;
            }

            $order->update(['total' => $total]);

            return $order->load('items.product');
        });
    }
}
