<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <a href="{{ route('admin.admin_shop.index.get') }}">Administration</a>
            <h1 class="mb-0">Categories</h1>
        </div>
        <a class="btn btn-primary" href="{{ route('admin.categories.create') }}">Add category</a>
    </div>

    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif

    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Products</th>
                    <th>Status</th>
                    <th><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($categories as $category)
                    <tr>
                        <td>{{ $category->name }}</td>
                        <td>{{ $category->slug }}</td>
                        <td>{{ $category->products_count }}</td>
                        <td>{{ $category->is_active ? 'Active' : 'Inactive' }}</td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.categories.edit', $category) }}">Edit</a>
                            <form class="d-inline" method="POST" action="{{ route('admin.categories.toggle', $category) }}">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-sm {{ $category->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}" type="submit">
                                    {{ $category->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No categories yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $categories->links() }}
</div>
