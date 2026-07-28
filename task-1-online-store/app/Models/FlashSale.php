<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlashSale extends Model
{
    protected $fillable = [
        'product_id',
        'discounted_price',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'discounted_price' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isActive(): bool
    {
        $now = now();

        return $this->starts_at->lessThanOrEqualTo($now)
            && $this->ends_at->greaterThanOrEqualTo($now);
    }
}
