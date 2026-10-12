<div class="container py-4 py-md-5">
    <header class="mb-4">
        <h1 class="display-5 fw-bold">Example Shop</h1>
        <p class="text-muted mb-0">Find something you'll love.</p>
    </header>

    <form class="row g-2 mb-4" method="GET" action="{{ route('shop.index') }}">
        <div class="col-12 col-md">
            <label class="visually-hidden" for="search">Search products</label>
            <input class="form-control" id="search" name="search" type="search"
                   value="{{ $search }}" placeholder="Search products">
        </div>
        <div class="col-12 col-md-4">
            <label class="visually-hidden" for="category">Category</label>
            <select class="form-select" id="category" name="category">
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->slug }}" @selected($categorySlug === $category->slug)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-auto d-flex gap-2">
            <button class="btn btn-primary flex-grow-1" type="submit">Search</button>
            @if ($search || $categorySlug)
                <a class="btn btn-outline-secondary" href="{{ route('shop.index') }}">Clear</a>
            @endif
        </div>
    </form>

    @if ($products->isEmpty())
        <div class="alert alert-light border" role="status">No products found.</div>
    @else
        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-3 g-md-4">
            @foreach ($products as $product)
                <div class="col">
                    <article class="card h-100">
                        <a class="text-decoration-none" href="{{ route('shop.products.show', $product->slug) }}">
                            @if ($product->image_path)
                                <img class="card-img-top" src="{{ asset('storage/'.$product->image_path) }}"
                                     alt="{{ $product->name }}" style="height: 220px; object-fit: cover">
                            @else
                                <div class="bg-light d-flex align-items-center justify-content-center text-muted"
                                     style="height: 220px">No image</div>
                            @endif
                            <div class="card-body">
                                @if ($product->category)
                                    <div class="small text-muted mb-1">{{ $product->category->name }}</div>
                                @endif
                                <h2 class="h5 card-title text-dark">{{ $product->name }}</h2>
                                <div class="fw-semibold text-dark">฿{{ number_format((float) $product->price, 2) }}</div>
                            </div>
                        </a>
                        <div class="card-footer bg-white border-0 pt-0">
                            @if ($product->stock > 0)
                                <div class="d-flex justify-content-between align-items-center gap-2">
                                    <span class="small text-success">In stock</span>
                                    <form method="POST" action="{{ route('cart.products.store', $product) }}">
                                        @csrf
                                        <input type="hidden" name="quantity" value="1">
                                        <button class="btn btn-sm btn-primary" type="submit">Add to cart</button>
                                    </form>
                                </div>
                            @else
                                <span class="small text-muted">Out of stock</span>
                            @endif
                        </div>
                    </article>
                </div>
            @endforeach
        </div>

        @if ($products->hasPages())
            @php
                $startPage = max(1, $products->currentPage() - 2);
                $endPage = min($products->lastPage(), $products->currentPage() + 2);
            @endphp
            <nav class="mt-4" aria-label="Product pages">
                <ul class="pagination justify-content-center flex-wrap">
                    <li class="page-item {{ $products->onFirstPage() ? 'disabled' : '' }}">
                        @if ($products->onFirstPage())
                            <span class="page-link">Previous</span>
                        @else
                            <a class="page-link" href="{{ $products->previousPageUrl() }}">Previous</a>
                        @endif
                    </li>
                    @for ($page = $startPage; $page <= $endPage; $page++)
                        <li class="page-item {{ $page === $products->currentPage() ? 'active' : '' }}">
                            @if ($page === $products->currentPage())
                                <span class="page-link">{{ $page }}</span>
                            @else
                                <a class="page-link" href="{{ $products->url($page) }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endfor
                    <li class="page-item {{ $products->hasMorePages() ? '' : 'disabled' }}">
                        @if ($products->hasMorePages())
                            <a class="page-link" href="{{ $products->nextPageUrl() }}">Next</a>
                        @else
                            <span class="page-link">Next</span>
                        @endif
                    </li>
                </ul>
            </nav>
        @endif
    @endif
</div>
