<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

class ShoppingCart
{
    public function items(array $cart): Collection
    {
        $products = Product::whereIn('id', array_keys($cart))->get()->keyBy('id');

        return collect($cart)->map(function ($quantity, $productId) use ($products) {
            $product = $products->get($productId);
            $available = $product && $product->is_active && $product->stock >= $quantity;

            return [
                'product' => $product,
                'quantity' => (int) $quantity,
                'available' => $available,
                'line_total' => $product ? (float) $product->price * (int) $quantity : 0,
            ];
        });
    }

    public function subtotal(Collection $items): float
    {
        return (float) $items
            ->where('available', true)
            ->sum('line_total');
    }
}
