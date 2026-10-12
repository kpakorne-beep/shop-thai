<?php

namespace App\Http\Controllers\Console;

use App\Http\Controllers\Controller;
use App\Models\Product;

class AdminShopController extends Controller
{
    public function getIndex()
    {
        $productCount = Product::count();
        $lowStockCount = Product::where('stock', '<=', 5)->count();

        return view('pages.admin.admin_shop.index', compact('productCount', 'lowStockCount'));
    }
}