<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SharedPageLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_pages_load_the_shared_stylesheet_and_navigation()
    {
        $product = Product::create([
            'name' => 'Canvas Bag',
            'slug' => 'canvas-bag',
            'sku' => 'BAG-001',
            'price' => 25,
            'stock' => 3,
        ]);
        $order = $this->createOrder($product);

        $responses = [
            $this->get(route('shop.index')),
            $this->get(route('shop.products.show', $product->slug)),
            $this->get(route('cart.index')),
            $this->withSession(['cart' => [$product->id => 1]])
                ->get(route('checkout.create')),
            $this->get(route('checkout.confirmation', $order->order_number)),
            $this->get(route('orders.track')),
        ];

        foreach ($responses as $response) {
            $response->assertOk()
                ->assertSee('css/app.css')
                ->assertSee('site-navbar');
        }
    }

    public function test_console_pages_load_the_shared_stylesheet_and_login_uses_console_layout()
    {
        $category = Category::create([
            'name' => 'Accessories',
            'slug' => 'accessories',
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Canvas Bag',
            'slug' => 'canvas-bag',
            'sku' => 'BAG-001',
            'price' => 25,
            'stock' => 3,
        ]);
        $order = $this->createOrder($product);
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($admin);

        $responses = [
            $this->get(route('admin.admin_shop.index.get')),
            $this->get(route('admin.products.index')),
            $this->get(route('admin.products.create')),
            $this->get(route('admin.products.edit', $product)),
            $this->get(route('admin.categories.index')),
            $this->get(route('admin.categories.create')),
            $this->get(route('admin.categories.edit', $category)),
            $this->get(route('admin.orders.index')),
            $this->get(route('admin.orders.show', $order)),
            $this->get(route('admin.settings.bank-transfer.edit')),
        ];

        foreach ($responses as $response) {
            $response->assertOk()
                ->assertSee('css/app.css')
                ->assertSee('site-navbar');
        }

        auth()->logout();

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('css/app.css')
            ->assertSee('admin-login-layout')
            ->assertDontSee('site-navbar');
    }

    private function createOrder(Product $product): Order
    {
        $order = Order::create([
            'order_number' => 'ES-'.strtoupper(bin2hex(random_bytes(10))),
            'status' => Order::STATUS_PENDING_PAYMENT,
            'customer_name' => 'Example Customer',
            'customer_email' => 'customer@example.test',
            'customer_phone' => '0812345678',
            'shipping_address' => '123 Example Road',
            'shipping_subdistrict' => 'Example Subdistrict',
            'shipping_district' => 'Example District',
            'shipping_province' => 'Bangkok',
            'shipping_postal_code' => '10100',
            'subtotal' => 25,
            'shipping_fee' => 0,
            'total' => 25,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'unit_price' => $product->price,
            'quantity' => 1,
            'line_total' => 25,
        ]);
        $order->payment()->create([
            'amount' => 25,
            'status' => Payment::STATUS_AWAITING_TRANSFER,
        ]);

        return $order;
    }
}
