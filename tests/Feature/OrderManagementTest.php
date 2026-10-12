<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Mail\OrderUpdateMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_submit_proof_using_the_order_email_and_admin_can_reject_it()
    {
        Mail::fake();
        Storage::fake('local');
        $product = $this->createProduct(4);
        $order = $this->createOrder($product, 1);

        $this->post(route('checkout.payment-proof', $order->order_number), [
            'customer_email' => 'someone-else@example.test',
            'proof' => $this->proofFile(),
        ])->assertNotFound();

        $this->post(route('checkout.payment-proof', $order->order_number), [
            'customer_email' => $order->customer_email,
            'transaction_reference' => 'TRANSFER-123',
            'proof' => $this->proofFile(),
        ])->assertRedirect(route('checkout.confirmation', $order->order_number));
        Mail::assertSent(OrderUpdateMail::class, function (OrderUpdateMail $mail) use ($order) {
            return $mail->hasTo($order->customer_email)
                && $mail->updateMessage === 'We received your bank transfer proof and will review it shortly.';
        });

        $order->load('payment');
        $proofPath = $order->payment->proof_path;
        $this->assertSame(Payment::STATUS_PENDING_REVIEW, $order->payment->status);
        $this->assertSame('TRANSFER-123', $order->payment->transaction_reference);
        Storage::disk('local')->assertExists($proofPath);
        Storage::disk('public')->assertMissing($proofPath);

        $admin = $this->createAdmin();
        $this->actingAs($admin)
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee($order->order_number);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('View transfer proof');

        $this->actingAs($admin)
            ->get(route('admin.orders.proof', $order))
            ->assertOk();

        $this->actingAs($admin)
            ->post(route('admin.orders.payment-review', $order), [
                'decision' => Payment::STATUS_REJECTED,
                'review_note' => 'The amount does not match.',
            ])
            ->assertRedirect(route('admin.orders.show', $order));
        Mail::assertSent(OrderUpdateMail::class, function (OrderUpdateMail $mail) use ($order) {
            return $mail->hasTo($order->customer_email)
                && $mail->updateMessage === 'Your payment proof was rejected. Please review the note and submit a new proof.';
        });

        $order->refresh()->load('payment');
        $this->assertSame(Payment::STATUS_REJECTED, $order->payment->status);
        $this->assertSame('The amount does not match.', $order->payment->review_note);
        $this->assertSame(Order::STATUS_PENDING_PAYMENT, $order->status);
        $this->assertSame(3, $product->fresh()->stock);
        Mail::assertSent(OrderUpdateMail::class, 2);

        $this->get(route('checkout.confirmation', $order->order_number))
            ->assertOk()
            ->assertSee('The amount does not match.')
            ->assertSee('Submit bank transfer proof');
    }

    public function test_admin_confirmation_advances_order_and_validates_delivery_transitions()
    {
        Mail::fake();
        $product = $this->createProduct(3);
        $order = $this->createOrder($product, 1);
        $admin = $this->createAdmin();
        $this->actingAs($admin);

        $this->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee($order->order_number);

        $this->post(route('admin.orders.payment-review', $order), [
            'decision' => Payment::STATUS_CONFIRMED,
        ])->assertSessionHasErrors('decision');

        $order->payment()->update([
            'status' => Payment::STATUS_PENDING_REVIEW,
            'proof_path' => 'payment-proofs/proof.png',
        ]);
        $this->post(route('admin.orders.payment-review', $order), [
            'decision' => Payment::STATUS_CONFIRMED,
        ])->assertRedirect(route('admin.orders.show', $order));
        Mail::assertSent(OrderUpdateMail::class, function (OrderUpdateMail $mail) use ($order) {
            return $mail->hasTo($order->customer_email)
                && $mail->updateMessage === 'Your payment has been confirmed.';
        });

        $this->assertSame(Order::STATUS_PROCESSING, $order->fresh()->status);
        $this->assertSame(Payment::STATUS_CONFIRMED, $order->payment->fresh()->status);

        $this->put(route('admin.orders.status', $order), [
            'status' => Order::STATUS_DELIVERED,
        ])->assertSessionHasErrors('status');

        $this->put(route('admin.orders.status', $order), [
            'status' => Order::STATUS_SHIPPED,
            'tracking_number' => 'TRACK-123',
        ])->assertRedirect(route('admin.orders.show', $order));
        Mail::assertSent(OrderUpdateMail::class, function (OrderUpdateMail $mail) use ($order) {
            return $mail->hasTo($order->customer_email)
                && $mail->updateMessage === 'Your order has been shipped.'
                && $mail->order->tracking_number === 'TRACK-123';
        });
        $order->refresh();
        $this->assertSame(Order::STATUS_SHIPPED, $order->status);
        $this->assertSame('TRACK-123', $order->tracking_number);
        $this->assertNotNull($order->shipped_at);

        $this->put(route('admin.orders.status', $order), [
            'status' => Order::STATUS_DELIVERED,
        ])->assertRedirect(route('admin.orders.show', $order));
        Mail::assertSent(OrderUpdateMail::class, function (OrderUpdateMail $mail) use ($order) {
            return $mail->hasTo($order->customer_email)
                && $mail->updateMessage === 'Your order has been marked as delivered.';
        });
        $order->refresh();
        $this->assertSame(Order::STATUS_DELIVERED, $order->status);
        $this->assertNotNull($order->delivered_at);
        Mail::assertSent(OrderUpdateMail::class, 3);
    }

    public function test_cancelling_an_unpaid_order_restores_stock_once()
    {
        Mail::fake();
        $product = $this->createProduct(5);
        $order = $this->createOrder($product, 3);
        $admin = $this->createAdmin();
        $this->actingAs($admin);
        $this->assertSame(2, $product->fresh()->stock);

        $this->put(route('admin.orders.status', $order), [
            'status' => Order::STATUS_CANCELLED,
        ])->assertRedirect(route('admin.orders.show', $order));

        $this->assertSame(Order::STATUS_CANCELLED, $order->fresh()->status);
        $this->assertSame(Payment::STATUS_CANCELLED, $order->payment->fresh()->status);
        $this->assertSame(5, $product->fresh()->stock);
        Mail::assertSent(OrderUpdateMail::class, function (OrderUpdateMail $mail) use ($order) {
            return $mail->hasTo($order->customer_email)
                && $mail->updateMessage === 'Your order has been cancelled.';
        });

        $this->put(route('admin.orders.status', $order), [
            'status' => Order::STATUS_CANCELLED,
        ])->assertSessionHasErrors('status');
        $this->assertSame(5, $product->fresh()->stock);
        Mail::assertSent(OrderUpdateMail::class, 1);
    }

    public function test_paid_order_cannot_be_cancelled_without_a_refund_workflow()
    {
        $product = $this->createProduct(3);
        $order = $this->createOrder($product, 1);
        $order->update(['status' => Order::STATUS_PROCESSING]);
        $order->payment()->update(['status' => Payment::STATUS_CONFIRMED]);
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->put(route('admin.orders.status', $order), ['status' => Order::STATUS_CANCELLED])
            ->assertSessionHasErrors('status');

        $this->assertSame(Order::STATUS_PROCESSING, $order->fresh()->status);
        $this->assertSame(2, $product->fresh()->stock);
    }

    private function createProduct(int $stock): Product
    {
        return Product::create([
            'name' => 'Canvas Bag',
            'slug' => 'canvas-bag',
            'sku' => 'BAG-001',
            'price' => '25.50',
            'stock' => $stock,
        ]);
    }

    private function createOrder(Product $product, int $quantity): Order
    {
        $total = (float) $product->price * $quantity;
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
            'subtotal' => $total,
            'shipping_fee' => 0,
            'total' => $total,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'unit_price' => $product->price,
            'quantity' => $quantity,
            'line_total' => $total,
        ]);
        $order->payment()->create([
            'amount' => $total,
            'status' => Payment::STATUS_AWAITING_TRANSFER,
        ]);
        $product->decrement('stock', $quantity);

        return $order;
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        return $admin;
    }

    private function proofFile(): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/tVQAAAAASUVORK5CYII=');

        return UploadedFile::fake()->createWithContent('transfer-proof.png', $png);
    }
}
