<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FlashSale;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FlashSaleController extends Controller
{
    public function index(): JsonResponse
    {
        $activeSales = FlashSale::with('product')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->get();

        return response()->json([
            'data' => $activeSales->map(fn (FlashSale $sale) => [
                'id' => $sale->id,
                'product_id' => $sale->product_id,
                'product_name' => $sale->product->name,
                'regular_price' => (float) $sale->product->price,
                'discounted_price' => (float) $sale->discounted_price,
                'starts_at' => $sale->starts_at->toIso8601String(),
                'ends_at' => $sale->ends_at->toIso8601String(),
            ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'discounted_price' => ['required', 'numeric', 'min:0'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ]);

        $product = Product::findOrFail($validated['product_id']);

        if ((float) $validated['discounted_price'] >= (float) $product->price) {
            return response()->json([
                'message' => 'The flash sale price must be lower than the regular price.',
            ], 422);
        }

        $flashSale = FlashSale::create($validated);

        return response()->json([
            'data' => $flashSale,
        ], 201);
    }
}
