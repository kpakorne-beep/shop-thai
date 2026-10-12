<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Shop administration</h1>
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button class="btn btn-outline-secondary" type="submit">Log out</button>
        </form>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card card-body">
                <div class="text-muted">Products</div>
                <strong class="fs-3">{{ $productCount }}</strong>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card card-body">
                <div class="text-muted">Products with stock at or below 5</div>
                <strong class="fs-3">{{ $lowStockCount }}</strong>
            </div>
        </div>
    </div>

    <a class="btn btn-primary" href="{{ route('admin.products.index') }}">Manage products</a>
    <a class="btn btn-outline-primary" href="{{ route('admin.orders.index') }}">Manage orders</a>
</div>
