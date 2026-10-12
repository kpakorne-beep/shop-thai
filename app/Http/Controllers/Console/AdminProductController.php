<?php

namespace App\Http\Controllers\Console;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdminProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category')->latest()->paginate(15);

        return $this->view('pages.admin.products.index', compact('products'), 'Products');
    }

    public function create()
    {
        $product = new Product(['is_active' => true]);
        $categories = Category::where('is_active', true)->orderBy('name')->get();

        return $this->view('pages.admin.products.form', compact('product', 'categories'), 'Create Product');
    }

    public function store(Request $request)
    {
        $validated = $this->validateProduct($request);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('products', 'public');
        }

        $validated['is_active'] = $request->boolean('is_active');
        Product::create($validated);

        return redirect()->route('admin.products.index')->with('status', 'Product created.');
    }

    public function edit(Product $product)
    {
        $categories = Category::where(function ($query) use ($product) {
            $query->where('is_active', true);
            if ($product->category_id) {
                $query->orWhere('id', $product->category_id);
            }
        })
            ->orderBy('name')
            ->get();

        return $this->view('pages.admin.products.form', compact('product', 'categories'), 'Edit '.$product->name);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $this->validateProduct($request, $product);
        $oldImagePath = $product->image_path;

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('products', 'public');
        }

        $validated['is_active'] = $request->boolean('is_active');
        $product->update($validated);

        if (isset($validated['image_path']) && $oldImagePath) {
            Storage::disk('public')->delete($oldImagePath);
        }

        return redirect()->route('admin.products.index')->with('status', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        $imagePath = $product->image_path;
        $product->delete();

        if ($imagePath) {
            Storage::disk('public')->delete($imagePath);
        }

        return redirect()->route('admin.products.index')->with('status', 'Product deleted.');
    }

    private function validateProduct(Request $request, ?Product $product = null): array
    {
        $slugRule = Rule::unique('products', 'slug');
        $skuRule = Rule::unique('products', 'sku');
        if ($product) {
            $slugRule->ignore($product->id);
            $skuRule->ignore($product->id);
        }

        return $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', $slugRule],
            'sku' => ['required', 'string', 'max:255', $skuRule],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'stock' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);
    }
}
