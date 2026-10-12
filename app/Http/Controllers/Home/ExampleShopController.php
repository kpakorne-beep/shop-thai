<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ExampleShopController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:255'],
        ]);
        $search = $filters['search'] ?? null;
        $categorySlug = $filters['category'] ?? null;
        $queryString = array_filter($filters, function ($value) {
            return $value !== null && $value !== '';
        });

        $products = Product::query()
            ->with('category')
            ->where('is_active', true)
            ->when($search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%');
                });
            })
            ->when($categorySlug, function ($query, $categorySlug) {
                $query->whereHas('category', function ($query) use ($categorySlug) {
                    $query->where('slug', $categorySlug)
                        ->where('is_active', true);
                });
            })
            ->latest()
            ->paginate(12)
            ->appends($queryString);

        $categories = Category::where('is_active', true)->orderBy('name')->get();

        return $this->view('pages.home.example_shop.index', compact('products', 'categories', 'search', 'categorySlug'), 'Shop');
    }

    public function show(string $slug)
    {
        $product = Product::with('category')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return $this->view('pages.home.example_shop.show', compact('product'), $product->name);
    }

    public function getIndex(Request $request)
    {
        return $this->index($request);
    }
}
