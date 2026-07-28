# Task 1 — Online Store API (Laravel)

A JSON API for a simple online store: products, inventory, flash sales and
orders, with a purchase flow that is safe under concurrent load.

## Domain model

| Table | Purpose |
|---|---|
| `products` | Catalog item: name, SKU, regular price. |
| `inventories` | One row per product, tracks `quantity` on hand. Has a DB-level `CHECK (quantity >= 0)` constraint as a last line of defense. |
| `flash_sales` | A time-boxed discounted price for a product (`starts_at` / `ends_at`). |
| `orders` | One purchase, with a `customer_email` and a `total`. |
| `order_items` | Line items of an order — at least one is required per order. |

A product's "current price" is its flash-sale price whenever an active flash
sale exists for it (`starts_at <= now <= ends_at`), otherwise its regular
price. See `Product::currentPrice()`.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Create a MySQL database (this project was built and tested against
XAMPP's bundled MariaDB) and point `.env` at it:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=online_store
DB_USERNAME=root
DB_PASSWORD=
```

Then migrate and seed a couple of demo products (one of them mid flash
sale):

```bash
php artisan migrate --seed
php artisan serve
```

## Endpoints

All responses are JSON.

| Method | Path | Description | Success | Failure |
|---|---|---|---|---|
| GET | `/api/products` | List products, with current price / stock / active flash sale. | 200 | — |
| GET | `/api/products/{id}` | Show one product. | 200 | 404 |
| POST | `/api/products` | Create a product + its initial inventory. | 201 | 422 |
| GET | `/api/flash-sales` | List currently active flash sales. | 200 | — |
| POST | `/api/flash-sales` | Start a flash sale for a product. | 201 | 422 |
| GET | `/api/orders` | List orders with their line items. | 200 | — |
| GET | `/api/orders/{id}` | Show one order. | 200 | 404 |
| POST | `/api/orders` | Place an order (see below). | 201 | 409 / 422 |

### `POST /api/orders`

```json
{
  "customer_email": "buyer@example.com",
  "items": [
    { "product_id": 1, "quantity": 2 }
  ]
}
```

- `items` must contain at least one entry (an order needs at minimum one
  order item).
- Each item is priced at the product's flash-sale price if one is active,
  otherwise its regular price.
- On success: `201` with the created order and its items.
- If a product doesn't exist or `items` is malformed: `422` with field
  errors.
- If there isn't enough stock left to fulfil an item: `409 Conflict` with
  `{"error": "insufficient_stock", "product_id", "requested", "available"}`.

## How the flash-sale race condition is prevented

`App\Services\OrderService::placeOrder()` is the single place an order gets
created, used by both the HTTP controller and the `orders:purchase` CLI
command. For every line item it:

1. Opens a DB transaction.
2. Runs `SELECT ... FOR UPDATE` on that product's `inventories` row
   (pessimistic row lock) — this blocks any other transaction from reading
   the same row until the current one commits or rolls back.
3. Checks `quantity >= requested`; throws `InsufficientStockException`
   (→ HTTP 409) if not.
4. Decrements the quantity and creates the order line item.
5. Commits.

Because the check-then-decrement happens while holding the row lock, two
concurrent requests for the last unit of stock can't both see "enough
stock is available" — the second one always sees the already-decremented
value once it acquires the lock. Multi-item orders lock their rows in
ascending `product_id` order, so two concurrent multi-item orders can't
deadlock each other. The `inventories.quantity` column also has a DB-level
`CHECK (quantity >= 0)` constraint as a second line of defense.

## Running the tests

```bash
php artisan test
```

This runs:

- `tests/Feature/OrderApiTest.php` — happy path, validation, and
  insufficient-stock cases over HTTP (uses `RefreshDatabase`).
- `tests/Feature/FlashSaleRaceConditionTest.php` — the concurrency test
  (see below).

### The race-condition test, in detail

A single PHP process only ever executes one line at a time, so it can't by
itself create a genuine race condition. `FlashSaleRaceConditionTest`
instead:

1. Seeds a product with 10 units of stock, committed directly to the
   `online_store_testing` MySQL database (not wrapped in a test
   transaction, since child processes need to see it).
2. Spawns 30 independent `php artisan orders:purchase {product} 1` child
   **OS processes** via `Symfony\Component\Process\Process`, starting all
   of them before waiting on any of them — genuine concurrent database
   connections racing for the same row, not simulated concurrency inside
   one process.
3. Asserts exactly 10 succeed, 20 fail with insufficient stock, final
   inventory is exactly `0` (never negative), and the `order_items` /
   `orders` rows created match the successful count exactly.

Before running it the first time, migrate the testing database:

```bash
cp .env.testing.example .env.testing   # adjust DB credentials if needed
php artisan migrate:fresh --env=testing
php artisan test --filter=FlashSaleRaceConditionTest
```
