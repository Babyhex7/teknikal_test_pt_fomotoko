<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'sku',
        'description',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class);
    }

    public function flashSales(): HasMany
    {
        return $this->hasMany(FlashSale::class);
    }

    /**
     * The flash sale currently in effect for this product, if any.
     */
    public function activeFlashSale(): ?FlashSale
    {
        return $this->flashSales
            ->first(fn (FlashSale $sale) => $sale->isActive());
    }

    /**
     * The price a customer pays right now: the flash-sale price when one is
     * running, otherwise the regular price.
     */
    public function currentPrice(): float
    {
        return (float) ($this->activeFlashSale()?->discounted_price ?? $this->price);
    }
}
