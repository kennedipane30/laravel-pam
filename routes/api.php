<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\UmkmApiController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\CartController; // <-- JANGAN LUPA TAMBAH INI

// =================================================================
// ROUTE PUBLIC (Bisa diakses tanpa login)
// =================================================================

// Autentikasi
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Produk
Route::get('/products', [ProductController::class, 'index']);


// =================================================================
// ROUTE PROTECTED (Harus Login & Token Bearer)
// =================================================================
Route::middleware(['auth:sanctum'])->group(function () {

    // --- User ---
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // --- UMKM ---
    Route::get('/umkm/status', [UmkmApiController::class, 'checkStatus']);
    Route::post('/umkm/register', [UmkmApiController::class, 'register']);

    // --- Produk Seller ---
    Route::post('/products', [ProductController::class, 'store']);


    // =============================================================
    // -------------------------- CART ------------------------------
    // =============================================================
    Route::get('/cart', [CartController::class, 'index']);                          // Lihat keranjang
    Route::post('/cart/add', [CartController::class, 'addToCart']);                // Tambah ke keranjang
    Route::put('/cart/items/{id}', [CartController::class, 'updateQuantity']);     // Update quantity item
    Route::put('/cart/items/{id}/note', [CartController::class, 'updateNote']);    // Update catatan item
    Route::delete('/cart/items/{id}', [CartController::class, 'removeItem']);      // Hapus item
    Route::delete('/cart/clear', [CartController::class, 'clearCart']);            // Kosongkan keranjang


    // =============================================================
    // ------------------------- ORDER ------------------------------
    // =============================================================

    // Detail Pesanan
    Route::get('/orders/{id}', [OrderController::class, 'show']);

    // Pembeli
    Route::post('/checkout', [OrderController::class, 'checkout']);
    Route::get('/my-orders', [OrderController::class, 'myOrders']);
    Route::post('/orders/{id}/complete', [OrderController::class, 'completeOrder']);

    // Penjual
    Route::get('/umkm/orders', [OrderController::class, 'getUmkmIncomingOrders']);
    Route::post('/umkm/orders/{id}/ship', [OrderController::class, 'shipOrder']);

    // Review
    Route::post('/reviews', [ReviewController::class, 'store']);

    // Notifikasi
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
});
