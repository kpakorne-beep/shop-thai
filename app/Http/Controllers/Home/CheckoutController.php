<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ShopSetting;
use App\Services\OrderNotificationService;
use App\Services\ShoppingCart;
use App\Support\Money;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    private const SHIPPING_FEE_CENTS = 0;

    public function create(Request $request, ShoppingCart $shoppingCart)
    {
        try {
            $cart = $this->normalizeCart($request->session()->get('cart', []));
        } catch (ValidationException $exception) {
            return redirect()->route('cart.index')->withErrors($exception->errors());
        }

        $items = $shoppingCart->items($cart);

        if ($items->isEmpty()) {
            return redirect()->route('cart.index')->withErrors(['cart' => 'Add a product to your cart before checkout.']);
        }

        if ($items->contains(function ($item) {
            return ! $item['available'];
        })) {
            return redirect()->route('cart.index')->withErrors(['cart' => 'Review unavailable products in your cart before checkout.']);
        }

        $checkoutToken = $request->session()->get('checkout_token');
        if (! $checkoutToken || Order::where('checkout_token', $checkoutToken)->exists()) {
            $checkoutToken = (string) Str::uuid();
            $request->session()->put('checkout_token', $checkoutToken);
        }

        $subtotalCents = Money::toCents($shoppingCart->subtotal($items));
        $subtotal = Money::fromCents($subtotalCents);
        $shippingFee = Money::fromCents(self::SHIPPING_FEE_CENTS);
        $total = Money::fromCents($subtotalCents + self::SHIPPING_FEE_CENTS);

        return $this->view('pages.home.checkout.create', compact('items', 'subtotal', 'shippingFee', 'total', 'checkoutToken'), 'Checkout');
    }

    public function store(Request $request, OrderNotificationService $notifications)
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
            'checkout_token' => ['required', 'uuid'],
        ]);

        $existingOrder = Order::where('checkout_token', $validated['checkout_token'])->first();
        if ($existingOrder) {
            return redirect()->route('checkout.confirmation', $existingOrder->order_number);
        }

        if (! hash_equals((string) $request->session()->get('checkout_token'), $validated['checkout_token'])) {
            throw ValidationException::withMessages([
                'checkout_token' => 'This checkout session has expired. Please review your cart and try again.',
            ]);
        }

        $cart = $request->session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index')->withErrors(['cart' => 'Your cart is empty.']);
        }

        $cart = $this->normalizeCart($cart);

        try {
            $order = DB::transaction(function () use ($cart, $validated, $request) {
                $productIds = array_keys($cart);
                sort($productIds);
                $products = Product::whereIn('id', $productIds)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $subtotalCents = 0;
                foreach ($cart as $productId => $quantity) {
                    $product = $products->get($productId);
                    if (! $product || ! $product->is_active || $product->stock < $quantity) {
                        throw ValidationException::withMessages([
                            'cart' => 'A product is no longer available in the requested quantity. Please review your cart.',
                        ]);
                    }

                    $subtotalCents += Money::toCents($product->price) * $quantity;
                    if ($subtotalCents > 999999999999) {
                        throw ValidationException::withMessages([
                            'cart' => 'The order total exceeds the supported amount.',
                        ]);
                    }
                }

                $totalCents = $subtotalCents + self::SHIPPING_FEE_CENTS;
                $order = Order::create([
                    'order_number' => $this->newOrderNumber(),
                    'checkout_token' => $validated['checkout_token'],
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
                    'subtotal' => Money::fromCents($subtotalCents),
                    'shipping_fee' => Money::fromCents(self::SHIPPING_FEE_CENTS),
                    'total' => Money::fromCents($totalCents),
                ]);

                foreach ($cart as $productId => $quantity) {
                    $product = $products->get($productId);
                    $lineTotalCents = Money::toCents($product->price) * $quantity;
                    $order->items()->create([
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'sku' => $product->sku,
                        'unit_price' => $product->price,
                        'quantity' => $quantity,
                        'line_total' => Money::fromCents($lineTotalCents),
                    ]);
                    $product->decrement('stock', $quantity);
                }

                $order->payment()->create([
                    'method' => 'bank_transfer',
                    'status' => Payment::STATUS_AWAITING_TRANSFER,
                    'amount' => Money::fromCents($totalCents),
                ]);

                return $order;
            });
        } catch (QueryException $exception) {
            $existingOrder = Order::where('checkout_token', $validated['checkout_token'])->first();
            if (! $existingOrder) {
                throw $exception;
            }

            return redirect()->route('checkout.confirmation', $existingOrder->order_number);
        }

        $request->session()->forget('cart');
        $request->session()->forget('checkout_token');
        $notifications->send($order, 'We received your order.');

        return redirect()->route('checkout.confirmation', $order->order_number);
    }

    public function confirmation(string $orderNumber)
    {
        $order = Order::with(['items', 'payment'])
            ->where('order_number', $orderNumber)
            ->firstOrFail();
        $bankTransferDetails = ShopSetting::whereIn('key', [
            'bank_name',
            'account_name',
            'account_number',
            'instructions',
        ])->pluck('value', 'key');

        return $this->view('pages.home.checkout.confirmation', compact('order', 'bankTransferDetails'), 'Order '.$order->order_number);
    }

    public function submitPaymentProof(Request $request, string $orderNumber, OrderNotificationService $notifications)
    {
        $validated = $request->validate([
            'customer_email' => ['required', 'email', 'max:255'],
            'transaction_reference' => ['nullable', 'string', 'max:255'],
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        $order = Order::with('payment')->where('order_number', $orderNumber)->firstOrFail();
        if (! hash_equals(strtolower($order->customer_email), strtolower($validated['customer_email']))) {
            abort(404);
        }

        if (! $order->payment || ! in_array($order->payment->status, [
            Payment::STATUS_AWAITING_TRANSFER,
            Payment::STATUS_REJECTED,
        ], true)) {
            return back()->withErrors(['proof' => 'This order is not currently accepting payment proof.']);
        }

        $proofPath = $request->file('proof')->store('payment-proofs', 'local');
        $previousProofPath = $order->payment->proof_path;

        try {
            DB::transaction(function () use ($orderNumber, $validated, $proofPath) {
                $lockedOrder = Order::where('order_number', $orderNumber)->lockForUpdate()->firstOrFail();
                $payment = $lockedOrder->payment()->lockForUpdate()->firstOrFail();

                if (! in_array($payment->status, [
                    Payment::STATUS_AWAITING_TRANSFER,
                    Payment::STATUS_REJECTED,
                ], true)) {
                    throw ValidationException::withMessages([
                        'proof' => 'This order is not currently accepting payment proof.',
                    ]);
                }

                $payment->update([
                    'status' => Payment::STATUS_PENDING_REVIEW,
                    'transaction_reference' => $validated['transaction_reference'] ?? null,
                    'proof_path' => $proofPath,
                    'transferred_at' => now(),
                    'reviewed_by' => null,
                    'review_note' => null,
                ]);
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($proofPath);
            throw $exception;
        }

        if ($previousProofPath) {
            Storage::disk('local')->delete($previousProofPath);
        }

        $notifications->send($order->fresh(), 'We received your bank transfer proof and will review it shortly.');

        return redirect()
            ->route('checkout.confirmation', $orderNumber)
            ->with('status', 'Payment proof submitted for review.');
    }

    private function newOrderNumber(): string
    {
        do {
            $orderNumber = 'ES-'.Str::upper(Str::random(20));
        } while (Order::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    private function normalizeCart($cart): array
    {
        if (! is_array($cart)) {
            throw ValidationException::withMessages([
                'cart' => 'Your cart data is invalid. Please review your cart.',
            ]);
        }

        $normalized = [];
        foreach ($cart as $productId => $quantity) {
            $productId = (string) $productId;
            $validQuantity = filter_var($quantity, FILTER_VALIDATE_INT);
            if (! ctype_digit($productId)
                || (int) $productId < 1
                || $validQuantity === false
                || $validQuantity < 1
                || $validQuantity > 100
                || array_key_exists((int) $productId, $normalized)) {
                throw ValidationException::withMessages([
                    'cart' => 'Your cart contains invalid product data. Please review your cart.',
                ]);
            }

            $normalized[(int) $productId] = $validQuantity;
        }

        return $normalized;
    }
}
