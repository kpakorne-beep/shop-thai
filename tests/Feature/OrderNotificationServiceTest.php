<?php

namespace Tests\Feature;

use App\Mail\OrderUpdateMail;
use App\Models\Order;
use App\Services\OrderNotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Swift_TransportException;
use Tests\TestCase;

class OrderNotificationServiceTest extends TestCase
{
    public function test_transport_failure_is_logged_without_throwing()
    {
        $order = new Order([
            'order_number' => 'EXAMPLE-123',
            'customer_email' => 'customer@example.test',
        ]);
        $order->setAttribute('id', 42);
        $order->setRelation('items', collect());
        $order->setRelation('payment', null);

        $mailer = Mockery::mock();
        Mail::shouldReceive('to')
            ->once()
            ->with($order->customer_email)
            ->andReturn($mailer);
        $mailer->shouldReceive('send')
            ->once()
            ->with(Mockery::type(OrderUpdateMail::class))
            ->andThrow(new Swift_TransportException('Mail server unavailable.'));
        Log::shouldReceive('error')
            ->once()
            ->with('Unable to send order update email.', Mockery::on(function ($context) {
                return $context['order_id'] === 42
                    && $context['exception'] instanceof Swift_TransportException;
            }));

        app(OrderNotificationService::class)->send($order, 'Order received.');
    }
}
