<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown when an order would require more units of a product than are
 * currently available in inventory.
 */
class InsufficientStockException extends Exception
{
    public function __construct(
        public readonly int $productId,
        public readonly int $requested,
        public readonly int $available,
    ) {
        parent::__construct(
            "Insufficient stock for product #{$productId}: requested {$requested}, only {$available} available."
        );
    }
}
