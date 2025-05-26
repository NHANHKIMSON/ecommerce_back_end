<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



Route::apiResource('products', ProductController::class);
Route::apiResource('categories', ProductCategoryController::class);
Route::post('/products/{product}', [ProductController::class, 'update'])->name('products.update');



Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
// Route::post('logout', [AuthController::class, 'logout'])->
// middleware('auth:sanctum');
// Route::get('/me', [AuthController::class, 'me']);


// Route::apiResource('orders', OrderController::class)->only(['index', 'store', 'show']);


// Route::apiResource('orders', OrderController::class)->only(['index', 'store', 'show']);
// Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);

Route::middleware('auth:api')->group(function () {
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
});