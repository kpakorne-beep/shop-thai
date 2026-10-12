<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f7f7f4">
    <title>Example Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body class="{{ request()->routeIs('login') ? 'admin-login-page' : '' }}">
    @unless (request()->routeIs('login'))
        <nav class="navbar navbar-light site-navbar">
            <div class="container">
                <a class="navbar-brand site-brand" href="{{ route('shop.index') }}">
                    <span class="brand-mark" aria-hidden="true">E</span>
                    <span>Example <span class="brand-accent">Shop</span></span>
                </a>
                <a class="btn btn-outline-primary cart-link" href="{{ route('cart.index') }}">
                    <span aria-hidden="true">Bag</span>
                    <span class="cart-count">{{ array_sum(session('cart', [])) }}</span>
                </a>
                <a class="btn btn-link" href="{{ route('orders.track') }}">Track order</a>
            </div>
        </nav>
    @endunless
    <main class="page {{ request()->routeIs('login') ? 'admin-login-shell' : '' }}">
        @include($page, $data)
    </main>
</body>
</html>