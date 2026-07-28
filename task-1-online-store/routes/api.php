<?php

use App\Http\Controllers\Api\FlashSaleController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

Route::apiResource('products', ProductController::class)->only(['index', 'show', 'store']);
Route::apiResource('flash-sales', FlashSaleController::class)->only(['index', 'store']);
Route::apiResource('orders', OrderController::class)->only(['index', 'show', 'store']);
