<div class="container py-4" style="max-width: 760px">
    <a href="{{ route('admin.admin_shop.index.get') }}">Administration</a>
    <h1 class="my-3">Bank transfer details</h1>
    <p class="text-muted">These details are shown to customers on their order confirmation page while payment is due.</p>

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

    <form method="POST" action="{{ route('admin.settings.bank-transfer.update') }}">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label" for="bank_name">Bank name</label>
            <input class="form-control" id="bank_name" name="bank_name" maxlength="120"
                   value="{{ old('bank_name', $settings['bank_name'] ?? '') }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="account_name">Account holder name</label>
            <input class="form-control" id="account_name" name="account_name" maxlength="255"
                   value="{{ old('account_name', $settings['account_name'] ?? '') }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="account_number">Account number</label>
            <input class="form-control" id="account_number" name="account_number" maxlength="40"
                   inputmode="numeric" pattern="[0-9 -]+"
                   value="{{ old('account_number', $settings['account_number'] ?? '') }}" required>
            <div class="form-text">Only digits, spaces, and hyphens are accepted.</div>
        </div>
        <div class="mb-3">
            <label class="form-label" for="instructions">Transfer instructions (optional)</label>
            <textarea class="form-control" id="instructions" name="instructions" rows="4" maxlength="2000">{{ old('instructions', $settings['instructions'] ?? '') }}</textarea>
        </div>
        <button class="btn btn-primary" type="submit">Save bank details</button>
        <a class="btn btn-outline-secondary" href="{{ route('admin.admin_shop.index.get') }}">Cancel</a>
    </form>
</div>
