<div class="container py-4 py-md-5">
    <a class="d-inline-block mb-4" href="{{ route('shop.index') }}">← Back to products</a>

    <div class="row g-4 g-lg-5">
        <div class="col-12 col-md-6">
            @if ($product->image_path)
                <img class="img-fluid rounded w-100" src="{{ asset('storage/'.$product->image_path) }}"
                     alt="{{ $product->name }}" style="max-height: 560px; object-fit: cover">
            @else
                <div class="bg-light rounded d-flex align-items-center justify-content-center text-muted"
                     style="height: 360px">No image</div>
            @endif
        </div>
        <div class="col-12 col-md-6">
            @if ($product->category)
                <div class="text-muted mb-2">{{ $product->category->name }}</div>
            @endif
            <h1 class="display-6 fw-bold">{{ $product->name }}</h1>
            <p class="h3 text-primary my-3">฿{{ number_format((float) $product->price, 2) }}</p>
            @if ($product->stock > 0)
                <p class="text-success">In stock ({{ $product->stock }} available)</p>
            @else
                <p class="text-muted">Out of stock</p>
            @endif
            @if ($product->description)
                <div class="mt-4">{!! nl2br(e($product->description)) !!}</div>
            @endif
        </div>
    </div>
</div>
