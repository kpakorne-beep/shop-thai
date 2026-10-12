<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_find_order_with_order_number_and_checkout_email()
    {
        $order = $this->createOrder();

        $this->get(route('orders.track'))
            ->assertOk()
            ->assertSee('Track your order');

        $this->post(route('orders.track.lookup'), [
            'order_number' => $order->order_number,
            'customer_email' => 'CUSTOMER@example.test',
        ])
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Pending payment')
            ->assertSee('Awaiting transfer')
            ->assertSee('TRACK-123');
    }

    public function test_order_tracking_does_not_reveal_data_when_email_or_order_number_is_wrong()
    {
        $order = $this->createOrder();

        $this->post(route('orders.track.lookup'), [
            'order_number' => $order->order_number,
            'customer_email' => 'wrong@example.test',
        ])
            ->assertSessionHasErrors('order')
            ->assertDontSee($order->customer_name);

        $this->post(route('orders.track.lookup'), [
            'order_number' => 'ES-NOT-REAL',
            'customer_email' => $order->customer_email,
        ])
            ->assertSessionHasErrors('order')
            ->assertDontSee($order->customer_name);
    }

    private function createOrder(): Order
    {
        $order = Order::create([
            'order_number' => 'ES-'.strtoupper(bin2hex(random_bytes(10))),
            'status' => Order::STATUS_PENDING_PAYMENT,
            'customer_name' => 'Private Customer Name',
            'customer_email' => 'customer@example.test',
            'customer_phone' => '0812345678',
            'shipping_address' => 'Private address',
            'shipping_subdistrict' => 'Example Subdistrict',
            'shipping_district' => 'Example District',
            'shipping_province' => 'Bangkok',
            'shipping_postal_code' => '10100',
            'subtotal' => 100,
            'shipping_fee' => 0,
            'total' => 100,
            'tracking_number' => 'TRACK-123',
        ]);

        $order->payment()->create([
            'amount' => 100,
            'status' => Payment::STATUS_AWAITING_TRANSFER,
        ]);

        return $order;
    }
}
