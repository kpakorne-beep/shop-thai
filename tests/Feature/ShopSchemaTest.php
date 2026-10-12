<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_shop_tables_are_created()
    {
        $this->assertDatabaseCount('categories', 0);
        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_guest_order_keeps_product_snapshot_and_payment_relationship()
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
            'price' => 250,
            'stock' => 10,
        ]);

        $order = Order::create([
            'order_number' => 'ORD-20261012-0001',
            'customer_name' => 'Guest Customer',
            'customer_email' => 'guest@example.test',
            'customer_phone' => '0812345678',
            'shipping_address' => '123 Example Road',
            'shipping_subdistrict' => 'Example Subdistrict',
            'shipping_district' => 'Example District',
            'shipping_province' => 'Bangkok',
            'shipping_postal_code' => '10100',
            'subtotal' => 500,
            'shipping_fee' => 50,
            'total' => 550,
        ]);

        $item = $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'unit_price' => $product->price,
            'quantity' => 2,
            'line_total' => 500,
        ]);

        $payment = $order->payment()->create([
            'amount' => $order->total,
        ]);
        $payment->refresh();

        $this->assertNull($order->user_id);
        $this->assertSame('Canvas Bag', $item->product_name);
        $this->assertSame('250.00', $item->unit_price);
        $this->assertSame('awaiting_transfer', $payment->status);
        $this->assertTrue($order->items->contains($item));
        $this->assertTrue($product->category->is($category));
        $this->assertTrue($payment->order->is($order));
    }
}
