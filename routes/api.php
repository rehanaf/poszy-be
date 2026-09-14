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
use App\Http\Controllers\DashboardController; // Import DashboardController
use App\Http\Controllers\PointSettingController; // Import PointSettingController
use App\Http\Controllers\StoreController; // Import StoreController

// Public routes (accessible without authentication)
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

// Protected routes (require authentication)
Route::middleware(['auth:sanctum', 'store.context'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/store/switch', [AuthController::class, 'switchStore']);

    // Rute untuk mendapatkan user yang sedang login (bisa diakses semua user terautentikasi)
    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/profile', [AuthController::class, 'user']);

    // Kelola/daftar toko: semua user login (superadmin lihat semua, user lihat punyanya)
    Route::get('/stores', [StoreController::class, 'index']);
    Route::post('/stores', [StoreController::class, 'store']);
    Route::get('/stores/{store}', [StoreController::class, 'show']);
    Route::put('/stores/{store}', [StoreController::class, 'update']);

    // Grup rute untuk 'owner', 'manager', dan 'kasir' (Semua fitur POS kecuali manajemen user)
    Route::middleware('role:owner,manager,kasir')->group(function () {
        Route::apiResource('categories', CategoryController::class);
        Route::get('/products/all', [ProductController::class, 'all']);
        Route::apiResource('products', ProductController::class);
        Route::get('/customers/next-code', [CustomerController::class, 'nextCode']);
        Route::apiResource('customers', CustomerController::class);
        Route::apiResource('payment-methods', PaymentMethodController::class);
        Route::apiResource('suppliers', SupplierController::class);
        Route::apiResource('shifts', ShiftController::class);
        Route::apiResource('expenses', ExpenseController::class);
        Route::apiResource('orders', OrderController::class);
        Route::get('/orders/{order}/pdf', [OrderController::class, 'pdf']);
        Route::get('/orders/{order}/print', [OrderController::class, 'print']);
        Route::apiResource('purchases', PurchaseController::class);
        Route::get('/dashboard/summary', [DashboardController::class, 'summary']);
        Route::get('/point-settings', [PointSettingController::class, 'index']);
        Route::get('/store', [StoreController::class, 'mine']); // Informasi branding toko sendiri
    });

    // Pengaturan poin: owner & manager (kasir hanya membaca)
    Route::middleware('role:owner,manager')->group(function () {
        Route::put('/point-settings', [PointSettingController::class, 'update']);
    });

    // Branding toko: owner (dan superadmin)
    Route::middleware('role:owner')->group(function () {
        Route::put('/store', [StoreController::class, 'updateMine']);
        Route::post('/store/logo', [StoreController::class, 'uploadLogo']);
    });

    // Grup rute khusus untuk 'owner' (Manajemen User)
    Route::middleware('role:owner')->group(function () {
        Route::apiResource('users', UserController::class); // Endpoint untuk mengelola user
    });
});