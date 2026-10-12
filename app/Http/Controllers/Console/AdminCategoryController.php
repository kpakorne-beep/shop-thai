<?php

namespace App\Http\Controllers\Console;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminCategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('products')->orderBy('name')->paginate(20);

        return $this->view('pages.admin.categories.index', compact('categories'), 'Categories');
    }

    public function create()
    {
        $category = new Category(['is_active' => true]);

        return $this->view('pages.admin.categories.form', compact('category'), 'Create Category');
    }

    public function store(Request $request)
    {
        $validated = $this->validateCategory($request);
        $validated['is_active'] = $request->boolean('is_active');
        Category::create($validated);

        return redirect()->route('admin.categories.index')->with('status', 'Category created.');
    }

    public function edit(Category $category)
    {
        return $this->view('pages.admin.categories.form', compact('category'), 'Edit '.$category->name);
    }

    public function update(Request $request, Category $category)
    {
        $validated = $this->validateCategory($request, $category);
        $validated['is_active'] = $request->boolean('is_active');
        $category->update($validated);

        return redirect()->route('admin.categories.index')->with('status', 'Category updated.');
    }

    public function toggle(Category $category)
    {
        $category->update(['is_active' => ! $category->is_active]);

        return redirect()->route('admin.categories.index')
            ->with('status', $category->is_active ? 'Category activated.' : 'Category deactivated.');
    }

    private function validateCategory(Request $request, ?Category $category = null): array
    {
        $slugRule = Rule::unique('categories', 'slug');
        if ($category) {
            $slugRule->ignore($category->id);
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slugRule],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
