<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('admin.admin_shop.index.get') }}">Administration</a>
            <h1 class="mb-0">Orders</h1>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif

    <form class="card card-body mb-4" method="GET" action="{{ route('admin.orders.index') }}">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-lg-5">
                <label class="form-label" for="search">Order number or customer email</label>
                <input class="form-control" id="search" name="search" value="{{ $filters['search'] ?? '' }}"
                       maxlength="100" placeholder="Search orders">
            </div>
            <div class="col-12 col-md-5 col-lg-3">
                <label class="form-label" for="status">Order status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All order statuses</option>
                    @foreach ([
                        'pending_payment' => 'Pending payment',
                        'processing' => 'Processing',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                    ] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-5 col-lg-3">
                <label class="form-label" for="payment_status">Payment status</label>
                <select class="form-select" id="payment_status" name="payment_status">
                    <option value="">All payment statuses</option>
                    @foreach ([
                        'awaiting_transfer' => 'Awaiting transfer',
                        'pending_review' => 'Pending review',
                        'confirmed' => 'Confirmed',
                        'rejected' => 'Rejected',
                        'cancelled' => 'Cancelled',
                    ] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['payment_status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary" type="submit">Apply filters</button>
                <a class="btn btn-outline-secondary" href="{{ route('admin.orders.index') }}">Clear</a>
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Order status</th>
                    <th>Payment status</th>
                    <th>Total</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td>{{ $order->order_number }}</td>
                        <td>{{ $order->customer_name }}<br><small class="text-muted">{{ $order->customer_email }}</small></td>
                        <td>{{ str_replace('_', ' ', ucfirst($order->status)) }}</td>
                        <td>{{ $order->payment ? str_replace('_', ' ', ucfirst($order->payment->status)) : 'Missing' }}</td>
                        <td>฿{{ number_format((float) $order->total, 2) }}</td>
                        <td>{{ $order->created_at->format('Y-m-d H:i') }}</td>
                        <td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.orders.show', $order) }}">Review</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No matching orders.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $orders->links() }}
</div>
