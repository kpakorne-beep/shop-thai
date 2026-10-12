<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ShoppingCart;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request, ShoppingCart $shoppingCart)
    {
        $items = $shoppingCart->items($request->session()->get('cart', []));
        $subtotal = $shoppingCart->subtotal($items);
        $hasUnavailableItems = $items->contains(function ($item) {
            return ! $item['available'];
        });

        return $this->view('pages.home.cart.index', compact('items', 'subtotal', 'hasUnavailableItems'));
    }

    public function store(Request $request, Product $product)
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        if (! $product->is_active || $product->stock < 1) {
            return back()->withErrors(['cart' => 'This product is currently unavailable.']);
        }

        $cart = $request->session()->get('cart', []);
        $quantity = ($cart[$product->id] ?? 0) + (int) $validated['quantity'];
        if ($quantity > $product->stock) {
            return back()->withErrors(['cart' => 'The requested quantity exceeds available stock.']);
        }

        $cart[$product->id] = $quantity;
        $request->session()->put('cart', $cart);

        return redirect()->route('cart.index')->with('status', 'Product added to cart.');
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'quantities' => ['required', 'array', 'min:1'],
            'quantities.*' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $cart = $request->session()->get('cart', []);
        foreach ($validated['quantities'] as $productId => $quantity) {
            if (! ctype_digit((string) $productId) || ! array_key_exists($productId, $cart)) {
                return back()->withErrors(['cart' => 'The cart contains an invalid product.']);
            }

            $product = Product::find($productId);
            if (! $product || ! $product->is_active || $quantity > $product->stock) {
                return back()->withErrors(['cart' => 'One or more requested quantities are unavailable.']);
            }
        }

        foreach ($validated['quantities'] as $productId => $quantity) {
            $cart[$productId] = (int) $quantity;
        }

        $request->session()->put('cart', $cart);

        return redirect()->route('cart.index')->with('status', 'Cart updated.');
    }

    public function destroy(Request $request, int $productId)
    {
        $cart = $request->session()->get('cart', []);
        unset($cart[$productId]);
        $request->session()->put('cart', $cart);

        return redirect()->route('cart.index')->with('status', 'Product removed from cart.');
    }
}
