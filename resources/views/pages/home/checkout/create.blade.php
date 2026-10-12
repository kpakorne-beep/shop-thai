<div class="container py-4 py-md-5" style="max-width: 960px">
    <h1 class="mb-4">Checkout</h1>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-lg-7">
            <form method="POST" action="{{ route('checkout.store') }}">
                @csrf
                <input type="hidden" name="checkout_token" value="{{ $checkoutToken }}">
                <h2 class="h4 mb-3">Contact and shipping details</h2>
                <div class="mb-3">
                    <label class="form-label" for="customer_name">Full name</label>
                    <input class="form-control" id="customer_name" name="customer_name" value="{{ old('customer_name') }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="customer_email">Email</label>
                    <input class="form-control" id="customer_email" name="customer_email" type="email" value="{{ old('customer_email') }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="customer_phone">Phone</label>
                    <input class="form-control" id="customer_phone" name="customer_phone" type="tel" value="{{ old('customer_phone') }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="shipping_address">Street address</label>
                    <textarea class="form-control" id="shipping_address" name="shipping_address" rows="3" required>{{ old('shipping_address') }}</textarea>
                </div>
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="shipping_subdistrict">Subdistrict</label>
                        <input class="form-control" id="shipping_subdistrict" name="shipping_subdistrict" value="{{ old('shipping_subdistrict') }}" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="shipping_district">District</label>
                        <input class="form-control" id="shipping_district" name="shipping_district" value="{{ old('shipping_district') }}" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="shipping_province">Province</label>
                        <input class="form-control" id="shipping_province" name="shipping_province" value="{{ old('shipping_province') }}" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="shipping_postal_code">Postal code</label>
                        <input class="form-control" id="shipping_postal_code" name="shipping_postal_code" value="{{ old('shipping_postal_code') }}" required>
                    </div>
                </div>
                <button class="btn btn-primary mt-4" type="submit">Place order</button>
            </form>
        </div>

        <aside class="col-12 col-lg-5">
            <div class="card">
                <div class="card-body">
                    <h2 class="h4">Order summary</h2>
                    @foreach ($items as $item)
                        <div class="d-flex justify-content-between gap-3 py-2 border-bottom">
                            <span>{{ $item['product']->name }} × {{ $item['quantity'] }}</span>
                            <span class="text-nowrap">฿{{ number_format($item['line_total'], 2) }}</span>
                        </div>
                    @endforeach
                    <div class="d-flex justify-content-between pt-3">
                        <span>Subtotal</span><span>฿{{ number_format($subtotal, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span>Shipping</span><span>฿{{ number_format($shippingFee, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between fw-bold fs-5 border-top pt-3">
                        <span>Total</span><span>฿{{ number_format($total, 2) }}</span>
                    </div>
                    <p class="small text-muted mt-3 mb-0">Shipping is currently free. Payment is by bank transfer and awaits store review.</p>
                </div>
            </div>
        </aside>
    </div>
</div>
