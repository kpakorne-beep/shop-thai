<div class="container shop-page py-4 py-md-5">
    <header class="shop-hero mb-4 mb-md-5">
        <div class="shop-hero-content">
            <span class="eyebrow">A LITTLE SOMETHING FOR YOU</span>
            <h1>Good things.<br><span>Great finds.</span></h1>
            <p>Thoughtfully picked pieces to make every day a little better.</p>
            <a class="btn btn-light hero-link" href="#shop-products">Explore the collection <span aria-hidden="true">↓</span></a>
        </div>
        <div class="hero-art" aria-hidden="true">
            <span class="hero-orbit hero-orbit-one"></span>
            <span class="hero-orbit hero-orbit-two"></span>
            <span class="hero-spark">✳</span>
        </div>
    </header>

    <section id="shop-products" aria-labelledby="products-heading">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
            <div>
                <span class="section-kicker">THE GOOD STUFF</span>
                <h2 id="products-heading" class="section-title mb-0">Find your favorite</h2>
            </div>
            <span class="text-muted small">Made for your everyday</span>
        </div>
        <form class="shop-filters mb-4" method="GET" action="{{ route('shop.index') }}#shop-products">
            <div class="search-field">
                <label class="visually-hidden" for="search">Search products</label>
                <span class="search-icon" aria-hidden="true">⌕</span>
                <input class="form-control" id="search" name="search" type="search"
                       value="{{ $search }}" placeholder="Search something lovely...">
            </div>
            <div class="category-field">
                <label class="visually-hidden" for="category">Category</label>
                <select class="form-select" id="category" name="category">
                    <option value="">All categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->slug }}" @selected($categorySlug === $category->
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="filter-actions">
                <button class="btn btn-primary" type="submit">Find products</button>
                @if ($search || $categorySlug)
                    <a class="btn btn-outline-secondary" href="{{ route('shop.index') }}">Clear</a>
                @endif
            </div>
        </form>

        @if ($products->isEmpty())
            <div class="empty-state" role="status">
                <span class="empty-icon" aria-hidden="true">✳</span>
                <h3>No products found.</h3>
                <p>Try another search or browse all categories.</p>
            </div>
        @else
            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-3 g-md-4">
                @foreach ($products as $product)
                    <div class="col">
                        <article class="card product-card h-100">
                            <a class="product-link" href="{{ route('shop.products.show', $product->slug) }}">
                                <div class="product-media">
                                    @if ($product->image_path)
                                        <img class="card-img-top" src="{{ asset('storage/'.$product->image_path) }}"
                                             alt="{{ $product->name }}">
                                    @else
                                        <div class="product-placeholder">No image</div>
                                    @endif
                                </div>
                                <div class="card-body">
                                    @if ($product->category)
                                        <div class="product-category">{{ $product->category->name }}</div>
                                    @endif
                                    <h3 class="product-title">{{ $product->name }}</h3>
                                    <div class="product-price">฿{{ number_format((float) $product->price, 2) }}</div>
                                </div>
                            </a>
                            <div class="card-footer">
                                @if ($product->stock > 0)
                                    <div class="d-flex justify-content-between align-items-center gap-2">
                                        <span class="stock-status"><span aria-hidden="true">●</span> In stock</span>
                                        <form method="POST" action="{{ route('cart.products.store', $product) }}">
                                            @csrf
                                            <input type="hidden" name="quantity" value="1">
                                            <button class="btn btn-sm btn-primary" type="submit">Add to bag <span aria-hidden="true">+</span></button>
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
    </section>
</div>
