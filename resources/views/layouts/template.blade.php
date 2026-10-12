<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f7f7f4">
    <title>{{ $title }} | Example Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}" rel="stylesheet">
</head>
<body class="{{ request()->routeIs('login') ? 'admin-login-page' : '' }}">
    @unless (request()->routeIs('login'))
        <nav class="navbar navbar-light site-navbar" aria-label="Main navigation">
            <div class="container">
                <a class="navbar-brand site-brand" href="{{ route('shop.index') }}">
                    <span class="brand-mark" aria-hidden="true">E</span>
                    <span>Example <span class="brand-accent">Shop</span></span>
                </a>

                <div class="site-nav-actions" aria-label="Quick links">
                    <button class="site-menu-toggle" type="button" aria-label="Toggle navigation" aria-controls="siteMenu" aria-expanded="false">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>

                    <a class="btn btn-outline-primary cart-link" href="{{ route('cart.index') }}" aria-label="Shopping cart, {{ array_sum(session('cart', [])) }} items">
                        <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M0 1.5A.5.5 0 0 1 .5 1h1a.5.5 0 0 1 .485.379L2.89 6H14.5a.5.5 0 0 1 .49.598l-1 5A.5.5 0 0 1 13.5 12h-9a.5.5 0 0 1-.485-.379L1.11 2H.5a.5.5 0 0 1-.5-.5ZM3.11 7l.8 4h9.18l.8-4H3.11ZM5 14a1 1 0 1 0 0 2 1 1 0 0 0 0-2Zm7 0a1 1 0 1 0 0 2 1 1 0 0 0 0-2Z"/>
                        </svg>
                        <span class="cart-count">{{ array_sum(session('cart', [])) }}</span>
                    </a>

                    <a class="btn btn-link site-track-link" href="{{ route('orders.track') }}">
                        <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10m0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6"/>
                        </svg>
                        Track order
                    </a>
                </div>
            </div>

            <div class="site-nav-panel" id="siteMenu">
                <a href="{{ route('cart.index') }}">Cart</a>
                <a href="{{ route('orders.track') }}">Track order</a>
            </div>
        </nav>
    @endunless
    <main class="page{{ request()->routeIs('login') ? ' admin-login-shell' : '' }}">
        @include($page, $data)
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggle = document.querySelector('.site-menu-toggle');
            const panel = document.getElementById('siteMenu');

            if (!toggle || !panel) {
                return;
            }

            const closeMenu = () => {
                panel.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
            };

            toggle.addEventListener('click', function () {
                const isOpen = panel.classList.toggle('is-open');
                toggle.setAttribute('aria-expanded', String(isOpen));
            });

            panel.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', closeMenu);
            });
        });
    </script>
</body>
</html>