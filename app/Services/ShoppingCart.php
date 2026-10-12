<?php

namespace App\Services;

use App\Models\Product;
use App\Support\Money;
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
                'line_total' => $product
                    ? Money::fromCents(Money::toCents($product->price) * (int) $quantity)
                    : '0.00',
            ];
        });
    }

    public function subtotal(Collection $items): string
    {
        $subtotalCents = $items
            ->where('available', true)
            ->sum(function ($item) {
                return Money::toCents($item['line_total']);
            });

        return Money::fromCents($subtotalCents);
    }
}
