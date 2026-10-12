<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;

class ExampleShopController extends Controller
{
    public function getIndex()
    {
        $data = [];
        return $this->view('pages.home.example_shop.index', $data);
    }
}
