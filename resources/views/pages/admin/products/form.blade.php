<div class="container py-4" style="max-width: 800px">
    <a href="{{ route('admin.products.index') }}">← Back to products</a>
    <h1 class="my-3">{{ $product->exists ? 'Edit product' : 'Add product' }}</h1>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" enctype="multipart/form-data"
          action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}">
        @csrf
        @if ($product->exists)
            @method('PUT')
        @endif

        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label" for="name">Name</label>
                <input class="form-control" id="name" name="name" value="{{ old('name', $product->name) }}" maxlength="255" required>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="sku">SKU</label>
                <input class="form-control" id="sku" name="sku" value="{{ old('sku', $product->sku) }}" maxlength="255" required>
            </div>
            <div class="col-md-8">
                <label class="form-label" for="slug">URL slug</label>
                <input class="form-control" id="slug" name="slug" value="{{ old('slug', $product->slug) }}" maxlength="255" required>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="category_id">Category</label>
                <select class="form-select" id="category_id" name="category_id">
                    <option value="">Uncategorized</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) old('category_id', $product->category_id) === (string) $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="price">Price</label>
                <input class="form-control" id="price" name="price" type="number" min="0.01" step="0.01" value="{{ old('price', $product->price) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="stock">Stock</label>
                <input class="form-control" id="stock" name="stock" type="number" min="0" step="1" value="{{ old('stock', $product->stock ?? 0) }}" required>
            </div>
            <div class="col-12">
                <label class="form-label" for="description">Description</label>
                <textarea class="form-control" id="description" name="description" rows="4">{{ old('description', $product->description) }}</textarea>
            </div>
            <div class="col-12">
                <label class="form-label" for="image">Product image (JPG, PNG, or WebP; up to 2 MB)</label>
                <input class="form-control" id="image" name="image" type="file" accept=".jpg,.jpeg,.png,.webp">
                @if ($product->image_path)
                    <img class="mt-3" src="{{ asset('storage/'.$product->image_path) }}" alt="" width="120">
                @endif
            </div>
            <div class="col-12">
                <div class="form-check">
                    <input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $product->is_active))>
                    <label class="form-check-label" for="is_active">Visible in the shop</label>
                </div>
            </div>
            <div class="col-12">
                <button class="btn btn-primary" type="submit">Save product</button>
                <a class="btn btn-outline-secondary" href="{{ route('admin.products.index') }}">Cancel</a>
            </div>
        </div>
    </form>
</div>
