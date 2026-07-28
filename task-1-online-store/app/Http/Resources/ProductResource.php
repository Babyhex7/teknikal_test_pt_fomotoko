<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $activeFlashSale = $this->activeFlashSale();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'description' => $this->description,
            'price' => (float) $this->price,
            'current_price' => $this->currentPrice(),
            'in_stock_quantity' => $this->whenLoaded('inventory', fn () => $this->inventory?->quantity ?? 0),
            'flash_sale' => $activeFlashSale ? [
                'discounted_price' => (float) $activeFlashSale->discounted_price,
                'starts_at' => $activeFlashSale->starts_at->toIso8601String(),
                'ends_at' => $activeFlashSale->ends_at->toIso8601String(),
            ] : null,
        ];
    }
}
