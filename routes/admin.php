<?php

use App\Http\Controllers\Console\AdminLoginController;
use App\Http\Controllers\Console\AdminProductController;
use App\Http\Controllers\Console\AdminShopController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AdminLoginController::class, 'create'])->name('login');
Route::post('/login', [AdminLoginController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('login.store');

Route::middleware(['auth', 'admin'])->group(function () {
    Route::post('/logout', [AdminLoginController::class, 'destroy'])->name('admin.logout');
    Route::get('/index', [AdminShopController::class, 'getIndex'])->name('admin.admin_shop.index.get');
    Route::get('/products', [AdminProductController::class, 'index'])->name('admin.products.index');
    Route::get('/products/create', [AdminProductController::class, 'create'])->name('admin.products.create');
    Route::post('/products', [AdminProductController::class, 'store'])->name('admin.products.store');
    Route::get('/products/{product}/edit', [AdminProductController::class, 'edit'])->name('admin.products.edit');
    Route::put('/products/{product}', [AdminProductController::class, 'update'])->name('admin.products.update');
    Route::delete('/products/{product}', [AdminProductController::class, 'destroy'])->name('admin.products.destroy');
});
