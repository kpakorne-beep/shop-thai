<?php

namespace App\Services;

use App\Mail\OrderUpdateMail;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Swift_TransportException;

class OrderNotificationService
{
    public function send(Order $order, string $message): void
    {
        $order->loadMissing(['items', 'payment']);

        try {
            Mail::to($order->customer_email)->send(new OrderUpdateMail($order, $message));
        } catch (Swift_TransportException $exception) {
            Log::error('Unable to send order update email.', [
                'order_id' => $order->getKey(),
                'exception' => $exception,
            ]);
        }
    }
}
