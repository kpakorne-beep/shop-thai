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
                    <tr><td colspan="7" class="text-center text-muted py-4">No orders yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $orders->links() }}
</div>
