<div class="container py-4 py-md-5" style="max-width: 760px">
    <h1 class="mb-3">Track your order</h1>
    <p class="text-muted">Enter the order number and email address used at checkout.</p>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form class="card card-body mb-4" method="POST" action="{{ route('orders.track.lookup') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label" for="order_number">Order number</label>
            <input class="form-control" id="order_number" name="order_number"
                   value="{{ old('order_number') }}" maxlength="32" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="customer_email">Checkout email</label>
            <input class="form-control" id="customer_email" name="customer_email" type="email"
                   value="{{ old('customer_email') }}" maxlength="255" required>
        </div>
        <button class="btn btn-primary align-self-start" type="submit">Find order</button>
    </form>

    @isset($order)
        <section class="card" aria-labelledby="order-status-heading">
            <div class="card-body">
                <h2 class="h4" id="order-status-heading">Order {{ $order->order_number }}</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Order status</dt>
                    <dd class="col-sm-8">{{ str_replace('_', ' ', ucfirst($order->status)) }}</dd>
                    <dt class="col-sm-4">Payment status</dt>
                    <dd class="col-sm-8">{{ $order->payment ? str_replace('_', ' ', ucfirst($order->payment->status)) : 'Unavailable' }}</dd>
                    <dt class="col-sm-4">Total</dt>
                    <dd class="col-sm-8">฿{{ number_format((float) $order->total, 2) }}</dd>
                    @if ($order->tracking_number)
                        <dt class="col-sm-4">Tracking number</dt>
                        <dd class="col-sm-8">{{ $order->tracking_number }}</dd>
                    @endif
                    @if ($order->shipped_at)
                        <dt class="col-sm-4">Shipped at</dt>
                        <dd class="col-sm-8">{{ $order->shipped_at->format('Y-m-d H:i') }}</dd>
                    @endif
                    @if ($order->delivered_at)
                        <dt class="col-sm-4">Delivered at</dt>
                        <dd class="col-sm-8">{{ $order->delivered_at->format('Y-m-d H:i') }}</dd>
                    @endif
                </dl>
            </div>
        </section>
    @endisset
</div>
