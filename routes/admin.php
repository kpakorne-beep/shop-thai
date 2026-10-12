<?php

use App\Http\Controllers\Console\AdminLoginController;
use App\Http\Controllers\Console\AdminCategoryController;
use App\Http\Controllers\Console\AdminBankTransferSettingsController;
use App\Http\Controllers\Console\AdminOrderController;
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
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('admin.orders.index');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('admin.orders.show');
    Route::get('/orders/{order}/payment-proof', [AdminOrderController::class, 'proof'])->name('admin.orders.proof');
    Route::post('/orders/{order}/payment-review', [AdminOrderController::class, 'reviewPayment'])
        ->name('admin.orders.payment-review');
    Route::put('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])
        ->name('admin.orders.status');
    Route::get('/categories', [AdminCategoryController::class, 'index'])->name('admin.categories.index');
    Route::get('/categories/create', [AdminCategoryController::class, 'create'])->name('admin.categories.create');
    Route::post('/categories', [AdminCategoryController::class, 'store'])->name('admin.categories.store');
    Route::get('/categories/{category}/edit', [AdminCategoryController::class, 'edit'])->name('admin.categories.edit');
    Route::put('/categories/{category}', [AdminCategoryController::class, 'update'])->name('admin.categories.update');
    Route::patch('/categories/{category}/toggle', [AdminCategoryController::class, 'toggle'])
        ->name('admin.categories.toggle');
    Route::get('/settings/bank-transfer', [AdminBankTransferSettingsController::class, 'edit'])
        ->name('admin.settings.bank-transfer.edit');
    Route::put('/settings/bank-transfer', [AdminBankTransferSettingsController::class, 'update'])
        ->name('admin.settings.bank-transfer.update');
    Route::get('/products', [AdminProductController::class, 'index'])->name('admin.products.index');
    Route::get('/products/create', [AdminProductController::class, 'create'])->name('admin.products.create');
    Route::post('/products', [AdminProductController::class, 'store'])->name('admin.products.store');
    Route::get('/products/{product}/edit', [AdminProductController::class, 'edit'])->name('admin.products.edit');
    Route::put('/products/{product}', [AdminProductController::class, 'update'])->name('admin.products.update');
    Route::delete('/products/{product}', [AdminProductController::class, 'destroy'])->name('admin.products.destroy');
});
