<h1>{{ $updateMessage }}</h1>

<p>Order number: <strong>{{ $order->order_number }}</strong></p>
<p>Order status: {{ str_replace('_', ' ', ucfirst($order->status)) }}</p>

@if ($order->payment)
    <p>Payment status: {{ str_replace('_', ' ', ucfirst($order->payment->status)) }}</p>
@endif

<p>Total: ฿{{ number_format((float) $order->total, 2) }}</p>

@if ($order->tracking_number)
    <p>Tracking number: {{ $order->tracking_number }}</p>
@endif

@if ($order->payment && $order->payment->review_note)
    <p>Payment review note: {{ $order->payment->review_note }}</p>
@endif

<h2>Items</h2>
<ul>
    @foreach ($order->items as $item)
        <li>{{ $item->product_name }} × {{ $item->quantity }} — ฿{{ number_format((float) $item->line_total, 2) }}</li>
    @endforeach
</ul>

<p><a href="{{ route('checkout.confirmation', $order->order_number) }}">View your order</a></p>

<p>This is an automated message from Example Shop. Please do not reply with payment card or account passwords.</p>
