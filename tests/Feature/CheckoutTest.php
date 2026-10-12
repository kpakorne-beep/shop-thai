<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_add_to_cart_and_place_a_bank_transfer_order()
    {
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
            ->assertSee('Shipping')
            ->assertSee('฿0.00');

        $this->post(route('checkout.store'), $this->shippingDetails())
            ->assertRedirect();

        $order = Order::with(['items', 'payment'])->firstOrFail();
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

        $this->withSession(['cart' => [$product->id => 2]])
            ->from(route('checkout.create'))
            ->post(route('checkout.store'), $this->shippingDetails())
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

        $this->withSession(['cart' => [$product->id => 1]])
            ->post(route('checkout.store'), $this->shippingDetails() + ['total' => '0.01'])
            ->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertSame('25.50', $order->total);
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
