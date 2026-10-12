<div class="container py-4" style="max-width: 1000px">
    <a href="{{ route('admin.orders.index') }}">← Back to orders</a>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 my-3">
        <h1 class="h2 mb-0">Order {{ $order->order_number }}</h1>
        <span class="badge text-bg-secondary">{{ str_replace('_', ' ', ucfirst($order->status)) }}</span>
    </div>

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

    <div class="row g-4">
        <section class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h4">Customer and delivery</h2>
                    <p>{{ $order->customer_name }}<br>{{ $order->customer_email }}<br>{{ $order->customer_phone }}</p>
                    <address class="mb-0">
                        {{ $order->shipping_address }}<br>
                        {{ $order->shipping_subdistrict }}, {{ $order->shipping_district }}<br>
                        {{ $order->shipping_province }} {{ $order->shipping_postal_code }}
                    </address>
                    @if ($order->tracking_number)
                        <p class="mt-3 mb-0"><strong>Tracking:</strong> {{ $order->tracking_number }}</p>
                    @endif
                </div>
            </div>
        </section>

        <section class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h4">Payment</h2>
                    @if ($order->payment)
                        <p><strong>Status:</strong> {{ str_replace('_', ' ', ucfirst($order->payment->status)) }}</p>
                        <p><strong>Amount:</strong> ฿{{ number_format((float) $order->payment->amount, 2) }}</p>
                        <p><strong>Reference:</strong> {{ $order->payment->transaction_reference ?: '—' }}</p>
                        @if ($order->payment->review_note)
                            <p><strong>Review note:</strong> {{ $order->payment->review_note }}</p>
                        @endif
                        @if ($order->payment->proof_path)
                            <a class="btn btn-outline-primary mb-3" href="{{ route('admin.orders.proof', $order) }}" target="_blank" rel="noopener">
                                View transfer proof
                            </a>
                        @endif

                        @if ($order->payment->status === \App\Models\Payment::STATUS_PENDING_REVIEW
                            && $order->status === \App\Models\Order::STATUS_PENDING_PAYMENT)
                            <form method="POST" action="{{ route('admin.orders.payment-review', $order) }}">
                                @csrf
                                <label class="form-label" for="review_note">Review note (required when rejecting)</label>
                                <textarea class="form-control mb-3" id="review_note" name="review_note" rows="2">{{ old('review_note') }}</textarea>
                                <div class="d-flex flex-wrap gap-2">
                                    <button class="btn btn-success" name="decision" value="confirmed" type="submit">Confirm payment</button>
                                    <button class="btn btn-outline-danger" name="decision" value="rejected" type="submit">Reject proof</button>
                                </div>
                            </form>
                        @endif
                    @else
                        <p class="text-danger">No payment record was found for this order.</p>
                    @endif
                </div>
            </div>
        </section>

        <section class="col-12">
            <div class="card">
                <div class="card-body">
                    <h2 class="h4">Items</h2>
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>Product</th><th>SKU</th><th>Unit price</th><th>Quantity</th><th class="text-end">Total</th></tr></thead>
                            <tbody>
                                @foreach ($order->items as $item)
                                    <tr>
                                        <td>{{ $item->product_name }}</td>
                                        <td>{{ $item->sku }}</td>
                                        <td>฿{{ number_format((float) $item->unit_price, 2) }}</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td class="text-end">฿{{ number_format((float) $item->line_total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr><th colspan="4" class="text-end">Subtotal</th><th class="text-end">฿{{ number_format((float) $order->subtotal, 2) }}</th></tr>
                                <tr><th colspan="4" class="text-end">Shipping</th><th class="text-end">฿{{ number_format((float) $order->shipping_fee, 2) }}</th></tr>
                                <tr><th colspan="4" class="text-end">Total</th><th class="text-end">฿{{ number_format((float) $order->total, 2) }}</th></tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        @if (in_array($order->status, [\App\Models\Order::STATUS_PROCESSING, \App\Models\Order::STATUS_SHIPPED, \App\Models\Order::STATUS_PENDING_PAYMENT], true))
            <section class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h2 class="h4">Update order status</h2>
                        <form class="row g-3 align-items-end" method="POST" action="{{ route('admin.orders.status', $order) }}">
                            @csrf
                            @method('PUT')
                            <div class="col-12 col-md-4">
                                <label class="form-label" for="status">Next status</label>
                                <select class="form-select" id="status" name="status" required>
                                    @if ($order->status === \App\Models\Order::STATUS_PROCESSING)
                                        <option value="shipped">Shipped</option>
                                    @elseif ($order->status === \App\Models\Order::STATUS_SHIPPED)
                                        <option value="delivered">Delivered</option>
                                    @elseif ($order->status === \App\Models\Order::STATUS_PENDING_PAYMENT)
                                        <option value="cancelled">Cancel unpaid order and restore stock</option>
                                    @endif
                                </select>
                            </div>
                            @if ($order->status === \App\Models\Order::STATUS_PROCESSING)
                                <div class="col-12 col-md-5">
                                    <label class="form-label" for="tracking_number">Tracking number (optional)</label>
                                    <input class="form-control" id="tracking_number" name="tracking_number"
                                           value="{{ old('tracking_number', $order->tracking_number) }}" maxlength="255">
                                </div>
                            @endif
                            <div class="col-12 col-md-auto">
                                <button class="btn btn-primary" type="submit">Save status</button>
                            </div>
                        </form>
                        @if ($order->status === \App\Models\Order::STATUS_PROCESSING)
                            <p class="small text-muted mt-3 mb-0">Paid orders cannot be cancelled here because refunds are not implemented.</p>
                        @endif
                    </div>
                </div>
            </section>
        @endif
    </div>
</div>
