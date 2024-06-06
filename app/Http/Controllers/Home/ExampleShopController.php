<?php namespace App\Http\Controllers\Home;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ExampleShopController extends Controller {

    public function getIndex()
    {
        sd('777');
        $data = [];
        return $this->view('pages.home.example_shop.index');
    }
}
