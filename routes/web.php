<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', 'Home\\ExampleShopController@index')->name('shop.index');
Route::get('/products/{slug}', 'Home\\ExampleShopController@show')->name('shop.products.show');
Route::get('/cart', 'Home\\CartController@index')->name('cart.index');
Route::post('/cart/products/{product}', 'Home\\CartController@store')->name('cart.products.store');
Route::put('/cart', 'Home\\CartController@update')->name('cart.update');
Route::delete('/cart/products/{productId}', 'Home\\CartController@destroy')->name('cart.products.destroy');
Route::get('/checkout', 'Home\\CheckoutController@create')->name('checkout.create');
Route::post('/checkout', 'Home\\CheckoutController@store')->name('checkout.store');
Route::get('/orders/{orderNumber}/confirmation', 'Home\\CheckoutController@confirmation')
    ->name('checkout.confirmation');
Route::get('/order-status', 'Home\\OrderTrackingController@create')->name('orders.track');
Route::post('/order-status', 'Home\\OrderTrackingController@lookup')
    ->middleware('throttle:10,1')
    ->name('orders.track.lookup');
Route::post('/orders/{orderNumber}/payment-proof', 'Home\\CheckoutController@submitPaymentProof')
    ->middleware('throttle:10,1')
    ->name('checkout.payment-proof');