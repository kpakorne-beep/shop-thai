<div class="container shop-page py-4 py-md-5">
    <a class="back-link d-inline-block mb-4" href="{{ route('shop.index') }}"><span aria-hidden="true">←</span> Back to products</a>

    <div class="row g-4 g-lg-5">
        <div class="col-12 col-md-6">
            <div class="detail-media">
                @if ($product->image_path)
                    <img class="img-fluid w-100" src="{{ asset('storage/'.$product->image_path) }}"
                         alt="{{ $product->name }}">
                @else
                    <div class="product-placeholder">No image</div>
                @endif
            </div>
        </div>
        <div class="col-12 col-md-6 d-flex align-items-center">
            <div class="product-details">
            @if ($product->category)
                <div class="product-category mb-2">{{ $product->category->name }}</div>
            @endif
            <h1 class="product-detail-title">{{ $product->name }}</h1>
            <p class="product-detail-price">฿{{ number_format((float) $product->price, 2) }}</p>
            @if ($product->stock > 0)
                <p class="stock-status mb-0"><span aria-hidden="true">●</span> In stock ({{ $product->stock }} available)</p>
                <form class="d-flex flex-wrap gap-2 align-items-end mt-4" method="POST"
                      action="{{ route('cart.products.store', $product) }}">
                    @csrf
                    <div>
                        <label class="form-label" for="quantity">Quantity</label>
                        <input class="form-control" id="quantity" name="quantity" type="number"
                               min="1" max="{{ min($product->stock, 100) }}" value="1" required>
                    </div>
                    <button class="btn btn-primary" type="submit">Add to bag <span aria-hidden="true">+</span></button>
                </form>
            @else
                <p class="text-muted">Out of stock</p>
            @endif
            @if ($product->description)
                <div class="product-description mt-4">{!! nl2br(e($product->description)) !!}</div>
            @endif
            </div>
        </div>
    </div>
</div>
