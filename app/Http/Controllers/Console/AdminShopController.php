<?php namespace App\Http\Controllers\Console;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AdminShopController extends Controller {

    public function getIndex()
    {
        sd('666');
        $data = [];
        return $this->view('pages.home.admin_shop.index',$data);
    }
}