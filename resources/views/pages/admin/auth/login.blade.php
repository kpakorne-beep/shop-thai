<div class="container py-5" style="max-width: 480px">
    <h1 class="mb-4">Administrator login</h1>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('login.store') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label" for="email">Email</label>
            <input class="form-control" id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
        </div>
        <div class="mb-3">
            <label class="form-label" for="password">Password</label>
            <input class="form-control" id="password" name="password" type="password" required>
        </div>
        <div class="form-check mb-3">
            <input class="form-check-input" id="remember" name="remember" type="checkbox" value="1">
            <label class="form-check-label" for="remember">Remember me</label>
        </div>
        <button class="btn btn-primary w-100" type="submit">Log in</button>
    </form>
</div>
