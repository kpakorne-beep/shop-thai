<div class="container py-5" style="max-width: 760px">
    <div class="alert alert-success">
        <h1 class="h3">Order received</h1>
        <p class="mb-0">Your order has been created and is awaiting bank transfer.</p>
    </div>

    <div class="card">
        <div class="card-body">
            <p><strong>Order number:</strong> {{ $order->order_number }}</p>
            <p><strong>Order status:</strong> {{ str_replace('_', ' ', ucfirst($order->status)) }}</p>
            <p><strong>Payment status:</strong> {{ str_replace('_', ' ', ucfirst($order->payment->status)) }}</p>
            <hr>
            @foreach ($order->items as $item)
                <div class="d-flex justify-content-between gap-3 py-2">
                    <span>{{ $item->product_name }} × {{ $item->quantity }}</span>
                    <span class="text-nowrap">฿{{ number_format((float) $item->line_total, 2) }}</span>
                </div>
            @endforeach
            <div class="d-flex justify-content-between border-top pt-3 mt-2 fw-bold">
                <span>Total (shipping free)</span>
                <span>฿{{ number_format((float) $order->total, 2) }}</span>
            </div>
        </div>
    </div>

    <p class="text-muted mt-3">The store will provide bank transfer instructions. Keep your order number for reference.</p>
    <a class="btn btn-primary" href="{{ route('shop.index') }}">Continue shopping</a>
</div>
