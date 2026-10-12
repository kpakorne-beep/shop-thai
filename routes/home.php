<?php

use App\Http\Controllers\Home\ExampleShopController;
use Illuminate\Support\Facades\Route;

Route::get('/index', [ExampleShopController::class, 'getIndex'])
    ->name('home.example_shop.index.get');