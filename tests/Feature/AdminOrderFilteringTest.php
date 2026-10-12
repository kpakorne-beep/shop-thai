<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderFilteringTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_search_by_order_number_or_customer_email()
    {
        $this->actingAs($this->createAdmin());
        $matchingOrder = $this->createOrder('ES-SEARCH-MATCH', 'buyer@example.test');
        $this->createOrder('ES-OTHER-ORDER', 'other@example.test');

        $this->get(route('admin.orders.index', ['search' => 'SEARCH-MATCH']))
            ->assertOk()
            ->assertSee($matchingOrder->order_number)
            ->assertDontSee('ES-OTHER-ORDER');

        $this->get(route('admin.orders.index', ['search' => 'buyer@example.test']))
            ->assertOk()
            ->assertSee($matchingOrder->order_number)
            ->assertDontSee('ES-OTHER-ORDER');
    }

    public function test_admin_can_combine_order_and_payment_status_filters()
    {
        $this->actingAs($this->createAdmin());
        $matchingOrder = $this->createOrder(
            'ES-FILTER-MATCH',
            'buyer@example.test',
            Order::STATUS_PENDING_PAYMENT,
            Payment::STATUS_PENDING_REVIEW
        );
        $this->createOrder(
            'ES-FILTER-WRONG-PAYMENT',
            'buyer@example.test',
            Order::STATUS_PENDING_PAYMENT,
            Payment::STATUS_AWAITING_TRANSFER
        );
        $this->createOrder(
            'ES-FILTER-WRONG-ORDER',
            'buyer@example.test',
            Order::STATUS_PROCESSING,
            Payment::STATUS_PENDING_REVIEW
        );

        $this->get(route('admin.orders.index', [
            'status' => Order::STATUS_PENDING_PAYMENT,
            'payment_status' => Payment::STATUS_PENDING_REVIEW,
        ]))
            ->assertOk()
            ->assertSee($matchingOrder->order_number)
            ->assertDontSee('ES-FILTER-WRONG-PAYMENT')
            ->assertDontSee('ES-FILTER-WRONG-ORDER');
    }

    public function test_admin_order_search_pagination_preserves_active_filters()
    {
        $this->actingAs($this->createAdmin());

        for ($index = 1; $index <= 21; $index++) {
            $this->createOrder(
                'ES-PAGE-'.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                'paging@example.test',
                Order::STATUS_PROCESSING,
                Payment::STATUS_CONFIRMED
            );
        }

        $response = $this->get(route('admin.orders.index', [
            'search' => 'paging@example.test',
            'status' => Order::STATUS_PROCESSING,
            'payment_status' => Payment::STATUS_CONFIRMED,
        ]));

        $response        ->assertOk()
        ->assertSee('ES-PAGE-21')
        ->assertDontSee('ES-PAGE-01')
        ->assertSee('search=paging%40example.test&amp;status=processing&amp;payment_status=confirmed&amp;page=2', false);
    }

    public function test_invalid_order_filter_values_are_rejected()
    {
        $this->actingAs($this->createAdmin())
            ->get(route('admin.orders.index', ['status' => 'unknown']))
            ->assertRedirect()
            ->assertSessionHasErrors('status');
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        return $admin;
    }

    private function createOrder(
        string $orderNumber,
        string $email,
        string $orderStatus = Order::STATUS_PENDING_PAYMENT,
        string $paymentStatus = Payment::STATUS_AWAITING_TRANSFER
    ): Order {
        $order = Order::create([
            'order_number' => $orderNumber,
            'status' => $orderStatus,
            'customer_name' => 'Test Customer',
            'customer_email' => $email,
            'customer_phone' => '0812345678',
            'shipping_address' => '123 Example Road',
            'shipping_subdistrict' => 'Example Subdistrict',
            'shipping_district' => 'Example District',
            'shipping_province' => 'Bangkok',
            'shipping_postal_code' => '10100',
            'subtotal' => 100,
            'shipping_fee' => 0,
            'total' => 100,
        ]);
        $order->payment()->create([
            'amount' => 100,
            'status' => $paymentStatus,
        ]);

        return $order;
    }
}
