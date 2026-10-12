<?php

namespace Tests\Feature;

use App\Mail\OrderUpdateMail;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_add_to_cart_and_place_a_bank_transfer_order()
    {
        Mail::fake();

        $product = Product::create([
            'name' => 'Canvas Bag',
            'slug' => 'canvas-bag',
            'sku' => 'BAG-001',
            'price' => '25.50',
            'stock' => 5,
        ]);

        $this->post(route('cart.products.store', $product), ['quantity' => 2])
            ->assertRedirect(route('cart.index'));

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Canvas Bag')
            ->assertSee('51.00');

        $this->get(route('checkout.create'))
            ->assertOk()
            ->assertSee('฿51.00')
            ->assertSee('name="checkout_token"', false)
            ->assertSee('Shipping')
            ->assertSee('฿0.00');

        $checkoutToken = $this->app['session.store']->get('checkout_token');
        $this->post(route('checkout.store'), $this->shippingDetails() + ['checkout_token' => $checkoutToken])
            ->assertRedirect();

        $order = Order::with(['items', 'payment'])->firstOrFail();
        Mail::assertSent(OrderUpdateMail::class, function (OrderUpdateMail $mail) use ($order) {
            return $mail->hasTo($order->customer_email)
                && $mail->order->is($order)
                && $mail->updateMessage === 'We received your order.';
        });
        $emailHtml = view('emails.orders.update', [
            'order' => $order,
            'updateMessage' => 'We received your order.',
        ])->render();
        $this->assertStringContainsString($order->order_number, $emailHtml);
        $this->assertStringNotContainsString($order->shipping_address, $emailHtml);
        $this->assertNull($order->user_id);
        $this->assertSame(Order::STATUS_PENDING_PAYMENT, $order->status);
        $this->assertSame('51.00', $order->subtotal);
        $this->assertSame('0.00', $order->shipping_fee);
        $this->assertSame('51.00', $order->total);
        $this->assertSame('Canvas Bag', $order->items->first()->product_name);
        $this->assertSame('25.50', $order->items->first()->unit_price);
        $this->assertSame(2, $order->items->first()->quantity);
        $this->assertSame('51.00', $order->payment->amount);
        $this->assertSame(Payment::STATUS_AWAITING_TRANSFER, $order->payment->status);
        $this->assertSame(3, $product->fresh()->stock);
        $this->assertNull(session('cart'));

        $this->get(route('checkout.confirmation', $order->order_number))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Order received');
    }

    public function test_checkout_rejects_insufficient_stock_without_creating_an_order()
    {
        $product = Product::create([
            'name' => 'Canvas Bag',
            'slug' => 'canvas-bag',
            'sku' => 'BAG-001',
            'price' => '25.50',
            'stock' => 1,
        ]);

        $this->withSession([
            'cart' => [$product->id => 2],
            'checkout_token' => (string) Str::uuid(),
        ])
            ->from(route('checkout.create'))
            ->post(route('checkout.store'), $this->shippingDetails() + [
                'checkout_token' => $this->app['session.store']->get('checkout_token'),
            ])
            ->assertRedirect(route('checkout.create'))
            ->assertSessionHasErrors('cart');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(1, $product->fresh()->stock);
    }

    public function test_checkout_does_not_trust_stale_cart_prices()
    {
        $product = Product::create([
            'name' => 'Canvas Bag',
            'slug' => 'canvas-bag',
            'sku' => 'BAG-001',
            'price' => '25.50',
            'stock' => 5,
        ]);

        $token = (string) Str::uuid();
        $this->withSession(['cart' => [$product->id => 1], 'checkout_token' => $token])
            ->post(route('checkout.store'), $this->shippingDetails() + [
                'total' => '0.01',
                'checkout_token' => $token,
            ])
            ->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertSame('25.50', $order->total);
    }

    public function test_reposting_a_checkout_request_returns_the_existing_order_without_charging_stock_twice()
    {
        Mail::fake();
        $product = Product::create([
            'name' => 'Canvas Bag',
            'slug' => 'canvas-bag',
            'sku' => 'BAG-001',
            'price' => '25.50',
            'stock' => 5,
        ]);
        $token = (string) Str::uuid();
        $payload = $this->shippingDetails() + ['checkout_token' => $token];

        $this->withSession([
            'cart' => [$product->id => 2],
            'checkout_token' => $token,
        ])->post(route('checkout.store'), $payload);

        $order = Order::firstOrFail();
        $this->post(route('checkout.store'), $payload)
            ->assertRedirect(route('checkout.confirmation', $order->order_number));

        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(3, $product->fresh()->stock);
        Mail::assertSent(OrderUpdateMail::class, 1);
    }

    public function test_checkout_rejects_malformed_cart_data()
    {
        $product = Product::create([
            'name' => 'Canvas Bag',
            'slug' => 'canvas-bag',
            'sku' => 'BAG-001',
            'price' => '25.50',
            'stock' => 5,
        ]);
        $token = (string) Str::uuid();

        $this->withSession([
            'cart' => [$product->id => 101],
            'checkout_token' => $token,
        ])->from(route('checkout.create'))
            ->post(route('checkout.store'), $this->shippingDetails() + ['checkout_token' => $token])
            ->assertRedirect(route('checkout.create'))
            ->assertSessionHasErrors('cart');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_checkout_sums_decimal_prices_using_integer_cents()
    {
        $firstProduct = Product::create([
            'name' => 'Sticker',
            'slug' => 'sticker',
            'sku' => 'STICKER-001',
            'price' => '0.10',
            'stock' => 5,
        ]);
        $secondProduct = Product::create([
            'name' => 'Postcard',
            'slug' => 'postcard',
            'sku' => 'POSTCARD-001',
            'price' => '0.20',
            'stock' => 5,
        ]);
        $token = (string) Str::uuid();

        $this->withSession([
            'cart' => [$firstProduct->id => 3, $secondProduct->id => 4],
            'checkout_token' => $token,
        ])->post(route('checkout.store'), $this->shippingDetails() + ['checkout_token' => $token])
            ->assertRedirect();

        $order = Order::with('payment')->firstOrFail();
        $this->assertSame('1.10', $order->total);
        $this->assertSame('1.10', $order->payment->amount);
        $this->assertSame('0.30', $order->items->first()->line_total);
        $this->assertSame('0.80', $order->items->last()->line_total);
    }

    public function test_checkout_rejects_totals_that_exceed_the_order_amount_limit()
    {
        $product = Product::create([
            'name' => 'Premium Item',
            'slug' => 'premium-item',
            'sku' => 'PREMIUM-001',
            'price' => '9999999999.99',
            'stock' => 2,
        ]);
        $token = (string) Str::uuid();

        $this->withSession([
            'cart' => [$product->id => 2],
            'checkout_token' => $token,
        ])->from(route('checkout.create'))
            ->post(route('checkout.store'), $this->shippingDetails() + ['checkout_token' => $token])
            ->assertRedirect(route('checkout.create'))
            ->assertSessionHasErrors('cart');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(2, $product->fresh()->stock);
    }

    private function shippingDetails(): array
    {
        return [
            'customer_name' => 'Example Customer',
            'customer_email' => 'customer@example.test',
            'customer_phone' => '0812345678',
            'shipping_address' => '123 Example Road',
            'shipping_subdistrict' => 'Example Subdistrict',
            'shipping_district' => 'Example District',
            'shipping_province' => 'Bangkok',
            'shipping_postal_code' => '10100',
        ];
    }
}
