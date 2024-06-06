<?php

// HOME
Route::get('/index',[
    'uses' => 'ExampleShopController@getIndex',
    'as'   => 'home.example_shop.index.get'
]);