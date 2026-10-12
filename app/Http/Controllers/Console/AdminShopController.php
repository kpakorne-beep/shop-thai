<?php

namespace App\Http\Controllers\Console;

use App\Http\Controllers\Controller;

class AdminShopController extends Controller
{
    public function getIndex()
    {
        $data = [];

        return $this->view('pages.admin.admin_shop.index', $data);
    }
}