<div class="container py-4 py-md-5" style="max-width: 960px">
    <h1 class="mb-4">Your cart</h1>

    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($items->isEmpty())
        <div class="alert alert-light border">Your cart is empty.</div>
        <a class="btn btn-primary" href="{{ route('shop.index') }}">Browse products</a>
    @else
        <form method="POST" action="{{ route('cart.update') }}">
            @csrf
            @method('PUT')
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th style="width: 150px">Quantity</th>
                            <th class="text-end">Subtotal</th>
                            <th><span class="visually-hidden">Remove</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $productId => $item)
                            <tr>
                                <td>
                                    @if ($item['product'])
                                        <a href="{{ route('shop.products.show', $item['product']->slug) }}">
                                            {{ $item['product']->name }}
                                        </a>
                                    @else
                                        Unavailable product
                                    @endif
                                    @unless ($item['available'])
                                        <div class="small text-danger">Unavailable or insufficient stock. Remove this item or adjust quantity.</div>
                                    @endunless
                                </td>
                                <td>
                                    @if ($item['product'])
                                        ฿{{ number_format((float) $item['product']->price, 2) }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if ($item['product'] && $item['product']->is_active && $item['product']->stock > 0)
                                        <input class="form-control" type="number" min="1"
                                               max="{{ min($item['product']->stock, 100) }}"
                                               name="quantities[{{ $productId }}]" value="{{ $item['quantity'] }}"
                                               aria-label="Quantity for {{ $item['product']->name }}">
                                    @else
                                        {{ $item['quantity'] }}
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if ($item['product'])
                                        ฿{{ number_format($item['line_total'], 2) }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-danger" type="submit"
                                            form="remove-product-{{ $productId }}">Remove</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-end">Subtotal</th>
                            <th class="text-end">฿{{ number_format($subtotal, 2) }}</th>
                            <th></th>
                        </tr>
                        <tr>
                            <td colspan="5">
                                <button class="btn btn-outline-primary" type="submit">Update cart</button>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </form>
        @foreach ($items as $productId => $item)
            <form id="remove-product-{{ $productId }}" method="POST"
                  action="{{ route('cart.products.destroy', $productId) }}" class="d-none">
                @csrf
                @method('DELETE')
            </form>
        @endforeach

        @if ($hasUnavailableItems)
            <div class="alert alert-warning">Update or remove unavailable items before checkout.</div>
        @else
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <a href="{{ route('shop.index') }}">Continue shopping</a>
                <a class="btn btn-primary" href="{{ route('checkout.create') }}">Proceed to checkout</a>
            </div>
        @endif
    @endif
</div>
