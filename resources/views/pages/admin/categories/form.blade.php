<div class="container py-4" style="max-width: 800px">
    <a href="{{ route('admin.categories.index') }}">← Back to categories</a>
    <h1 class="my-3">{{ $category->exists ? 'Edit category' : 'Add category' }}</h1>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}">
        @csrf
        @if ($category->exists)
            @method('PUT')
        @endif

        <div class="mb-3">
            <label class="form-label" for="name">Name</label>
            <input class="form-control" id="name" name="name" value="{{ old('name', $category->name) }}" maxlength="255" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="slug">URL slug</label>
            <input class="form-control" id="slug" name="slug" value="{{ old('slug', $category->slug) }}"
                   pattern="[a-z0-9]+(-[a-z0-9]+)*" maxlength="255" required>
            <div class="form-text">Use lowercase English letters, numbers, and hyphens, for example home-decor.</div>
        </div>
        <div class="mb-3">
            <label class="form-label" for="description">Description</label>
            <textarea class="form-control" id="description" name="description" rows="4" maxlength="2000">{{ old('description', $category->description) }}</textarea>
        </div>
        <div class="form-check mb-3">
            <input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1"
                   @checked(old('is_active', $category->is_active))>
            <label class="form-check-label" for="is_active">Active and visible in the shop</label>
        </div>
        <button class="btn btn-primary" type="submit">Save category</button>
        <a class="btn btn-outline-secondary" href="{{ route('admin.categories.index') }}">Cancel</a>
    </form>
</div>
