<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Services\ShoppingCart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    private const SHIPPING_FEE = 0;

    public function create(Request $request, ShoppingCart $shoppingCart)
    {
        $items = $shoppingCart->items($request->session()->get('cart', []));

        if ($items->isEmpty()) {
            return redirect()->route('cart.index')->withErrors(['cart' => 'Add a product to your cart before checkout.']);
        }

        if ($items->contains(function ($item) {
            return ! $item['available'];
        })) {
            return redirect()->route('cart.index')->withErrors(['cart' => 'Review unavailable products in your cart before checkout.']);
        }

        $subtotal = $shoppingCart->subtotal($items);
        $shippingFee = self::SHIPPING_FEE;
        $total = $subtotal + $shippingFee;

        return view('pages.home.checkout.create', compact('items', 'subtotal', 'shippingFee', 'total'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'shipping_address' => ['required', 'string', 'max:2000'],
            'shipping_subdistrict' => ['required', 'string', 'max:255'],
            'shipping_district' => ['required', 'string', 'max:255'],
            'shipping_province' => ['required', 'string', 'max:255'],
            'shipping_postal_code' => ['required', 'string', 'max:20'],
        ]);

        $cart = $request->session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index')->withErrors(['cart' => 'Your cart is empty.']);
        }

        $order = DB::transaction(function () use ($cart, $validated, $request) {
            $productIds = array_map('intval', array_keys($cart));
            sort($productIds);
            $products = Product::whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $subtotalCents = 0;
            foreach ($cart as $productId => $quantity) {
                $product = $products->get((int) $productId);
                if (! $product || ! $product->is_active || $quantity < 1 || $product->stock < $quantity) {
                    throw ValidationException::withMessages([
                        'cart' => 'A product is no longer available in the requested quantity. Please review your cart.',
                    ]);
                }

                $subtotalCents += (int) round((float) $product->price * 100) * (int) $quantity;
            }

            $subtotal = $subtotalCents / 100;
            $shippingFee = self::SHIPPING_FEE;
            $total = $subtotal + $shippingFee;
            $order = Order::create([
                'order_number' => $this->newOrderNumber(),
                'user_id' => $request->user() ? $request->user()->id : null,
                'status' => Order::STATUS_PENDING_PAYMENT,
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
                'customer_phone' => $validated['customer_phone'],
                'shipping_address' => $validated['shipping_address'],
                'shipping_subdistrict' => $validated['shipping_subdistrict'],
                'shipping_district' => $validated['shipping_district'],
                'shipping_province' => $validated['shipping_province'],
                'shipping_postal_code' => $validated['shipping_postal_code'],
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'total' => $total,
            ]);

            foreach ($cart as $productId => $quantity) {
                $product = $products->get((int) $productId);
                $lineTotalCents = (int) round((float) $product->price * 100) * (int) $quantity;
                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'unit_price' => $product->price,
                    'quantity' => $quantity,
                    'line_total' => $lineTotalCents / 100,
                ]);
                $product->decrement('stock', (int) $quantity);
            }

            $order->payment()->create([
                'method' => 'bank_transfer',
                'status' => Payment::STATUS_AWAITING_TRANSFER,
                'amount' => $total,
            ]);

            return $order;
        });

        $request->session()->forget('cart');

        return redirect()->route('checkout.confirmation', $order->order_number);
    }

    public function confirmation(string $orderNumber)
    {
        $order = Order::with(['items', 'payment'])
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        return view('pages.home.checkout.confirmation', compact('order'));
    }

    private function newOrderNumber(): string
    {
        do {
            $orderNumber = 'ES-'.Str::upper(Str::random(20));
        } while (Order::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }
}
