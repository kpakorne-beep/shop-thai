<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <a href="{{ route('admin.admin_shop.index.get') }}">Administration</a>
            <h1 class="mb-0">Products</h1>
        </div>
        <a class="btn btn-primary" href="{{ route('admin.products.create') }}">Add product</a>
    </div>

    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif

    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Name / SKU</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td>
                            @if ($product->image_path)
                                <img src="{{ asset('storage/'.$product->image_path) }}" alt="" width="56" height="56" style="object-fit: cover">
                            @else
                                <span class="text-muted">No image</span>
                            @endif
                        </td>
                        <td>{{ $product->name }}<br><small class="text-muted">{{ $product->sku }}</small></td>
                        <td>{{ $product->category ? $product->category->name : '—' }}</td>
                        <td>{{ number_format((float) $product->price, 2) }}</td>
                        <td>{{ $product->stock }}</td>
                        <td>{{ $product->is_active ? 'Active' : 'Inactive' }}</td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.products.edit', $product) }}">Edit</a>
                            <form class="d-inline" method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Delete this product?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No products yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $products->links() }}
</div>
