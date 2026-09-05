<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\UserController; // Import UserController

// Public routes (accessible without authentication)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Rute untuk mendapatkan user yang sedang login (bisa diakses semua user terautentikasi)
    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/profile', [AuthController::class, 'user']);

    // Grup rute untuk 'cashier' dan 'admin' (Semua fitur POS kecuali manajemen user)
    Route::middleware('role:admin,cashier')->group(function () {
        Route::apiResource('categories', CategoryController::class);
        Route::get('/products/all', [ProductController::class, 'all']);
        Route::apiResource('products', ProductController::class);
        Route::apiResource('customers', CustomerController::class);
        Route::apiResource('payment-methods', PaymentMethodController::class);
        Route::apiResource('suppliers', SupplierController::class);
        Route::apiResource('shifts', ShiftController::class);
        Route::apiResource('expenses', ExpenseController::class);
        Route::apiResource('orders', OrderController::class);
        Route::get('/orders/{order}/pdf', [OrderController::class, 'pdf']);
        Route::get('/orders/{order}/print', [OrderController::class, 'print']);
        Route::apiResource('purchases', PurchaseController::class);
    });

    // Grup rute khusus untuk 'admin' (Manajemen User)
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('users', UserController::class); // Endpoint untuk mengelola user
    });
});