<div class="container py-5" style="max-width: 760px">
    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="alert alert-success">
        <h1 class="h3">Order received</h1>
        <p class="mb-0">Your order has been created. Keep the order number to check its status.</p>
    </div>

    <div class="card">
        <div class="card-body">
            <p><strong>Order number:</strong> {{ $order->order_number }}</p>
            <p><strong>Order status:</strong> {{ str_replace('_', ' ', ucfirst($order->status)) }}</p>
            <p><strong>Payment status:</strong> {{ str_replace('_', ' ', ucfirst($order->payment->status)) }}</p>
            @if ($order->payment->review_note)
                <p><strong>Payment review note:</strong> {{ $order->payment->review_note }}</p>
            @endif
            @if ($order->tracking_number)
                <p><strong>Tracking number:</strong> {{ $order->tracking_number }}</p>
            @endif
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

    @if (in_array($order->payment->status, [\App\Models\Payment::STATUS_AWAITING_TRANSFER, \App\Models\Payment::STATUS_REJECTED], true))
        <div class="card mt-4">
            <div class="card-body">
                <h2 class="h4">Submit bank transfer proof</h2>
                <p class="text-muted">Enter the email used for the order and attach an image or PDF. The store has not configured bank account details on this page yet.</p>
                <form method="POST" enctype="multipart/form-data" action="{{ route('checkout.payment-proof', $order->order_number) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="customer_email">Order email</label>
                        <input class="form-control" id="customer_email" name="customer_email" type="email"
                               value="{{ old('customer_email', $order->customer_email) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="transaction_reference">Transfer reference (optional)</label>
                        <input class="form-control" id="transaction_reference" name="transaction_reference"
                               value="{{ old('transaction_reference') }}" maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="proof">Proof (JPG, PNG, WebP, or PDF; up to 5 MB)</label>
                        <input class="form-control" id="proof" name="proof" type="file"
                               accept=".jpg,.jpeg,.png,.webp,.pdf" required>
                    </div>
                    <button class="btn btn-primary" type="submit">Send for review</button>
                </form>
            </div>
        </div>
    @elseif ($order->payment->status === \App\Models\Payment::STATUS_PENDING_REVIEW)
        <div class="alert alert-info mt-4">Your transfer proof was received and is waiting for review.</div>
    @endif

    <p class="text-muted mt-3">Keep the order number and this link to revisit the order status.</p>
    <a class="btn btn-primary" href="{{ route('shop.index') }}">Continue shopping</a>
</div>
