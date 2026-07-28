<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orders) {}

    public function index(): JsonResponse
    {
        $orders = Order::with('items.product')->latest()->get();

        return response()->json([
            'data' => OrderResource::collection($orders),
        ]);
    }

    public function show(Order $order): JsonResponse
    {
        $order->load('items.product');

        return response()->json([
            'data' => new OrderResource($order),
        ]);
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->orders->placeOrder(
                $request->validated('customer_email'),
                $request->validated('items'),
            );
        } catch (InsufficientStockException $e) {
            // 409 Conflict: the request is well-formed, but can't be
            // fulfilled because concurrent demand has exhausted the stock.
            return response()->json([
                'message' => $e->getMessage(),
                'error' => 'insufficient_stock',
                'product_id' => $e->productId,
                'requested' => $e->requested,
                'available' => $e->available,
            ], 409);
        }

        return response()->json([
            'data' => new OrderResource($order),
        ], 201);
    }
}
