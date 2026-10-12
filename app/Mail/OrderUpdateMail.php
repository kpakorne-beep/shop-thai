<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderUpdateMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;

    public $updateMessage;

    public function __construct(Order $order, string $updateMessage)
    {
        $this->order = $order;
        $this->updateMessage = $updateMessage;
    }

    public function build()
    {
        return $this->subject('Example Shop order '.$this->order->order_number)
            ->view('emails.orders.update');
    }
}
