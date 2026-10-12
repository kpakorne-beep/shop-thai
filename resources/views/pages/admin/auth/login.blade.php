<div class="container admin-login py-5">
    <div class="admin-login-layout">
        <aside class="admin-login-aside">
            <a class="admin-login-brand" href="{{ route('shop.index') }}">
                <span class="brand-mark" aria-hidden="true">E</span>
                <span>Example <span class="brand-accent">Shop</span></span>
            </a>
            <div class="admin-login-art" aria-hidden="true">
                <span class="admin-login-orbit admin-login-orbit-one"></span>
                <span class="admin-login-orbit admin-login-orbit-two"></span>
                <span class="admin-login-spark">✳</span>
            </div>
            <div class="admin-login-aside-copy">
                <span class="admin-login-eyebrow">YOUR STORE, IN GOOD HANDS</span>
                <p>Everything<br>in its right place.</p>
                <span>Manage your products, orders, and the little details that make your shop yours.</span>
            </div>
        </aside>

        <section class="admin-login-panel" aria-labelledby="admin-login-title">
            <header class="admin-login-heading">
                <span class="admin-login-kicker">ADMINISTRATOR ACCESS</span>
                <h1 id="admin-login-title">Welcome back</h1>
                <p>Sign in to continue to your shop dashboard.</p>
            </header>

            @if ($errors->any())
                <div class="alert alert-danger admin-login-error" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form class="admin-login-form" method="POST" action="{{ route('login.store') }}">
                @csrf
                <div class="admin-login-field">
                    <label class="form-label" for="email">Email address</label>
                    <input class="form-control" id="email" name="email" type="email"
                           value="{{ old('email') }}" placeholder="you@example.com"
                           autocomplete="username" required autofocus>
                </div>
                <div class="admin-login-field">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-control" id="password" name="password" type="password"
                           placeholder="Enter your password" autocomplete="current-password" required>
                </div>
                <div class="form-check admin-login-remember">
                    <input class="form-check-input" id="remember" name="remember" type="checkbox" value="1">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>
                <button class="btn btn-primary admin-login-submit" type="submit">
                    Sign in <span aria-hidden="true">→</span>
                </button>
            </form>

            <p class="admin-login-footnote">Secure access for Example Shop administrators.</p>
        </section>
    </div>
</div>
